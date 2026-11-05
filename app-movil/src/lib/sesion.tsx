import * as Device from 'expo-device';
import * as SecureStore from 'expo-secure-store';
import { createContext, useContext, useEffect, useState, type ReactNode } from 'react';

import {
  alExpirarSesion,
  api,
  ApiError,
  borrarCredenciales,
  cargarCredenciales,
  conLimite,
  guardarCredenciales,
  normalizarServidor,
} from './api';
import { iniciarSincronizacion, sincronizar, vaciar } from './cola';
import type { Usuario } from './tipos';

type Sesion = {
  cargando: boolean;
  usuario: Usuario | null;
  servidor: string;
  entrar: (servidor: string, email: string, password: string) => Promise<void>;
  salir: () => Promise<void>;
};

const Contexto = createContext<Sesion | null>(null);
const CLAVE_USUARIO = 'konstruktor.usuario';

export function ProveedorSesion({ children }: { children: ReactNode }) {
  const [cargando, setCargando] = useState(true);
  const [usuario, setUsuario] = useState<Usuario | null>(null);
  const [servidor, setServidor] = useState('');

  async function cerrar() {
    await borrarCredenciales();
    await vaciar();
    setUsuario(null);
  }

  useEffect(() => {
    alExpirarSesion(() => {
      cerrar();
    });
    (async () => {
      const c = await cargarCredenciales();
      setServidor(c.servidor);
      if (c.token) {
        try {
          // Con señal débil no se espera de más: se entra con lo guardado (si luego responde 401, la sesión se cierra igual).
          const r = await conLimite(api<{ user: Usuario }>('/api/auth/yo'), 6000);
          await guardarUsuario(r.user);
          setUsuario(r.user);
          sincronizar();
        } catch (e) {
          // Sin señal (o con el servidor caído) se entra con la sesión guardada; un 401 ya la cerró.
          if (!(e instanceof ApiError && e.status === 401)) setUsuario(await usuarioGuardado());
        }
      }
      setCargando(false);
    })();
    return iniciarSincronizacion();
  }, []);

  async function entrar(servidorIngresado: string, email: string, password: string) {
    const base = normalizarServidor(servidorIngresado);
    if (!base) throw new Error('Escribe la dirección del servidor');
    const dispositivo = [Device.manufacturer, Device.modelName].filter(Boolean).join(' ') || 'App móvil';
    const r = await api<{ token: string; user: Usuario }>('/api/auth/token', 'POST', { email, password, dispositivo }, base);
    await guardarCredenciales(base, r.token);
    await guardarUsuario(r.user);
    setServidor(base);
    setUsuario(r.user);
  }

  async function salir() {
    try {
      await api('/api/auth/token', 'DELETE');
    } catch {
      // Aunque no haya señal, la sesión local se cierra.
    }
    await cerrar();
  }

  return <Contexto.Provider value={{ cargando, usuario, servidor, entrar, salir }}>{children}</Contexto.Provider>;
}

export function useSesion(): Sesion {
  const s = useContext(Contexto);
  if (!s) throw new Error('useSesion fuera de ProveedorSesion');
  return s;
}

/** El Admin usa la app para consultar (gráficas y cifras), no para capturar: no ve los botones de registro. */
export function useSoloConsulta(): boolean {
  return useSesion().usuario?.rol_nombre === 'Admin';
}

// El usuario se guarda para poder abrir la app sin señal.
async function guardarUsuario(u: Usuario) {
  await SecureStore.setItemAsync(CLAVE_USUARIO, JSON.stringify(u));
}

async function usuarioGuardado(): Promise<Usuario | null> {
  const t = await SecureStore.getItemAsync(CLAVE_USUARIO);
  return t ? (JSON.parse(t) as Usuario) : null;
}
