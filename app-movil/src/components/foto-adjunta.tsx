import { Image } from 'expo-image';
import { Pressable, StyleSheet, Text, View } from 'react-native';

import { Boton, Icono, estilos as ui } from '@/components/ui';
import { elegirFoto } from '@/lib/imagen';
import { Colores, Espacio } from '@/lib/tema';

/** Foto opcional de un formulario (ticket, factura o avance). */
export function FotoAdjunta({ etiqueta, uri, onCambio }: { etiqueta: string; uri: string | null; onCambio: (uri: string | null) => void }) {
  async function agregar() {
    const nueva = await elegirFoto();
    if (nueva) onCambio(nueva);
  }

  return (
    <View style={{ gap: Espacio.xs }}>
      <Text style={ui.etiqueta}>{etiqueta}</Text>
      {uri ? (
        <View>
          <Image source={{ uri }} style={s.foto} contentFit="cover" />
          <Pressable style={s.quitar} onPress={() => onCambio(null)} accessibilityLabel="Quitar foto" hitSlop={8}>
            <Icono nombre="close" color={Colores.blanco} />
          </Pressable>
        </View>
      ) : (
        <Boton titulo="Agregar foto" icono="camera" tipo="secundario" onPress={agregar} />
      )}
    </View>
  );
}

const s = StyleSheet.create({
  foto: { width: '100%', aspectRatio: 4 / 3, borderRadius: 10, backgroundColor: Colores.borde },
  quitar: {
    position: 'absolute',
    top: Espacio.s,
    right: Espacio.s,
    backgroundColor: 'rgba(0,0,0,0.55)',
    borderRadius: 999,
    padding: 6,
  },
});
