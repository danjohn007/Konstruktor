import { router, Stack, useFocusEffect, useLocalSearchParams } from 'expo-router';
import { useCallback, useState } from 'react';
import { Pressable, RefreshControl, ScrollView, StyleSheet, Text, View } from 'react-native';

import { AvisoSinSenal, BarraAvance, Boton, Dato, Icono, Insignia, Opciones, Tarjeta, Vacio, estilos as ui } from '@/components/ui';
import { obtener } from '@/lib/api';
import { usePendientes, type Pendiente } from '@/lib/cola';
import { fecha, hoyISO, money, numero, pct } from '@/lib/formato';
import { Colores, Espacio } from '@/lib/tema';
import { NOMBRE_CATEGORIA, type Avance, type Control } from '@/lib/tipos';

type Nav = { obra: string; titulo: string };
type Seccion = 'resumen' | 'avance' | 'gastos';
const SECCIONES = [
  { valor: 'resumen', texto: 'Resumen' },
  { valor: 'avance', texto: 'Avance' },
  { valor: 'gastos', texto: 'Gastos' },
] as const;

export default function ObraDetalle() {
  const { id, titulo } = useLocalSearchParams<{ id: string; titulo?: string }>();
  const [seccion, setSeccion] = useState<Seccion>('resumen');
  const [avance, setAvance] = useState<Avance | null>(null);
  const [control, setControl] = useState<Control | null>(null);
  const [cache, setCache] = useState<string | undefined | null>(null);
  const [error, setError] = useState('');
  const [cargando, setCargando] = useState(false);
  const pendientes = usePendientes().filter((p) => p.cotizacionId === Number(id));
  const nav: Nav = { obra: id, titulo: titulo ?? '' };

  const cargar = useCallback(async () => {
    setCargando(true);
    try {
      const [a, c] = await Promise.all([obtener<Avance>(`/api/cotizaciones/${id}/avance`), obtener<Control>(`/api/cotizaciones/${id}/control`)]);
      setAvance(a.datos);
      setControl(c.datos);
      setCache(a.deCache || c.deCache ? (a.guardado ?? c.guardado) : null);
      setError('');
    } catch (e) {
      setError((e as Error).message);
    } finally {
      setCargando(false);
    }
  }, [id]);

  // Al volver de un formulario se recarga (y de paso se ve lo que ya se envió).
  useFocusEffect(
    useCallback(() => {
      cargar();
    }, [cargar]),
  );

  return (
    <>
      <Stack.Screen options={{ title: titulo ?? 'Obra' }} />
      <ScrollView contentContainerStyle={s.contenido} refreshControl={<RefreshControl refreshing={cargando} onRefresh={cargar} />}>
        <Opciones opciones={SECCIONES} valor={seccion} onCambio={setSeccion} />
        {cache !== null && <AvisoSinSenal guardado={cache} />}
        {error ? <Text style={{ color: Colores.rojo }}>{error}</Text> : null}
        {pendientes.length > 0 && <AvisoPendientes pendientes={pendientes} />}

        {avance && control && seccion === 'resumen' && <Resumen avance={avance} control={control} nav={nav} />}
        {avance && seccion === 'avance' && <ListaAvance avance={avance} nav={nav} />}
        {control && seccion === 'gastos' && <ListaGastos control={control} nav={nav} />}
      </ScrollView>
    </>
  );
}

function AvisoPendientes({ pendientes }: { pendientes: Pendiente[] }) {
  const conError = pendientes.some((p) => p.error);
  return (
    <Pressable onPress={() => router.push('/pendientes')}>
      <View style={[s.aviso, conError && { backgroundColor: Colores.rojoSuave }]}>
        <Icono nombre={conError ? 'alert-circle-outline' : 'cloud-upload-outline'} color={conError ? Colores.rojo : Colores.acento} />
        <Text style={[ui.texto, { flex: 1 }]}>
          {pendientes.length} registro(s) de esta obra {conError ? 'necesitan revisión' : 'se enviarán al recuperar la señal'}.
        </Text>
        <Icono nombre="chevron-right" color={Colores.textoSuave} />
      </View>
    </Pressable>
  );
}

