import { Stack } from 'expo-router';
import * as SplashScreen from 'expo-splash-screen';
import { StatusBar } from 'expo-status-bar';
import { useEffect, useState } from 'react';
import { View } from 'react-native';

import { Intro } from '@/components/intro';
import { verIntro } from '@/lib/preferencias';
import { ProveedorSesion, useSesion } from '@/lib/sesion';
import { Colores } from '@/lib/tema';

// La presentación oculta el splash nativo en cuanto pinta su primer cuadro (o, sin ella, al cargar la sesión).
SplashScreen.preventAutoHideAsync();

function Navegacion() {
  const { cargando, usuario } = useSesion();
  // null mientras se lee la preferencia: el splash nativo sigue a la vista.
  const [intro, setIntro] = useState<boolean | null>(null);
  const [introTerminada, setIntroTerminada] = useState(false);

  useEffect(() => {
    verIntro().then(setIntro);
  }, []);

  useEffect(() => {
    if (intro === false && !cargando) SplashScreen.hideAsync().catch(() => {});
  }, [intro, cargando]);

  return (
    <View style={{ flex: 1, backgroundColor: Colores.navy }}>
      {/* Las pantallas se montan debajo mientras corre la presentación, para que al terminar ya estén listas. */}
      {!cargando && intro !== null && (
        <Stack screenOptions={{ headerShown: false, contentStyle: { backgroundColor: Colores.navy } }}>
          <Stack.Protected guard={!!usuario}>
            <Stack.Screen name="(app)" />
          </Stack.Protected>
          <Stack.Protected guard={!usuario}>
            <Stack.Screen name="login" />
          </Stack.Protected>
        </Stack>
      )}
      {/* Si termina antes de que cargue la sesión, se queda en pantalla hasta entonces. */}
      {intro === true && (!introTerminada || cargando) && <Intro onFin={() => setIntroTerminada(true)} />}
    </View>
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
