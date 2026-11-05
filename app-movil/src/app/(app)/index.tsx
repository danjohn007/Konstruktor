import { router, useFocusEffect } from 'expo-router';
import { useCallback, useMemo, useState } from 'react';
import { FlatList, Pressable, RefreshControl, StyleSheet, Text, TextInput, View } from 'react-native';
import Animated, { FadeInDown } from 'react-native-reanimated';
import { useSafeAreaInsets } from 'react-native-safe-area-context';

import { FondoMarca } from '@/components/fondo-marca';
import {
  Anillo,
  AvisoSinSenal,
  Avatar,
  BarraAvance,
  Icono,
  InsigniaEstado,
  Opciones,
  Seccion,
  Tarjeta,
  TarjetaEsqueleto,
  Vacio,
  estilos as ui,
} from '@/components/ui';
import { obtener } from '@/lib/api';
import { usePendientes } from '@/lib/cola';
import { diasHasta, fecha, moneyCorto, pct, saludo } from '@/lib/formato';
import { useSesion, useSoloConsulta } from '@/lib/sesion';
import { Colores, Espacio, Sombra } from '@/lib/tema';
import type { MiSemana, Obra } from '@/lib/tipos';

type Filtro = 'todas' | 'atrasadas' | 'corriente';
const atrasada = (o: Obra) => o.pct_ejecutado + 0.0001 < o.pct_programado;