function Resumen({ avance, control, nav }: { avance: Avance; control: Control; nav: Nav }) {
  const hoy = hoyISO();
  const semana = avance.semanas.find((w) => w.inicio <= hoy && hoy <= w.fin);
  const atrasado = avance.pct_ejecutado + 0.0001 < avance.pct_programado;
  return (
    <View style={{ gap: Espacio.m }}>
      <Tarjeta>
        <View style={s.fila}>
          <Text style={ui.titulo}>Avance físico</Text>
          {atrasado ? <Insignia texto="Atrasada" color={Colores.ambar} fondo={Colores.ambarSuave} /> : <Insignia texto="Al corriente" color={Colores.verde} fondo={Colores.verdeSuave} />}
        </View>
        <BarraAvance real={avance.pct_ejecutado} programado={avance.pct_programado} alto={14} />
        <Text style={ui.textoSuave}>
          Ejecutado {pct(avance.pct_ejecutado, 1)} ({money(avance.ejecutado)}) · Programado a hoy {pct(avance.pct_programado, 1)}
        </Text>
      </Tarjeta>

      {semana && (
        <Tarjeta>
          <Text style={ui.titulo}>
            Esta semana ({fecha(semana.inicio).slice(0, 5)} – {fecha(semana.fin).slice(0, 5)})
          </Text>
          <View style={s.datos}>
            <Dato etiqueta="Programado" valor={money(semana.programado)} />
            <Dato etiqueta="Ejecutado" valor={money(semana.ejecutado)} />
            <Dato etiqueta="Gastado" valor={money(semana.gastado)} />
          </View>
        </Tarjeta>
      )}

      <Tarjeta>
        <Text style={ui.titulo}>Dinero de la obra</Text>
        <View style={s.datos}>
          <Dato etiqueta="Contrato (sin IVA)" valor={money(control.contrato)} />
          <Dato etiqueta="Cobrado" valor={money(control.cobrado)} />
          <Dato etiqueta="Gastado" valor={money(control.gastado)} />
          <Dato etiqueta="Saldo de caja" valor={money(control.saldo)} color={control.saldo < 0 ? Colores.rojo : undefined} />
        </View>
        <Text style={ui.textoSuave}>Costo presupuestado {money(control.costo_presupuestado)}</Text>
      </Tarjeta>

      <View style={s.botones}>
        <Boton titulo="Avance" icono="progress-check" onPress={() => router.push({ pathname: '/avance', params: { ...nav } })} style={{ flex: 1 }} />
        <Boton titulo="Gasto" icono="cash-minus" tipo="secundario" onPress={() => router.push({ pathname: '/movimiento', params: { ...nav, tipo: 'egreso' } })} style={{ flex: 1 }} />
      </View>
    </View>
  );
}

