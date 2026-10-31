import { router, Stack, useFocusEffect } from 'expo-router';
import { useCallback, useState } from 'react';
import { Alert, FlatList, Pressable, RefreshControl, StyleSheet, Text, View } from 'react-native';

import { AvisoSinSenal, BarraAvance, Icono, Insignia, Tarjeta, Vacio, estilos as ui } from '@/components/ui';
import { obtener } from '@/lib/api';
import { usePendientes } from '@/lib/cola';
import { fecha, money, pct } from '@/lib/formato';
import { useSesion } from '@/lib/sesion';
import { Colores, Espacio } from '@/lib/tema';
import type { Obra } from '@/lib/tipos';

export default function Obras() {
  const { usuario, salir } = useSesion();
  const pendientes = usePendientes();
  const [obras, setObras] = useState<Obra[] | null>(null);
  const [cache, setCache] = useState<string | undefined | null>(null);
  const [error, setError] = useState('');
  const [cargando, setCargando] = useState(false);

  const cargar = useCallback(async () => {
    setCargando(true);
    try {
      const r = await obtener<Obra[]>('/api/obras');
      setObras(r.datos);
      setCache(r.deCache ? r.guardado : null);
      setError('');
    } catch (e) {
      setError((e as Error).message);
    } finally {
      setCargando(false);
    }
  }, []);

  useFocusEffect(
    useCallback(() => {
      cargar();
    }, [cargar]),
  );

  function confirmarSalida() {
    const aviso = pendientes.length ? `\n\nHay ${pendientes.length} registro(s) sin enviar que se perderán.` : '';
    Alert.alert('Cerrar sesión', `¿Salir de la cuenta de ${usuario?.nombre}?${aviso}`, [
      { text: 'Cancelar', style: 'cancel' },
      { text: 'Salir', style: 'destructive', onPress: salir },
    ]);
  }

  return (
    <>
      <Stack.Screen
        options={{
          headerRight: () => (
            <View style={s.acciones}>
              {pendientes.length > 0 && (
                <Pressable onPress={() => router.push('/pendientes')} hitSlop={8} accessibilityLabel={`${pendientes.length} pendientes de enviar`} style={s.pendientes}>
                  <Icono nombre="cloud-upload-outline" color={Colores.blanco} />
                  <Text style={s.pendientesTexto}>{pendientes.length}</Text>
                </Pressable>
              )}
              <Pressable onPress={confirmarSalida} hitSlop={8} accessibilityLabel="Cerrar sesión">
                <Icono nombre="logout" color={Colores.blanco} />
              </Pressable>
            </View>
          ),
        }}
      />
      <FlatList
        data={obras ?? []}
        keyExtractor={(o) => String(o.id)}
        contentContainerStyle={{ padding: Espacio.l, gap: Espacio.m }}
        refreshControl={<RefreshControl refreshing={cargando} onRefresh={cargar} />}
        ListHeaderComponent={
          <View style={{ gap: Espacio.m }}>
            <Text style={ui.textoSuave}>Hola, {usuario?.nombre}</Text>
            {cache !== null && <AvisoSinSenal guardado={cache} />}
            {error ? <Text style={{ color: Colores.rojo }}>{error}</Text> : null}
          </View>
        }
        ListEmptyComponent={
          obras ? <Vacio icono="office-building-outline" texto={'No hay obras en ejecución.\nEn el sistema web, marca una cotización como "aceptada".'} /> : undefined
        }
        renderItem={({ item: o }) => {
          const atrasado = o.pct_ejecutado + 0.0001 < o.pct_programado;
          return (
            <Pressable onPress={() => router.push({ pathname: '/obra/[id]', params: { id: String(o.id), titulo: o.folio } })}>
              {({ pressed }) => (
                <Tarjeta style={pressed && { opacity: 0.7 }}>
                  <View style={s.fila}>
                    <Text style={ui.textoSuave}>{o.folio}</Text>
                    {atrasado ? <Insignia texto="Atrasada" color={Colores.ambar} fondo={Colores.ambarSuave} /> : <Insignia texto="Al corriente" color={Colores.verde} fondo={Colores.verdeSuave} />}
                  </View>
                  <Text style={ui.titulo}>{o.obra}</Text>
                  {o.cliente || o.municipio ? <Text style={ui.textoSuave}>{[o.cliente, o.municipio].filter(Boolean).join(' · ')}</Text> : null}
                  <BarraAvance real={o.pct_ejecutado} programado={o.pct_programado} />
                  <View style={s.fila}>
                    <Text style={ui.textoSuave}>
                      Avance {pct(o.pct_ejecutado)} · programado {pct(o.pct_programado)}
                    </Text>
                    {o.termino ? <Text style={ui.textoSuave}>Termina {fecha(o.termino)}</Text> : null}
                  </View>
                  <View style={s.fila}>
                    <Text style={ui.texto}>Gastado {money(o.gastado)}</Text>
                    <Text style={ui.textoSuave}>de {money(o.contrato)}</Text>
                  </View>
                </Tarjeta>
              )}
            </Pressable>
          );
        }}
      />
    </>
  );
}

const s = StyleSheet.create({
  acciones: { flexDirection: 'row', alignItems: 'center', gap: Espacio.l },
  pendientes: { flexDirection: 'row', alignItems: 'center', gap: 4 },
  pendientesTexto: { color: Colores.blanco, fontWeight: '700' },
  fila: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', gap: Espacio.s },
});