export default function Inicio() {
  const { usuario } = useSesion();
  const soloConsulta = useSoloConsulta();
  const insets = useSafeAreaInsets();
  const pendientes = usePendientes();
  const [obras, setObras] = useState<Obra[] | null>(null);
  const [semana, setSemana] = useState<MiSemana['semana'] | null>(null);
  const [cache, setCache] = useState<string | undefined | null>(null);
  const [error, setError] = useState('');
  const [cargando, setCargando] = useState(false);
  const [busqueda, setBusqueda] = useState('');
  const [filtro, setFiltro] = useState<Filtro>('todas');

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
    // Lo que registró el usuario esta semana (el Admin no captura); si falla no estorba.
    if (soloConsulta) return;
    obtener<MiSemana>('/api/ingeniero/resumen')
      .then((r) => setSemana(r.datos.semana))
      .catch(() => {});
  }, [soloConsulta]);

  useFocusEffect(
    useCallback(() => {
      cargar();
    }, [cargar]),
  );

  const lista = useMemo(() => obras ?? [], [obras]);
  const nAtrasadas = lista.filter(atrasada).length;
  const promedio = lista.length ? lista.reduce((t, o) => t + o.pct_ejecutado, 0) / lista.length : 0;
  // Con pocas obras el buscador y los filtros solo estorban.
  const conFiltros = lista.length >= 6;
  const visibles = useMemo(() => {
    const t = busqueda.trim().toLowerCase();
    const filtradas = !conFiltros
      ? lista
      : lista.filter((o) => {
          if (filtro === 'atrasadas' && !atrasada(o)) return false;
          if (filtro === 'corriente' && atrasada(o)) return false;
          return !t || [o.obra, o.folio, o.cliente, o.municipio].some((x) => x?.toLowerCase().includes(t));
        });
    // El Admin ve primero las obras más atrasadas respecto a su programa.
    return soloConsulta ? [...filtradas].sort((a, b) => a.pct_ejecutado - a.pct_programado - (b.pct_ejecutado - b.pct_programado)) : filtradas;
  }, [lista, busqueda, filtro, conFiltros, soloConsulta]);

  const conError = pendientes.some((p) => p.error);
  const nombre = usuario?.nombre?.split(' ')[0] ?? '';
  const hoy = new Date().toLocaleDateString('es-MX', { weekday: 'long', day: 'numeric', month: 'long' });

  const encabezado = (
    <View style={{ gap: Espacio.l }}>
      <View style={[s.hero, { paddingTop: insets.top + Espacio.l }]}>
        <FondoMarca />
        <View style={s.heroFila}>
          <View style={{ flex: 1 }}>
            <Text style={s.heroFecha}>{hoy.charAt(0).toUpperCase() + hoy.slice(1)}</Text>
            <Text style={s.heroSaludo} numberOfLines={1}>
              {saludo()}, {nombre}
            </Text>
          </View>
          {pendientes.length > 0 && (
            <Pressable
              onPress={() => router.push('/pendientes')}
              style={[s.pildora, conError && { backgroundColor: Colores.rojoSuave }]}
              accessibilityLabel={`${pendientes.length} pendientes de enviar`}>
              <Icono nombre={conError ? 'alert-circle-outline' : 'cloud-upload-outline'} color={conError ? Colores.rojo : Colores.navy} tam={16} />
              <Text style={[s.pildoraTexto, conError && { color: Colores.rojo }]}>{pendientes.length}</Text>
            </Pressable>
          )}
          <Pressable onPress={() => router.push('/ajustes')} hitSlop={8} accessibilityLabel="Mi cuenta y ajustes">
            <Avatar nombre={usuario?.nombre} tam={44} />
          </Pressable>
        </View>

        <View style={s.heroKpis}>
          <KpiHero icono="office-building-outline" valor={obras ? String(lista.length) : '—'} etiqueta="Obras activas" />
          <KpiHero icono="chart-donut" valor={obras ? pct(promedio) : '—'} etiqueta="Avance promedio" />
          <KpiHero icono="alert-outline" valor={obras ? String(nAtrasadas) : '—'} etiqueta="Atrasadas" alerta={nAtrasadas > 0} />
        </View>
      </View>

      <View style={s.cuerpo}>
        {cache !== null && <AvisoSinSenal guardado={cache} />}
        {error ? <Text style={{ color: Colores.rojo }}>{error}</Text> : null}

        {soloConsulta ? lista.length > 0 && <DineroCartera obras={lista} /> : semana && <TuSemana semana={semana} />}

        <Seccion titulo={`${soloConsulta ? 'Obras' : 'Mis obras'}${obras ? ` · ${lista.length}` : ''}`} />
        {conFiltros && (
          <>
            <View style={s.buscador}>
              <Icono nombre="magnify" color={Colores.textoTenue} />
              <TextInput
                value={busqueda}
                onChangeText={setBusqueda}
                placeholder="Buscar obra, folio o cliente"
                placeholderTextColor={Colores.textoTenue}
                style={s.buscadorInput}
                autoCorrect={false}
                returnKeyType="search"
              />
              {busqueda ? (
                <Pressable onPress={() => setBusqueda('')} hitSlop={8} accessibilityLabel="Limpiar búsqueda">
                  <Icono nombre="close-circle" color={Colores.textoTenue} tam={18} />
                </Pressable>
              ) : null}
            </View>
            <Opciones
              opciones={[
                { valor: 'todas', texto: 'Todas', cuenta: lista.length },
                { valor: 'atrasadas', texto: 'Atrasadas', cuenta: nAtrasadas },
                { valor: 'corriente', texto: 'Al corriente', cuenta: lista.length - nAtrasadas },
              ]}
              valor={filtro}
              onCambio={setFiltro}
            />
          </>
        )}
      </View>
    </View>
  );

  return (
    <FlatList
      style={{ backgroundColor: Colores.fondo }}
      data={visibles}
      keyExtractor={(o) => String(o.id)}
      contentContainerStyle={{ paddingBottom: insets.bottom + Espacio.xl, gap: Espacio.m }}
      refreshControl={<RefreshControl refreshing={cargando && obras !== null} onRefresh={cargar} progressViewOffset={insets.top} />}
      ListHeaderComponent={encabezado}
      ListEmptyComponent={
        obras === null ? (
          <View style={[s.cuerpo, { gap: Espacio.m }]}>
            <TarjetaEsqueleto />
            <TarjetaEsqueleto />
            <TarjetaEsqueleto />
          </View>
        ) : lista.length === 0 ? (
          <Vacio icono="office-building-outline" texto={'No hay obras en ejecución.\nEn el sistema web, marca una cotización como "aceptada".'} />
        ) : (
          <Vacio icono="magnify" texto="Ninguna obra coincide con la búsqueda." />
        )
      }
      renderItem={({ item, index }) => (
        <Animated.View entering={FadeInDown.duration(420).delay(Math.min(index, 6) * 60)} style={s.cuerpo}>
          {soloConsulta ? <FilaObra obra={item} /> : <TarjetaObra obra={item} />}
        </Animated.View>
      )}
    />
  );
}

