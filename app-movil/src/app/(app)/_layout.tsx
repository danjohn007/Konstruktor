import { Stack } from 'expo-router';

import { Colores } from '@/lib/tema';

export default function AppLayout() {
  return (
    <Stack
      screenOptions={{
        headerStyle: { backgroundColor: Colores.navy },
        headerTintColor: Colores.blanco,
        headerTitleStyle: { fontWeight: '600' },
        headerBackButtonDisplayMode: 'minimal',
        contentStyle: { backgroundColor: Colores.fondo },
      }}>
      <Stack.Screen name="index" options={{ title: 'Obras' }} />
      <Stack.Screen name="obra/[id]" options={{ title: 'Obra' }} />
      <Stack.Screen name="avance" options={{ title: 'Registrar avance', presentation: 'modal' }} />
      <Stack.Screen name="movimiento" options={{ title: 'Registrar gasto', presentation: 'modal' }} />
      <Stack.Screen name="comprobante" options={{ title: 'Comprobante' }} />
      <Stack.Screen name="pendientes" options={{ title: 'Pendientes de enviar' }} />
    </Stack>
  );
}