function ListaAvance({ avance, nav }: { avance: Avance; nav: Nav }) {
  return (
    <View style={{ gap: Espacio.m }}>
      <Boton titulo="Registrar avance" icono="plus" onPress={() => router.push({ pathname: '/avance', params: { ...nav } })} />
      {avance.conceptos.length === 0 && <Vacio icono="format-list-checks" texto="La cotización no tiene conceptos." />}
      {avance.conceptos.map((c) => (
        <Pressable key={c.id} onPress={() => router.push({ pathname: '/avance', params: { ...nav, concepto: String(c.id) } })}>
          {({ pressed }) => (
            <Tarjeta style={pressed && { opacity: 0.7 }}>
              <View style={s.fila}>
                <Text style={[ui.texto, { fontWeight: '700' }]}>{c.clave}</Text>
                {c.excedente ? (
                  <Insignia texto="Excedente" color={Colores.rojo} fondo={Colores.rojoSuave} />
                ) : c.pct >= 1 ? (
                  <Insignia texto="Terminado" color={Colores.verde} fondo={Colores.verdeSuave} />
                ) : c.atrasado ? (
                  <Insignia texto="Atrasado" color={Colores.ambar} fondo={Colores.ambarSuave} />
                ) : null}
              </View>
              <Text style={ui.texto} numberOfLines={2}>
                {c.descripcion}
              </Text>
              <BarraAvance real={c.pct} programado={c.pct_programado} />
              <Text style={ui.textoSuave}>
                {numero(c.ejecutado)} de {numero(c.cantidad)} {c.unidad} ({pct(c.pct)}) · debería ir en {numero(c.programado)}
              </Text>
              <Text style={ui.textoSuave}>
                Programa {fecha(c.inicio).slice(0, 5)} – {fecha(c.termino).slice(0, 5)}
                {c.ultimo_avance ? ` · último registro ${fecha(c.ultimo_avance)}` : ''}
              </Text>
            </Tarjeta>
          )}
        </Pressable>
      ))}

      {avance.registros.length > 0 && <Text style={[ui.titulo, { marginTop: Espacio.s }]}>Últimos registros</Text>}
      {avance.registros.slice(0, 20).map((r) => (
        <Tarjeta key={r.id} style={{ gap: 2 }}>
          <View style={s.fila}>
            <Text style={[ui.texto, { fontWeight: '600' }]}>
              {r.concepto_clave} · {numero(r.cantidad)} {r.unidad}
            </Text>
            <Text style={ui.textoSuave}>{fecha(r.fecha)}</Text>
          </View>
          {r.notas ? <Text style={ui.textoSuave}>{r.notas}</Text> : null}
          <View style={s.fila}>
            <Text style={ui.textoSuave}>{r.usuario ?? ''}</Text>
            {r.foto ? (
              <Pressable onPress={() => router.push({ pathname: '/comprobante', params: { tipo: 'avances', id: String(r.id) } })} hitSlop={8}>
                <Icono nombre="image-outline" color={Colores.acento} />
              </Pressable>
            ) : null}
          </View>
        </Tarjeta>
      ))}
    </View>
  );
}

function ListaGastos({ control, nav }: { control: Control; nav: Nav }) {
  return (
    <View style={{ gap: Espacio.m }}>
      <View style={s.botones}>
        <Boton titulo="Gasto" icono="minus-circle-outline" onPress={() => router.push({ pathname: '/movimiento', params: { ...nav, tipo: 'egreso' } })} style={{ flex: 1 }} />
        <Boton titulo="Ingreso" icono="plus-circle-outline" tipo="secundario" onPress={() => router.push({ pathname: '/movimiento', params: { ...nav, tipo: 'ingreso' } })} style={{ flex: 1 }} />
      </View>
      {control.movimientos.length === 0 && <Vacio icono="cash-minus" texto="Sin movimientos registrados." />}
      {control.movimientos.map((m) => {
        const ingreso = m.tipo === 'ingreso';
        return (
          <Tarjeta key={m.id} style={{ gap: 2 }}>
            <View style={s.fila}>
              <Text style={[ui.texto, { flex: 1, fontWeight: '600' }]} numberOfLines={2}>
                {m.descripcion}
              </Text>
              <Text style={[ui.texto, { fontWeight: '700', color: ingreso ? Colores.verde : Colores.texto }]}>
                {ingreso ? '+' : '−'}
                {money(m.monto)}
              </Text>
            </View>
            <Text style={ui.textoSuave}>
              {fecha(m.fecha)} · {NOMBRE_CATEGORIA[m.categoria] ?? m.categoria}
              {m.concepto_clave ? ` · ${m.concepto_clave}` : ''}
              {m.forma_pago ? ` · ${m.forma_pago}` : ''}
            </Text>
            <View style={s.fila}>
              <Text style={ui.textoSuave}>{m.proveedor ?? ''}</Text>
              {m.comprobante ? (
                <Pressable onPress={() => router.push({ pathname: '/comprobante', params: { tipo: 'movimientos', id: String(m.id) } })} hitSlop={8} accessibilityLabel="Ver comprobante">
                  <Icono nombre="paperclip" color={Colores.acento} />
                </Pressable>
              ) : null}
            </View>
          </Tarjeta>
        );
      })}
    </View>
  );
}

const s = StyleSheet.create({
  contenido: { padding: Espacio.l, gap: Espacio.m },
  fila: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', gap: Espacio.s },
  datos: { flexDirection: 'row', flexWrap: 'wrap', gap: Espacio.m },
  botones: { flexDirection: 'row', gap: Espacio.m },
  aviso: { flexDirection: 'row', alignItems: 'center', gap: Espacio.s, backgroundColor: Colores.acentoSuave, padding: Espacio.m, borderRadius: 10 },
});