function KpiHero({ icono, valor, etiqueta, alerta }: { icono: Parameters<typeof Icono>[0]['nombre']; valor: string; etiqueta: string; alerta?: boolean }) {
  return (
    <View style={s.kpiHero}>
      <Icono nombre={icono} color={alerta ? Colores.dorado : Colores.acentoClaro} tam={18} />
      <Text style={s.kpiHeroValor}>{valor}</Text>
      <Text style={s.kpiHeroEtiqueta} numberOfLines={1}>
        {etiqueta}
      </Text>
    </View>
  );
}

function TuSemana({ semana }: { semana: MiSemana['semana'] }) {
  const sinComprobante = semana.sin_comprobante > 0;
  return (
    <Tarjeta style={{ gap: Espacio.m }}>
      <View style={s.fila}>
        <Text style={ui.titulo}>Tu semana</Text>
        <Text style={ui.textoSuave}>desde el lunes</Text>
      </View>
      <View style={s.semana}>
        <DatoSemana icono="progress-check" valor={String(semana.avances)} etiqueta="Avances" color={Colores.verde} />
        <DatoSemana icono="receipt" valor={String(semana.gastos)} etiqueta="Gastos" color={Colores.acento} />
        <DatoSemana icono="cash" valor={moneyCorto(semana.gastado)} etiqueta="Gastado" color={Colores.violeta} />
      </View>
      {sinComprobante ? (
        <View style={s.alerta}>
          <Icono nombre="camera-off-outline" color={Colores.ambar} tam={18} />
          <Text style={s.alertaTexto}>
            {semana.sin_comprobante} gasto{semana.sin_comprobante === 1 ? '' : 's'} tuyo{semana.sin_comprobante === 1 ? '' : 's'} sin foto del ticket. Agrégala en la pestaña Gastos de la obra.
          </Text>
        </View>
      ) : null}
    </Tarjeta>
  );
}

/** Para el Admin: contratado, cobrado y gastado de todas las obras, en barras comparables. */
function DineroCartera({ obras }: { obras: Obra[] }) {
  const suma = (k: 'contrato' | 'cobrado' | 'gastado') => obras.reduce((t, o) => t + (Number(o[k]) || 0), 0);
  const contrato = suma('contrato');
  const barras = [
    { etiqueta: 'Contratado (sin IVA)', valor: contrato, color: Colores.navy },
    { etiqueta: 'Cobrado', valor: suma('cobrado'), color: Colores.verde },
    { etiqueta: 'Gastado', valor: suma('gastado'), color: Colores.violeta },
  ];
  const tope = Math.max(...barras.map((b) => b.valor)) || 1;

  return (
    <Tarjeta style={{ gap: Espacio.m }}>
      <Text style={ui.titulo}>Dinero de la cartera</Text>
      {barras.map((b) => (
        <View key={b.etiqueta} style={{ gap: 6 }}>
          <View style={s.fila}>
            <Text style={ui.texto}>{b.etiqueta}</Text>
            <Text style={[ui.texto, { fontWeight: '800', color: Colores.navy }]}>
              {moneyCorto(b.valor)}
              {b.valor !== contrato && contrato > 0 ? <Text style={ui.textoSuave}>{`  ${pct(b.valor / contrato)}`}</Text> : null}
            </Text>
          </View>
          <View style={s.barraFondo}>
            <View style={[s.barra, { width: `${(b.valor / tope) * 100}%`, backgroundColor: b.color }]} />
          </View>
        </View>
      ))}
    </Tarjeta>
  );
}

function DatoSemana({ icono, valor, etiqueta, color }: { icono: Parameters<typeof Icono>[0]['nombre']; valor: string; etiqueta: string; color: string }) {
  return (
    <View style={s.datoSemana}>
      <Icono nombre={icono} color={color} tam={18} />
      <Text style={s.datoSemanaValor} numberOfLines={1} adjustsFontSizeToFit>
        {valor}
      </Text>
      <Text style={ui.textoSuave}>{etiqueta}</Text>
    </View>
  );
}

