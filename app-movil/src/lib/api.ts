import AsyncStorage from '@react-native-async-storage/async-storage';
import * as SecureStore from 'expo-secure-store';
import { File } from 'expo-file-system';

/**
 * Cliente de la API del sistema web (PHP). La app se autentica con un token
 * Bearer que entrega POST /api/auth/token; el token vive en SecureStore.
 */

const CLAVE_SERVIDOR = 'konstruktor.servidor';
const CLAVE_TOKEN = 'konstruktor.token';
const PREFIJO_CACHE = 'konstruktor.cache:';

/** Servidor por defecto: EXPO_PUBLIC_API_URL en .env (ver README de la app). */
export const SERVIDOR_POR_DEFECTO = process.env.EXPO_PUBLIC_API_URL ?? '';

let servidor = '';
let token: string | null = null;
let alExpirar: (() => void) | null = null;

export class ApiError extends Error {
  constructor(
    message: string,
    public status: number,
  ) {
    super(message);
  }
}

/** Sin señal o el servidor no respondió: lo que se guardó queda en la cola de pendientes. */
export class SinConexion extends Error {
  constructor() {
    super('Sin conexión con el servidor');
  }
}

export function normalizarServidor(url: string): string {
  let u = url.trim().replace(/\/+$/, '');
  if (u && !/^https?:\/\//i.test(u)) u = 'https://' + u;
  return u;
}

export async function cargarCredenciales(): Promise<{ servidor: string; token: string | null }> {
  servidor = (await SecureStore.getItemAsync(CLAVE_SERVIDOR)) ?? SERVIDOR_POR_DEFECTO;
  token = await SecureStore.getItemAsync(CLAVE_TOKEN);
  return { servidor, token };
}

export async function guardarCredenciales(nuevoServidor: string, nuevoToken: string): Promise<void> {
  servidor = nuevoServidor;
  token = nuevoToken;
  await SecureStore.setItemAsync(CLAVE_SERVIDOR, nuevoServidor);
  await SecureStore.setItemAsync(CLAVE_TOKEN, nuevoToken);
}

export async function borrarCredenciales(): Promise<void> {
  token = null;
  await SecureStore.deleteItemAsync(CLAVE_TOKEN);
  await borrarCache();
}

/** Consultas guardadas para usar sin señal (obras, avance, gastos). */
export async function contarCache(): Promise<number> {
  const claves = await AsyncStorage.getAllKeys();
  return claves.filter((k) => k.startsWith(PREFIJO_CACHE)).length;
}

export async function borrarCache(): Promise<void> {
  const claves = await AsyncStorage.getAllKeys();
  await AsyncStorage.multiRemove(claves.filter((k) => k.startsWith(PREFIJO_CACHE)));
}

/** La sesión avisa aquí para volver al login cuando el servidor rechaza el token (401). */
export function alExpirarSesion(fn: () => void): void {
  alExpirar = fn;
}

export function servidorActual(): string {
  return servidor;
}

/** Encabezados para cargar archivos protegidos (p. ej. la foto de un comprobante en <Image>). */
export function encabezadosAuth(): Record<string, string> {
  return token ? { Authorization: `Bearer ${token}`, 'X-Auth-Token': token } : {};
}

/** Rechaza con SinConexion si la respuesta tarda más de `ms` (señal débil en obra). */
export function conLimite<T>(promesa: Promise<T>, ms: number): Promise<T> {
  return new Promise((resolve, reject) => {
    const t = setTimeout(() => reject(new SinConexion()), ms);
    promesa.then(resolve, reject).finally(() => clearTimeout(t));
  });
}

async function solicitar(url: string, init: RequestInit): Promise<Response> {
  try {
    return await fetch(url, init);
  } catch {
    throw new SinConexion();
  }
}

async function leer<T>(res: Response): Promise<T> {
  const data = await res.json().catch(() => ({}));
  if (res.status === 401 && token) {
    alExpirar?.();
  }
  if (!res.ok) {
    throw new ApiError((data as { error?: string }).error || `Error del servidor (${res.status})`, res.status);
  }
  return data as T;
}

export async function api<T = unknown>(ruta: string, method = 'GET', body?: unknown, base = servidor): Promise<T> {
  const headers: Record<string, string> = { Accept: 'application/json', ...encabezadosAuth() };
  if (body !== undefined) headers['Content-Type'] = 'application/json';
  const res = await solicitar(base + ruta, { method, headers, body: body === undefined ? undefined : JSON.stringify(body) });
  return leer<T>(res);
}

/** Sube una foto (campo "archivo") a un endpoint de comprobante o foto de avance. */
export async function subirArchivo(ruta: string, uri: string): Promise<void> {
  const form = new FormData();
  form.append('archivo', new File(uri));
  const res = await solicitar(servidor + ruta, { method: 'POST', headers: encabezadosAuth(), body: form });
  await leer(res);
}

/**
 * GET con respaldo local: si no hay señal devuelve la última copia guardada,
 * para que en obra se pueda consultar lo que ya se había descargado.
 */
export async function obtener<T>(ruta: string): Promise<{ datos: T; deCache: boolean; guardado?: string }> {
  try {
    const datos = await api<T>(ruta);
    guardarCopia(ruta, datos).catch(() => {});
    return { datos, deCache: false };
  } catch (e) {
    if (!(e instanceof SinConexion)) throw e;
    const copia = await AsyncStorage.getItem(PREFIJO_CACHE + ruta);
    if (!copia) throw e;
    const { datos, guardado } = JSON.parse(copia) as { datos: T; guardado: string };
    return { datos, deCache: true, guardado };
  }
}

function guardarCopia(ruta: string, datos: unknown): Promise<void> {
  return AsyncStorage.setItem(PREFIJO_CACHE + ruta, JSON.stringify({ datos, guardado: new Date().toISOString() }));
}

/**
 * Descarga de una vez todo lo que se consulta en obra (lista de obras y, de cada una,
 * avance y gastos) para tenerlo sin señal. Va obra por obra para no saturar el servidor.
 * Devuelve cuántas obras quedaron guardadas.
 */
export async function descargarParaSinSenal(alAvanzar?: (hechas: number, total: number) => void): Promise<number> {
  const obras = await api<{ id: number }[]>('/api/obras');
  await guardarCopia('/api/obras', obras);
  await api('/api/ingeniero/resumen')
    .then((r) => guardarCopia('/api/ingeniero/resumen', r))
    .catch(() => {});
  alAvanzar?.(0, obras.length);
  for (const [i, o] of obras.entries()) {
    const rutas = [`/api/cotizaciones/${o.id}/avance`, `/api/cotizaciones/${o.id}/control`];
    await Promise.all(rutas.map(async (r) => guardarCopia(r, await api(r))));
    alAvanzar?.(i + 1, obras.length);
  }
  return obras.length;
}
