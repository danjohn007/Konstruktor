import { Stack } from 'expo-router';
import * as SplashScreen from 'expo-splash-screen';
import { StatusBar } from 'expo-status-bar';
import { useEffect } from 'react';

import { ProveedorSesion, useSesion } from '@/lib/sesion';

SplashScreen.preventAutoHideAsync();

function Navegacion() {
  const { cargando, usuario } = useSesion();

  useEffect(() => {
    if (!cargando) SplashScreen.hideAsync();
  }, [cargando]);

  if (cargando) return null;

  return (
    <Stack screenOptions={{ headerShown: false }}>
      <Stack.Protected guard={!!usuario}>
        <Stack.Screen name="(app)" />
      </Stack.Protected>
      <Stack.Protected guard={!usuario}>
        <Stack.Screen name="login" />
      </Stack.Protected>
    </Stack>
  );
}

export default function RootLayout() {
  return (
    <ProveedorSesion>
      <StatusBar style="light" />
      <Navegacion />
    </ProveedorSesion>
  );
}