const abrirObra = (o: Obra) => router.push({ pathname: '/obra/[id]', params: { id: String(o.id), titulo: o.folio, nombre: o.obra } });

/** Para el Admin: una fila compacta por obra con su avance real y la marca de dónde debería ir. */
function FilaObra({ obra: o }: { obra: Obra }) {
  const atras = atrasada(o);
  return (
    <Pressable onPress={() => abrirObra(o)} style={({ pressed }) => pressed && { transform: [{ scale: 0.985 }], opacity: 0.92 }}>
      <Tarjeta style={{ gap: Espacio.s, padding: Espacio.m }}>
        <View style={s.fila}>
          <View style={{ flex: 1, gap: 1 }}>
            <Text style={s.folio}>{o.folio}</Text>
            <Text style={s.filaNombre} numberOfLines={1}>
              {o.obra}
            </Text>
          </View>
          <Text style={[s.filaPct, atras && { color: Colores.ambar }]}>{pct(o.pct_ejecutado)}</Text>
        </View>
        <BarraAvance real={o.pct_ejecutado} programado={o.pct_programado} />
        {atras ? <Text style={s.filaAtraso}>Debería ir en {pct(o.pct_programado)}</Text> : null}
      </Tarjeta>
    </Pressable>
  );
}

function TarjetaObra({ obra: o }: { obra: Obra }) {
  const dias = diasHasta(o.termino);
  const uso = o.contrato > 0 ? o.gastado / o.contrato : 0;
  return (
    <Pressable onPress={() => abrirObra(o)} style={({ pressed }) => pressed && { transform: [{ scale: 0.985 }], opacity: 0.92 }}>
      <Tarjeta style={{ gap: Espacio.m }}>
        <View style={s.obraFila}>
          <Anillo valor={o.pct_ejecutado} programado={o.pct_programado} tam={60} grosor={6}>
            <Text style={s.anilloTexto}>{pct(o.pct_ejecutado)}</Text>
          </Anillo>
          <View style={{ flex: 1, gap: 3 }}>
            <View style={s.fila}>
              <Text style={s.folio}>{o.folio}</Text>
              <InsigniaEstado real={o.pct_ejecutado} programado={o.pct_programado} />
            </View>
            <Text style={s.obraNombre} numberOfLines={2}>
              {o.obra}
            </Text>
            {o.cliente || o.municipio ? (
              <View style={s.lugar}>
                <Icono nombre="map-marker-outline" color={Colores.textoTenue} tam={14} />
                <Text style={[ui.textoSuave, { flex: 1 }]} numberOfLines={1}>
                  {[o.cliente, o.municipio].filter(Boolean).join(' · ')}
                </Text>
              </View>
            ) : null}
          </View>
        </View>

        <View style={s.separador} />

        <View style={s.obraDatos}>
          <MiniDato etiqueta="Programado" valor={pct(o.pct_programado)} />
          <MiniDato etiqueta="Gastado" valor={moneyCorto(o.gastado)} detalle={o.contrato > 0 ? `${pct(uso)} del contrato` : undefined} />
          <MiniDato
            etiqueta={dias !== null && dias < 0 ? 'Terminó' : 'Termina'}
            valor={dias === null ? '—' : dias < 0 ? fecha(o.termino) : dias === 0 ? 'Hoy' : `${dias} días`}
            alerta={dias !== null && dias >= 0 && dias <= 7 && atrasada(o)}
          />
        </View>
      </Tarjeta>
    </Pressable>
  );
}

function MiniDato({ etiqueta, valor, detalle, alerta }: { etiqueta: string; valor: string; detalle?: string; alerta?: boolean }) {
  return (
    <View style={{ flex: 1, gap: 1 }}>
      <Text style={s.miniEtiqueta}>{etiqueta}</Text>
      <Text style={[s.miniValor, alerta && { color: Colores.ambar }]} numberOfLines={1}>
        {valor}
      </Text>
      {detalle ? (
        <Text style={s.miniDetalle} numberOfLines={1}>
          {detalle}
        </Text>
      ) : null}
    </View>
  );
}

