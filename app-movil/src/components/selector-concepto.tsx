import { useMemo, useState } from 'react';
import { FlatList, Modal, Pressable, StyleSheet, Text, TextInput, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { Icono, estilos as ui } from '@/components/ui';
import { Colores, Espacio } from '@/lib/tema';

export type OpcionConcepto = { id: number; clave: string; descripcion: string; detalle?: string };

/** Campo que abre una lista con buscador para elegir un concepto del catálogo. */
export function SelectorConcepto({
  etiqueta,
  conceptos,
  valor,
  onCambio,
  textoVacio = 'Elegir concepto',
  permitirNinguno,
}: {
  etiqueta: string;
  conceptos: OpcionConcepto[];
  valor: number | null;
  onCambio: (id: number | null) => void;
  textoVacio?: string;
  permitirNinguno?: string;
}) {
  const [abierto, setAbierto] = useState(false);
  const [q, setQ] = useState('');
  const elegido = conceptos.find((c) => c.id === valor);
  const filtrados = useMemo(() => {
    const t = q.trim().toLowerCase();
    return t ? conceptos.filter((c) => `${c.clave} ${c.descripcion}`.toLowerCase().includes(t)) : conceptos;
  }, [q, conceptos]);

  function elegir(id: number | null) {
    onCambio(id);
    setAbierto(false);
    setQ('');
  }

  return (
    <View style={{ gap: Espacio.xs }}>
      <Text style={ui.etiqueta}>{etiqueta}</Text>
      <Pressable style={[ui.input, s.campo]} onPress={() => setAbierto(true)} accessibilityRole="button">
        <Text style={[ui.texto, { flex: 1 }, !elegido && { color: Colores.textoSuave }]} numberOfLines={2}>
          {elegido ? `${elegido.clave} · ${elegido.descripcion}` : permitirNinguno ?? textoVacio}
        </Text>
        <Icono nombre="chevron-down" color={Colores.textoSuave} />
      </Pressable>

      <Modal visible={abierto} animationType="slide" presentationStyle="pageSheet" onRequestClose={() => setAbierto(false)}>
        <SafeAreaView style={s.modal} edges={['top', 'bottom']}>
          <View style={s.cabecera}>
            <Text style={ui.titulo}>{etiqueta}</Text>
            <Pressable onPress={() => setAbierto(false)} hitSlop={12} accessibilityLabel="Cerrar">
              <Icono nombre="close" tam={24} />
            </Pressable>
          </View>
          <TextInput style={[ui.input, { marginHorizontal: Espacio.l }]} placeholder="Buscar por clave o descripción" value={q} onChangeText={setQ} autoCorrect={false} />
          <FlatList
            data={filtrados}
            keyExtractor={(c) => String(c.id)}
            keyboardShouldPersistTaps="handled"
            contentContainerStyle={{ padding: Espacio.l, gap: Espacio.s }}
            ListHeaderComponent={
              permitirNinguno ? (
                <Pressable style={[s.fila, valor === null && s.filaActiva]} onPress={() => elegir(null)}>
                  <Text style={ui.texto}>{permitirNinguno}</Text>
                </Pressable>
              ) : undefined
            }
            renderItem={({ item }) => (
              <Pressable style={[s.fila, item.id === valor && s.filaActiva]} onPress={() => elegir(item.id)}>
                <Text style={[ui.texto, { fontWeight: '600' }]}>
                  {item.clave} · {item.descripcion}
                </Text>
                {item.detalle ? <Text style={ui.textoSuave}>{item.detalle}</Text> : null}
              </Pressable>
            )}
          />
        </SafeAreaView>
      </Modal>
    </View>
  );
}

const s = StyleSheet.create({
  campo: { flexDirection: 'row', alignItems: 'center', gap: Espacio.s },
  modal: { flex: 1, backgroundColor: Colores.fondo, gap: Espacio.m },
  cabecera: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', paddingHorizontal: Espacio.l, paddingTop: Espacio.l },
  fila: { backgroundColor: Colores.blanco, padding: Espacio.m, borderRadius: 10, gap: 2, borderWidth: 1, borderColor: Colores.borde },
  filaActiva: { borderColor: Colores.acento, backgroundColor: Colores.acentoSuave },
});
