import { useState } from 'react';
import { Alert, FlatList, RefreshControl, StyleSheet, Text, View } from 'react-native';

import { Boton, Icono, Tarjeta, Vacio, estilos as ui } from '@/components/ui';
import { descartar, reintentar, sincronizar, usePendientes } from '@/lib/cola';
import { Colores, Espacio } from '@/lib/tema';

/** Lo capturado sin señal que aún no llega al servidor. */
export default function Pendientes() {
  const pendientes = usePendientes();
  const [enviando, setEnviando] = useState(false);

  async function enviar() {
    setEnviando(true);
    await sincronizar();
    setEnviando(false);
  }

  function confirmarDescartar(uuid: string, resumen: string) {
    Alert.alert('Descartar registro', `"${resumen}" no se enviará y se borrará del teléfono.`, [
      { text: 'Cancelar', style: 'cancel' },
      { text: 'Descartar', style: 'destructive', onPress: () => descartar(uuid) },
    ]);
  }

  return (
    <FlatList
      data={pendientes}
      keyExtractor={(p) => p.uuid}
      contentContainerStyle={{ padding: Espacio.l, gap: Espacio.m }}
      refreshControl={<RefreshControl refreshing={enviando} onRefresh={enviar} />}
      ListHeaderComponent={pendientes.length ? <Boton titulo="Enviar ahora" icono="cloud-upload-outline" onPress={enviar} cargando={enviando} /> : undefined}
      ListEmptyComponent={<Vacio icono="cloud-check-outline" texto="Todo está enviado." />}
      renderItem={({ item: p }) => (
        <Tarjeta>
          <View style={s.fila}>
            <Icono nombre={p.tipo === 'avance' ? 'progress-check' : 'receipt'} color={Colores.acento} />
            <Text style={[ui.texto, { flex: 1, fontWeight: '600' }]}>{p.resumen}</Text>
            {p.foto ? <Icono nombre="camera-outline" color={Colores.textoSuave} tam={18} /> : null}
          </View>
          <Text style={ui.textoSuave}>
            {p.obra} · capturado {new Date(p.creado).toLocaleString('es-MX', { dateStyle: 'short', timeStyle: 'short' })}
          </Text>
          {p.idServidor !== null && !p.error ? <Text style={ui.textoSuave}>Registro enviado; falta subir la foto.</Text> : null}
          {p.error ? (
            <>
              <Text style={{ color: Colores.rojo }}>El servidor lo rechazó: {p.error}</Text>
              <View style={s.fila}>
                <Boton titulo="Reintentar" tipo="secundario" onPress={() => reintentar(p.uuid)} style={{ flex: 1 }} />
                <Boton titulo="Descartar" tipo="peligro" onPress={() => confirmarDescartar(p.uuid, p.resumen)} style={{ flex: 1 }} />
              </View>
            </>
          ) : null}
        </Tarjeta>
      )}
    />
  );
}

const s = StyleSheet.create({
  fila: { flexDirection: 'row', alignItems: 'center', gap: Espacio.s },
});
