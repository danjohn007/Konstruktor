import { Image } from 'expo-image';
import { useLocalSearchParams } from 'expo-router';
import { useState } from 'react';
import { StyleSheet, View } from 'react-native';

import { Vacio } from '@/components/ui';
import { encabezadosAuth, servidorActual } from '@/lib/api';
import { Colores } from '@/lib/tema';

/** Foto de un comprobante o de un avance; el archivo se pide con el token de la sesión. */
export default function Comprobante() {
  const { tipo, id } = useLocalSearchParams<{ tipo: 'movimientos' | 'avances'; id: string }>();
  const [error, setError] = useState(false);

  if (error) {
    return <Vacio icono="file-alert-outline" texto={'No se pudo mostrar el archivo.\nSi es un PDF, ábrelo desde el sistema web.'} />;
  }

  return (
    <View style={s.fondo}>
      <Image
        source={{ uri: `${servidorActual()}/archivos/${tipo}/${id}`, headers: encabezadosAuth() }}
        style={{ flex: 1 }}
        contentFit="contain"
        cachePolicy="memory"
        onError={() => setError(true)}
        accessibilityLabel="Comprobante"
      />
    </View>
  );
}

const s = StyleSheet.create({
  fondo: { flex: 1, backgroundColor: Colores.navy },
});
