import AsyncStorage from '@react-native-async-storage/async-storage';
import NetInfo from '@react-native-community/netinfo';
import * as Crypto from 'expo-crypto';
import { Directory, File, Paths } from 'expo-file-system';
import { useSyncExternalStore } from 'react';
import { AppState } from 'react-native';

import { api, ApiError, SinConexion, subirArchivo } from './api';

/**
 * Cola de pendientes ("outbox"). Todo lo que se captura en obra —avances y
 * gastos— entra primero aquí y se envía en cuanto hay señal. Cada registro
 * lleva un uuid: si la respuesta se pierde y se reintenta, el servidor no lo duplica.
 */

export type Pendiente = {
  uuid: string;
  tipo: 'avance' | 'movimiento';
  cotizacionId: number;
  obra: string;
  resumen: string;
  cuerpo: Record<string, unknown>;
  foto: string | null; // copia local en Documentos/pendientes
  creado: string;
  error: string | null; // el servidor lo rechazó (p. ej. validación): requiere atención
  idServidor: number | null; // ya se creó, solo falta subir la foto
};

const CLAVE = 'konstruktor.cola';
let pendientes: Pendiente[] = [];
let cargada = false;
let enviando: Promise<void> | null = null;
const oyentes = new Set<() => void>();

function avisar() {
  oyentes.forEach((fn) => fn());
}

async function persistir() {
  await AsyncStorage.setItem(CLAVE, JSON.stringify(pendientes));
  avisar();
}

async function cargar() {
  if (cargada) return;
  const texto = await AsyncStorage.getItem(CLAVE);
  pendientes = texto ? (JSON.parse(texto) as Pendiente[]) : [];
  cargada = true;
  avisar();
}

function carpeta(): Directory {
  const dir = new Directory(Paths.document, 'pendientes');
  if (!dir.exists) dir.create({ intermediates: true, idempotent: true });
  return dir;
}

function borrarFoto(uri: string | null) {
  if (!uri) return;
  try {
    const f = new File(uri);
    if (f.exists) f.delete();
  } catch {
    // Si ya no existe no importa.
  }
}

/** Agrega un registro a la cola (copiando la foto a un lugar que el sistema no borra) e intenta enviarlo. */
export async function encolar(p: Omit<Pendiente, 'uuid' | 'creado' | 'error' | 'idServidor' | 'foto'>, fotoUri: string | null) {
  await cargar();
  const uuid = Crypto.randomUUID();
  let foto: string | null = null;
  if (fotoUri) {
    const destino = new File(carpeta(), `${uuid}.jpg`);
    await new File(fotoUri).copy(destino);
    foto = destino.uri;
  }
  pendientes = [...pendientes, { ...p, uuid, foto, creado: new Date().toISOString(), error: null, idServidor: null }];
  await persistir();
  await sincronizar();
  return pendientes.some((x) => x.uuid === uuid) ? 'pendiente' : 'enviado';
}

async function enviarUno(p: Pendiente): Promise<void> {
  let id = p.idServidor;
  if (id === null) {
    const ruta = p.tipo === 'avance' ? `/api/cotizaciones/${p.cotizacionId}/avances` : `/api/cotizaciones/${p.cotizacionId}/movimientos`;
    const r = await api<{ id: number }>(ruta, 'POST', { ...p.cuerpo, uuid: p.uuid });
    id = r.id;
    // Se guarda el id para no volver a crear el registro si falla la foto.
    pendientes = pendientes.map((x) => (x.uuid === p.uuid ? { ...x, idServidor: id } : x));
    await persistir();
  }
  if (p.foto && new File(p.foto).exists) {
    await subirArchivo(p.tipo === 'avance' ? `/api/avances/${id}/foto` : `/api/movimientos/${id}/comprobante`, p.foto);
  }
}

/** Envía todo lo pendiente. Se detiene al primer error de conexión; los rechazos quedan marcados. */
export function sincronizar(): Promise<void> {
  enviando ??= (async () => {
    await cargar();
    for (const p of [...pendientes]) {
      if (p.error) continue;
      try {
        await enviarUno(p);
        borrarFoto(p.foto);
        pendientes = pendientes.filter((x) => x.uuid !== p.uuid);
        await persistir();
      } catch (e) {
        if (e instanceof SinConexion) break;
        if (e instanceof ApiError && e.status === 401) break; // la sesión manda al login
        const mensaje = e instanceof Error ? e.message : 'Error desconocido';
        pendientes = pendientes.map((x) => (x.uuid === p.uuid ? { ...x, error: mensaje } : x));
        await persistir();
      }
    }
  })().finally(() => {
    enviando = null;
  });
  return enviando;
}

export async function reintentar(uuid: string) {
  pendientes = pendientes.map((x) => (x.uuid === uuid ? { ...x, error: null } : x));
  await persistir();
  await sincronizar();
}

export async function descartar(uuid: string) {
  const p = pendientes.find((x) => x.uuid === uuid);
  borrarFoto(p?.foto ?? null);
  pendientes = pendientes.filter((x) => x.uuid !== uuid);
  await persistir();
}

/** Al cerrar sesión se descarta lo que no se envió (pertenece a ese usuario). */
export async function vaciar() {
  pendientes.forEach((p) => borrarFoto(p.foto));
  pendientes = [];
  await persistir();
}

export function usePendientes(): Pendiente[] {
  return useSyncExternalStore(
    (fn) => {
      oyentes.add(fn);
      cargar();
      return () => oyentes.delete(fn);
    },
    () => pendientes,
  );
}

/** Reintenta solo al recuperar señal y al volver a abrir la app. */
export function iniciarSincronizacion(): () => void {
  const quitarRed = NetInfo.addEventListener((estado) => {
    if (estado.isConnected && estado.isInternetReachable !== false) sincronizar();
  });
  const quitarApp = AppState.addEventListener('change', (s) => {
    if (s === 'active') sincronizar();
  });
  return () => {
    quitarRed();
    quitarApp.remove();
  };
}