const s = StyleSheet.create({
  hero: {
    overflow: 'hidden',
    paddingHorizontal: Espacio.l,
    paddingBottom: Espacio.xl,
    borderBottomLeftRadius: 28,
    borderBottomRightRadius: 28,
    gap: Espacio.l,
    backgroundColor: Colores.navy,
  },
  heroFila: { flexDirection: 'row', alignItems: 'center', gap: Espacio.m },
  heroFecha: { fontSize: 13, color: Colores.acentoClaro, fontWeight: '600' },
  heroSaludo: { marginTop: 2, fontSize: 24, fontWeight: '800', color: Colores.blanco, letterSpacing: -0.4 },
  pildora: { flexDirection: 'row', alignItems: 'center', gap: 4, paddingHorizontal: 10, paddingVertical: 6, borderRadius: 999, backgroundColor: 'rgba(255,255,255,0.92)' },
  pildoraTexto: { fontSize: 13, fontWeight: '800', color: Colores.navy },
  heroKpis: { flexDirection: 'row', gap: Espacio.s },
  kpiHero: {
    flex: 1,
    gap: 2,
    padding: Espacio.m,
    borderRadius: 16,
    borderWidth: 1,
    borderColor: 'rgba(255,255,255,0.1)',
    backgroundColor: 'rgba(255,255,255,0.07)',
  },
  kpiHeroValor: { marginTop: 4, fontSize: 22, fontWeight: '800', color: Colores.blanco, letterSpacing: -0.4 },
  kpiHeroEtiqueta: { fontSize: 12, color: Colores.textoNavySuave },
  cuerpo: { paddingHorizontal: Espacio.l, gap: Espacio.m },
  fila: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', gap: Espacio.s },
  semana: { flexDirection: 'row', gap: Espacio.s },
  datoSemana: { flex: 1, gap: 2, padding: Espacio.m, borderRadius: 14, backgroundColor: Colores.fondo },
  datoSemanaValor: { marginTop: 4, fontSize: 18, fontWeight: '800', color: Colores.navy },
  barraFondo: { height: 10, borderRadius: 999, overflow: 'hidden', backgroundColor: Colores.fondo },
  barra: { height: '100%', borderRadius: 999 },
  alerta: { flexDirection: 'row', gap: Espacio.s, alignItems: 'center', padding: Espacio.m, borderRadius: 12, backgroundColor: Colores.ambarSuave },
  alertaTexto: { flex: 1, fontSize: 13, color: Colores.ambar },
  buscador: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: Espacio.s,
    paddingHorizontal: Espacio.m,
    borderRadius: 14,
    backgroundColor: Colores.blanco,
    borderWidth: 1,
    borderColor: Colores.linea,
    boxShadow: Sombra.tarjeta,
  },
  buscadorInput: { flex: 1, paddingVertical: 12, fontSize: 15, color: Colores.texto },
  obraFila: { flexDirection: 'row', gap: Espacio.m, alignItems: 'center' },
  anilloTexto: { fontSize: 13, fontWeight: '800', color: Colores.navy },
  folio: { fontSize: 12, fontWeight: '700', color: Colores.textoTenue, letterSpacing: 0.3 },
  obraNombre: { fontSize: 16, fontWeight: '800', color: Colores.navy, letterSpacing: -0.2 },
  filaNombre: { fontSize: 15, fontWeight: '800', color: Colores.navy },
  filaPct: { fontSize: 20, fontWeight: '800', color: Colores.navy, letterSpacing: -0.4 },
  filaAtraso: { fontSize: 12, color: Colores.ambar },
  lugar: { flexDirection: 'row', alignItems: 'center', gap: 4 },
  separador: { height: 1, backgroundColor: Colores.linea },
  obraDatos: { flexDirection: 'row', gap: Espacio.m },
  miniEtiqueta: { fontSize: 11, color: Colores.textoTenue, fontWeight: '600', textTransform: 'uppercase', letterSpacing: 0.4 },
  miniValor: { fontSize: 15, fontWeight: '800', color: Colores.texto },
  miniDetalle: { fontSize: 11, color: Colores.textoSuave },
});
