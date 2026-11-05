import { Image } from 'expo-image';
import { router, Stack, useFocusEffect, useLocalSearchParams } from 'expo-router';
import { useCallback, useMemo, useState } from 'react';
import { Alert, Pressable, RefreshControl, ScrollView, StyleSheet, Text, TextInput, useWindowDimensions, View } from 'react-native';
import Animated, { FadeIn, FadeInDown } from 'react-native-reanimated';

import { colorCategoria, IconoCategoria } from '@/components/categoria';
import { FilaRegistro } from '@/components/fila-registro';
import { CurvaAvance, semanaActual } from '@/components/curva-avance';
import { FlujoSemanal } from '@/components/flujo-semanal';
import {
  Anillo,
  AvisoSinSenal,
  BarraAvance,
  Boton,
  Icono,
  InsigniaEstado,
  Kpi,
  Opciones,
  Seccion,
  Segmentos,
  Tarjeta,
  Vacio,
  estilos as ui,
  type NombreIcono,
} from '@/components/ui';
import { ApiError, encabezadosAuth, obtener, servidorActual, SinConexion, subirArchivo } from '@/lib/api';
import { usePendientes, type Pendiente } from '@/lib/cola';
import { diaRelativo, diasHasta, fecha, money, moneyCorto, numero, pct } from '@/lib/formato';
import { elegirFoto } from '@/lib/imagen';
import { useSesion, useSoloConsulta } from '@/lib/sesion';
import { Colores, Degradado, Espacio, Sombra } from '@/lib/tema';
import { NO_COSTO, NOMBRE_CATEGORIA, type Avance, type ConceptoAvance, type Control, type Movimiento } from '@/lib/tipos';

type Nav = { obra: string; titulo: string };
type Pestana = 'resumen' | 'avance' | 'gastos' | 'fotos';
const SECCIONES: { valor: Pestana; texto: string; icono: NombreIcono }[] = [
  { valor: 'resumen', texto: 'Resumen', icono: 'view-dashboard-outline' },
  { valor: 'avance', texto: 'Avance', icono: 'progress-check' },
  { valor: 'gastos', texto: 'Gastos', icono: 'cash-multiple' },
  { valor: 'fotos', texto: 'Fotos', icono: 'image-multiple-outline' },
];

export default function ObraDetalle() {
  const { id, titulo, nombre, seccion: seccionInicial } = useLocalSearchParams<{ id: string; titulo?: string; nombre?: string; seccion?: Pestana }>();
  const [seccion, setSeccion] = useState<Pestana>(seccionInicial ?? 'resumen');
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
      <ScrollView
        style={{ backgroundColor: Colores.fondo }}
        contentContainerStyle={{ paddingBottom: Espacio.xl * 2 }}
        refreshControl={<RefreshControl refreshing={cargando && avance !== null} onRefresh={cargar} colors={[Colores.acento]} />}>
        <Cabecera avance={avance} nombre={nombre} />

        <View style={s.contenido}>
          <Segmentos opciones={SECCIONES} valor={seccion} onCambio={setSeccion} />
          {cache !== null && <AvisoSinSenal guardado={cache} />}
          {error ? <Text style={{ color: Colores.rojo }}>{error}</Text> : null}
          {pendientes.length > 0 && <AvisoPendientes pendientes={pendientes} />}

          {!avance && !error ? <Text style={[ui.textoSuave, { textAlign: 'center', padding: Espacio.xl }]}>Cargando la obra…</Text> : null}

          <Animated.View key={seccion} entering={FadeIn.duration(250)} style={{ gap: Espacio.m }}>
            {avance && control && seccion === 'resumen' && <Resumen avance={avance} control={control} nav={nav} irA={setSeccion} />}
            {avance && seccion === 'avance' && <ListaAvance avance={avance} nav={nav} />}
            {control && seccion === 'gastos' && <ListaGastos control={control} nav={nav} recargar={cargar} />}
            {avance && seccion === 'fotos' && <Galeria avance={avance} nav={nav} />}
          </Animated.View>
        </View>
      </ScrollView>
    </>
  );
}

/** Continúa el navy del encabezado: anillo de avance real contra el programado y la semana en curso. */
function Cabecera({ avance, nombre }: { avance: Avance | null; nombre?: string }) {
  const semanas = avance?.semanas ?? [];
  const actual = semanaActual(semanas);
  const termino = semanas.length ? semanas[semanas.length - 1].fin : null;
  const dias = diasHasta(termino);
  const diferencia = avance ? (avance.pct_ejecutado - avance.pct_programado) * 100 : 0;
  const adelantada = diferencia >= -0.05;

  return (
    <View style={s.cabecera}>
      {nombre ? (
        <Text style={s.cabeceraNombre} numberOfLines={2}>
          {nombre}
        </Text>
      ) : null}
      <View style={s.cabeceraFila}>
        <Anillo valor={avance?.pct_ejecutado ?? 0} programado={avance?.pct_programado} tam={104} grosor={10} pista="rgba(255,255,255,0.12)">
          <Text style={s.anilloValor}>{avance ? pct(avance.pct_ejecutado) : '—'}</Text>
          <Text style={s.anilloEtiqueta}>real</Text>
        </Anillo>
        <View style={{ flex: 1, gap: Espacio.s }}>
          <FilaCabecera icono="flag-checkered" texto={`Programado a hoy ${avance ? pct(avance.pct_programado) : '—'}`} />
          {avance ? (
            <View style={[s.diferencia, { backgroundColor: adelantada ? 'rgba(34,160,107,0.2)' : 'rgba(245,165,36,0.18)' }]}>
              <Icono nombre={adelantada ? 'trending-up' : 'trending-down'} color={adelantada ? Colores.verdeNavy : Colores.dorado} tam={16} />
              <Text style={[s.diferenciaTexto, { color: adelantada ? Colores.verdeNavy : Colores.dorado }]}>
                {diferencia >= 0 ? '+' : '−'}
                {numero(Math.abs(diferencia), 1)} pts {adelantada ? 'arriba' : 'abajo'} del programa
              </Text>
            </View>
          ) : null}
          {actual >= 0 ? <FilaCabecera icono="calendar-week" texto={`Semana ${semanas[actual].numero} de ${semanas.length}`} /> : null}
          {dias !== null ? (
            <FilaCabecera icono="timer-sand" texto={dias > 0 ? `Faltan ${dias} días · ${fecha(termino)}` : dias === 0 ? 'Termina hoy' : `Terminó el ${fecha(termino)}`} />
          ) : null}
        </View>
      </View>
    </View>
  );
}

function FilaCabecera({ icono, texto }: { icono: NombreIcono; texto: string }) {
  return (
    <View style={s.filaCabecera}>
      <Icono nombre={icono} color={Colores.acentoClaro} tam={16} />
      <Text style={s.filaCabeceraTexto} numberOfLines={1}>
        {texto}
      </Text>
    </View>
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

function Accion({ icono, titulo, detalle, onPress, principal }: { icono: NombreIcono; titulo: string; detalle: string; onPress: () => void; principal?: boolean }) {
  return (
    <Pressable onPress={onPress} style={({ pressed }) => [s.accion, principal && s.accionPrincipal, pressed && { transform: [{ scale: 0.97 }] }]}>
      <View style={[s.accionIcono, principal && { backgroundColor: 'rgba(255,255,255,0.18)' }]}>
        <Icono nombre={icono} color={principal ? Colores.blanco : Colores.acento} tam={22} />
      </View>
      <Text style={[s.accionTitulo, principal && { color: Colores.blanco }]}>{titulo}</Text>
      <Text style={[s.accionDetalle, principal && { color: 'rgba(255,255,255,0.75)' }]} numberOfLines={1}>
        {detalle}
      </Text>
    </Pressable>
  );
}

function Resumen({ avance, control, nav, irA }: { avance: Avance; control: Control; nav: Nav; irA: (s: Pestana) => void }) {
  const actual = semanaActual(avance.semanas);
  const semana = actual >= 0 ? avance.semanas[actual] : undefined;
  const fotos = avance.registros.filter((r) => r.foto).length;
  const usoPresupuesto = control.costo_presupuestado > 0 ? control.gastado / control.costo_presupuestado : 0;
  const soloConsulta = useSoloConsulta();

  return (
    <>
      {!soloConsulta && (
        <View style={s.acciones}>
          <Accion principal icono="progress-check" titulo="Avance" detalle="Registrar hoy" onPress={() => router.push({ pathname: '/avance', params: { ...nav } })} />
          <Accion icono="receipt" titulo="Gasto" detalle="Con su ticket" onPress={() => router.push({ pathname: '/movimiento', params: { ...nav, tipo: 'egreso' } })} />
          <Accion icono="image-multiple-outline" titulo="Fotos" detalle={`${fotos} evidencia${fotos === 1 ? '' : 's'}`} onPress={() => irA('fotos')} />
        </View>
      )}

      <Tarjeta style={{ gap: Espacio.m }}>
        <View style={s.fila}>
          <Text style={ui.titulo}>Curva de avance</Text>
          <InsigniaEstado real={avance.pct_ejecutado} programado={avance.pct_programado} />
        </View>
        <CurvaAvance semanas={avance.semanas} contrato={avance.contrato} />
      </Tarjeta>

      {soloConsulta && <EstadoConceptos conceptos={avance.conceptos} onPress={() => irA('avance')} />}

      {semana && !soloConsulta && (
        <Tarjeta style={{ gap: Espacio.m }}>
          <View style={s.fila}>
            <Text style={ui.titulo}>Esta semana</Text>
            <Text style={ui.textoSuave}>
              {fecha(semana.inicio).slice(0, 5)} – {fecha(semana.fin).slice(0, 5)}
            </Text>
          </View>
          <View style={s.semana}>
            <DatoSemana etiqueta="Programado" valor={moneyCorto(semana.programado)} />
            <DatoSemana etiqueta="Ejecutado" valor={moneyCorto(semana.ejecutado)} color={semana.ejecutado + 0.01 < semana.programado ? Colores.ambar : Colores.verde} />
            <DatoSemana etiqueta="Gastado" valor={moneyCorto(semana.gastado)} />
          </View>
          <BarraAvance real={semana.programado > 0 ? semana.ejecutado / semana.programado : 0} programado={1} />
        </Tarjeta>
      )}

      <Seccion titulo="Dinero de la obra" accion="Ver gastos" onAccion={() => irA('gastos')} />
      {soloConsulta ? (
        <Tarjeta style={{ gap: Espacio.m }}>
          <View style={s.fila}>
            <Text style={ui.titulo}>Flujo por semana</Text>
            <Text style={ui.textoSuave}>Contrato {moneyCorto(control.contrato)} sin IVA</Text>
          </View>
          <View style={s.semana}>
            <DatoSemana etiqueta="Cobrado" valor={moneyCorto(control.cobrado)} color={Colores.verde} />
            <DatoSemana etiqueta="Gastado" valor={moneyCorto(control.gastado)} color={Colores.violeta} />
            <DatoSemana etiqueta="Saldo de caja" valor={moneyCorto(control.saldo)} color={control.saldo < 0 ? Colores.rojo : Colores.navy} />
          </View>
          <FlujoSemanal semanas={avance.semanas} />
        </Tarjeta>
      ) : (
        <>
          <View style={s.kpis}>
            <Kpi icono="file-sign" etiqueta="Contrato (sin IVA)" valor={moneyCorto(control.contrato)} />
            <Kpi icono="cash-plus" etiqueta="Cobrado" valor={moneyCorto(control.cobrado)} color={Colores.verde} fondo={Colores.verdeSuave} />
          </View>
          <View style={s.kpis}>
            <Kpi icono="cash-minus" etiqueta="Gastado" valor={moneyCorto(control.gastado)} color={Colores.violeta} fondo={Colores.violetaSuave} />
            <Kpi
              icono="safe"
              etiqueta="Saldo de caja"
              valor={moneyCorto(control.saldo)}
              color={control.saldo < 0 ? Colores.rojo : Colores.ambar}
              fondo={control.saldo < 0 ? Colores.rojoSuave : Colores.ambarSuave}
            />
          </View>
        </>
      )}
      <Tarjeta style={{ gap: Espacio.s }}>
        <View style={s.fila}>
          <Text style={ui.textoSuave}>Gastado del costo presupuestado</Text>
          <Text style={[ui.texto, { fontWeight: '800', color: usoPresupuesto > 1 ? Colores.rojo : Colores.navy }]}>{pct(usoPresupuesto)}</Text>
        </View>
        <BarraAvance real={usoPresupuesto} color={usoPresupuesto > 1 ? Colores.rojo : Colores.violeta} />
        <Text style={ui.textoSuave}>
          {money(control.gastado)} de {money(control.costo_presupuestado)}
        </Text>
      </Tarjeta>
    </>
  );
}

function DatoSemana({ etiqueta, valor, color }: { etiqueta: string; valor: string; color?: string }) {
  return (
    <View style={s.datoSemana}>
      <Text style={ui.textoSuave}>{etiqueta}</Text>
      <Text style={[s.datoSemanaValor, color ? { color } : null]} numberOfLines={1} adjustsFontSizeToFit>
        {valor}
      </Text>
    </View>
  );
}

type FiltroAvance = 'todos' | 'atrasados' | 'proceso' | 'terminados';
const estadoConcepto = (c: ConceptoAvance): FiltroAvance => (c.pct >= 1 ? 'terminados' : c.atrasado ? 'atrasados' : 'proceso');

/** Cómo van los conceptos de un vistazo; al tocarla abre la lista. */
function EstadoConceptos({ conceptos, onPress }: { conceptos: ConceptoAvance[]; onPress: () => void }) {
  if (!conceptos.length) return null;
  const cuenta = (f: FiltroAvance) => conceptos.filter((c) => estadoConcepto(c) === f).length;
  const partes = [
    { texto: 'Terminados', n: cuenta('terminados'), color: Colores.acento },
    { texto: 'En tiempo', n: cuenta('proceso'), color: Colores.verde },
    { texto: 'Atrasados', n: cuenta('atrasados'), color: Colores.ambarMarca },
  ];

  return (
    <Pressable onPress={onPress} style={({ pressed }) => pressed && { transform: [{ scale: 0.985 }], opacity: 0.92 }}>
      <Tarjeta style={{ gap: Espacio.m }}>
        <View style={s.fila}>
          <Text style={ui.titulo}>Conceptos</Text>
          <Text style={ui.textoSuave}>{conceptos.length} en total</Text>
        </View>
        <View style={s.reparto}>
          {partes
            .filter((p) => p.n > 0)
            .map((p) => (
              <View key={p.texto} style={{ flex: p.n, backgroundColor: p.color }} />
            ))}
        </View>
        <View style={s.semana}>
          {partes.map((p) => (
            <View key={p.texto} style={s.datoSemana}>
              <View style={s.leyendaFila}>
                <View style={[s.leyendaPunto, { backgroundColor: p.color }]} />
                <Text style={ui.textoSuave}>{p.texto}</Text>
              </View>
              <Text style={s.datoSemanaValor}>{p.n}</Text>
            </View>
          ))}
        </View>
      </Tarjeta>
    </Pressable>
  );
}

function ListaAvance({ avance, nav }: { avance: Avance; nav: Nav }) {
  const soloConsulta = useSoloConsulta();
  const [busqueda, setBusqueda] = useState('');
  const [filtro, setFiltro] = useState<FiltroAvance>('todos');
  const cuenta = (f: FiltroAvance) => avance.conceptos.filter((c) => estadoConcepto(c) === f).length;
  const conceptos = useMemo(() => {
    const t = busqueda.trim().toLowerCase();
    return avance.conceptos.filter(
      (c) => (filtro === 'todos' || estadoConcepto(c) === filtro) && (!t || `${c.clave} ${c.descripcion}`.toLowerCase().includes(t)),
    );
  }, [avance.conceptos, busqueda, filtro]);

  return (
    <>
      {!soloConsulta && <Boton titulo="Registrar avance" icono="plus" onPress={() => router.push({ pathname: '/avance', params: { ...nav } })} />}
      {avance.conceptos.length === 0 && <Vacio icono="format-list-checks" texto="La cotización no tiene conceptos." />}
      {avance.conceptos.length > 0 && (
        <>
          <Buscador valor={busqueda} onCambio={setBusqueda} placeholder="Buscar concepto por clave o descripción" />
          <Opciones
            opciones={[
              { valor: 'todos', texto: 'Todos', cuenta: avance.conceptos.length },
              { valor: 'atrasados', texto: 'Atrasados', cuenta: cuenta('atrasados') },
              { valor: 'proceso', texto: 'En tiempo', cuenta: cuenta('proceso') },
              { valor: 'terminados', texto: 'Terminados', cuenta: cuenta('terminados') },
            ]}
            valor={filtro}
            onCambio={setFiltro}
          />
        </>
      )}
      {avance.conceptos.length > 0 && conceptos.length === 0 && <Vacio icono="magnify" texto="Ningún concepto coincide." />}
      {conceptos.map((c, i) => (
        <Animated.View key={c.id} entering={FadeInDown.duration(350).delay(Math.min(i, 8) * 40)}>
          <Pressable
            onPress={() => router.push({ pathname: '/concepto', params: { ...nav, concepto: String(c.id) } })}
            style={({ pressed }) => pressed && { transform: [{ scale: 0.985 }], opacity: 0.92 }}>
            <Tarjeta style={{ gap: Espacio.s }}>
              <View style={s.conceptoFila}>
                <Anillo valor={c.pct} programado={c.pct_programado} tam={46} grosor={5} color={c.excedente ? Colores.rojo : c.pct >= 1 ? Colores.acento : undefined}>
                  <Text style={s.anilloChico}>{pct(c.pct)}</Text>
                </Anillo>
                <View style={{ flex: 1, gap: 2 }}>
                  <View style={s.fila}>
                    <Text style={s.clave}>{c.clave}</Text>
                    {c.excedente || c.pct >= 1 || c.atrasado ? <InsigniaEstado real={c.pct} programado={c.pct_programado} excedente={c.excedente} /> : null}
                  </View>
                  <Text style={ui.texto} numberOfLines={2}>
                    {c.descripcion}
                  </Text>
                </View>
              </View>
              <Text style={ui.textoSuave}>
                {numero(c.ejecutado)} de {numero(c.cantidad)} {c.unidad} · debería ir en {numero(c.programado)}
                {c.ultimo_avance ? ` · último ${fecha(c.ultimo_avance)}` : ''}
              </Text>
            </Tarjeta>
          </Pressable>
        </Animated.View>
      ))}

      {avance.registros.length > 0 && <Seccion titulo="Últimos registros" />}
      {avance.registros.slice(0, 15).map((r) => (
        <FilaRegistro key={r.id} registro={r} nav={nav} />
      ))}
    </>
  );
}

function Buscador({ valor, onCambio, placeholder }: { valor: string; onCambio: (t: string) => void; placeholder: string }) {
  return (
    <View style={s.buscador}>
      <Icono nombre="magnify" color={Colores.textoTenue} />
      <TextInput value={valor} onChangeText={onCambio} placeholder={placeholder} placeholderTextColor={Colores.textoTenue} style={s.buscadorInput} autoCorrect={false} />
      {valor ? (
        <Pressable onPress={() => onCambio('')} hitSlop={8} accessibilityLabel="Limpiar búsqueda">
          <Icono nombre="close-circle" color={Colores.textoTenue} tam={18} />
        </Pressable>
      ) : null}
    </View>
  );
}

function ListaGastos({ control, nav, recargar }: { control: Control; nav: Nav; recargar: () => void }) {
  // El ingeniero (residente) registra gastos; los ingresos los lleva la oficina. El Admin solo consulta.
  const { usuario } = useSesion();
  const esIngeniero = usuario?.rol_nombre === 'Ingeniero';
  const soloConsulta = useSoloConsulta();
  const [subiendo, setSubiendo] = useState<number | null>(null);

  // Reparto del costo por categoría (sin préstamos ni retiros, que no son costo).
  const costos = control.movimientos.filter((m) => m.tipo === 'egreso' && !NO_COSTO.includes(m.categoria));
  const totalCosto = costos.reduce((t, m) => t + Number(m.monto), 0);
  const porCategoria = Object.entries(
    costos.reduce<Record<string, number>>((acc, m) => ({ ...acc, [m.categoria]: (acc[m.categoria] ?? 0) + Number(m.monto) }), {}),
  ).sort((a, b) => b[1] - a[1]);

  const grupos = control.movimientos.reduce<{ dia: string; movimientos: Movimiento[] }[]>((acc, m) => {
    const ultimo = acc[acc.length - 1];
    if (ultimo && ultimo.dia === m.fecha) ultimo.movimientos.push(m);
    else acc.push({ dia: m.fecha, movimientos: [m] });
    return acc;
  }, []);

  async function agregarTicket(m: Movimiento) {
    const uri = await elegirFoto();
    if (!uri) return;
    setSubiendo(m.id);
    try {
      await subirArchivo(`/api/movimientos/${m.id}/comprobante`, uri);
      recargar();
    } catch (e) {
      Alert.alert(
        'No se pudo subir la foto',
        e instanceof SinConexion ? 'Para agregar el ticket a un gasto ya enviado se necesita señal. Inténtalo de nuevo con conexión.' : e instanceof ApiError ? e.message : 'Error desconocido',
      );
    } finally {
      setSubiendo(null);
    }
  }

  return (
    <>
      {!soloConsulta && (
        <View style={s.botones}>
          <Boton titulo="Gasto" icono="minus-circle-outline" onPress={() => router.push({ pathname: '/movimiento', params: { ...nav, tipo: 'egreso' } })} style={{ flex: 1 }} />
          {!esIngeniero && (
            <Boton titulo="Ingreso" icono="plus-circle-outline" tipo="secundario" onPress={() => router.push({ pathname: '/movimiento', params: { ...nav, tipo: 'ingreso' } })} style={{ flex: 1 }} />
          )}
        </View>
      )}

      {totalCosto > 0 && (
        <Tarjeta style={{ gap: Espacio.m }}>
          <View style={s.fila}>
            <Text style={ui.titulo}>¿En qué se ha gastado?</Text>
            <Text style={[ui.texto, { fontWeight: '800', color: Colores.navy }]}>{moneyCorto(totalCosto)}</Text>
          </View>
          <View style={s.reparto}>
            {porCategoria.map(([cat, monto]) => (
              <View key={cat} style={{ flex: monto, backgroundColor: colorCategoria(cat) }} />
            ))}
          </View>
          <View style={{ gap: Espacio.s }}>
            {porCategoria.map(([cat, monto]) => (
              <View key={cat} style={s.leyendaFila}>
                <View style={[s.leyendaPunto, { backgroundColor: colorCategoria(cat) }]} />
                <Text style={[ui.texto, { flex: 1 }]}>{NOMBRE_CATEGORIA[cat] ?? cat}</Text>
                <Text style={ui.textoSuave}>{pct(monto / totalCosto)}</Text>
                <Text style={[ui.texto, { fontWeight: '700', minWidth: 76, textAlign: 'right' }]}>{moneyCorto(monto)}</Text>
              </View>
            ))}
          </View>
        </Tarjeta>
      )}

      {control.movimientos.length === 0 && <Vacio icono="cash-minus" texto="Sin movimientos registrados." />}
      {grupos.map((g) => (
        <View key={g.dia} style={{ gap: Espacio.s }}>
          <Text style={s.dia}>{diaRelativo(g.dia)}</Text>
          <Tarjeta style={{ padding: 0, gap: 0 }}>
            {g.movimientos.map((m, i) => {
              const ingreso = m.tipo === 'ingreso';
              return (
                <View key={m.id} style={[s.movimiento, i > 0 && s.movimientoBorde]}>
                  <IconoCategoria categoria={m.categoria} />
                  <View style={{ flex: 1, gap: 2 }}>
                    <Text style={[ui.texto, { fontWeight: '700' }]} numberOfLines={2}>
                      {m.descripcion}
                    </Text>
                    <Text style={ui.textoSuave} numberOfLines={1}>
                      {NOMBRE_CATEGORIA[m.categoria] ?? m.categoria}
                      {m.concepto_clave ? ` · ${m.concepto_clave}` : ''}
                      {m.proveedor ? ` · ${m.proveedor}` : ''}
                    </Text>
                    {m.comprobante ? (
                      <Pressable onPress={() => router.push({ pathname: '/comprobante', params: { tipo: 'movimientos', id: String(m.id) } })} hitSlop={6} style={s.ticket}>
                        <Icono nombre="paperclip" color={Colores.acento} tam={14} />
                        <Text style={s.ticketTexto}>Ver comprobante</Text>
                      </Pressable>
                    ) : !ingreso && !soloConsulta ? (
                      <Pressable onPress={() => agregarTicket(m)} hitSlop={6} style={s.ticket} disabled={subiendo !== null}>
                        <Icono nombre="camera-plus-outline" color={Colores.ambar} tam={14} />
                        <Text style={[s.ticketTexto, { color: Colores.ambar }]}>{subiendo === m.id ? 'Subiendo…' : 'Agregar ticket'}</Text>
                      </Pressable>
                    ) : null}
                  </View>
                  <Text style={[s.monto, ingreso && { color: Colores.verde }]}>
                    {ingreso ? '+' : '−'}
                    {money(m.monto)}
                  </Text>
                </View>
              );
            })}
          </Tarjeta>
        </View>
      ))}
    </>
  );
}

/** Galería de evidencias: todas las fotos de avance, la más reciente primero (como en la web). */
function Galeria({ avance, nav }: { avance: Avance; nav: Nav }) {
  const { width } = useWindowDimensions();
  const soloConsulta = useSoloConsulta();
  const fotos = avance.registros.filter((r) => r.foto);
  const lado = (width - Espacio.l * 2 - Espacio.s * 2) / 3;

  if (!fotos.length) {
    return soloConsulta ? (
      <Vacio icono="image-multiple-outline" texto={'Aún no hay fotos.\nAparecen cuando el residente registra avance con evidencia.'} />
    ) : (
      <Vacio
        icono="image-multiple-outline"
        texto={'Aún no hay fotos.\nAl registrar avance, adjunta una foto como evidencia.'}
        accion={<Boton titulo="Registrar avance" icono="camera" onPress={() => router.push({ pathname: '/avance', params: { ...nav } })} />}
      />
    );
  }

  return (
    <>
      <Text style={ui.textoSuave}>
        {fotos.length} foto{fotos.length === 1 ? '' : 's'} de evidencia · toca una para ver su registro
      </Text>
      <View style={s.galeria}>
        {fotos.map((r, i) => (
          <Animated.View key={r.id} entering={FadeIn.duration(300).delay(Math.min(i, 12) * 30)}>
            <Pressable onPress={() => router.push({ pathname: '/registro', params: { ...nav, id: String(r.id) } })} style={({ pressed }) => pressed && { opacity: 0.85 }}>
              <Image
                source={{ uri: `${servidorActual()}/archivos/avances/${r.id}`, headers: encabezadosAuth() }}
                style={[s.foto, { width: lado, height: lado }]}
                contentFit="cover"
                cachePolicy="memory-disk"
                transition={200}
                accessibilityLabel={`Evidencia ${r.concepto_clave} del ${fecha(r.fecha)}`}
              />
              <View style={s.fotoPie}>
                <Text style={s.fotoPieTexto} numberOfLines={1}>
                  {r.concepto_clave} · {fecha(r.fecha).slice(0, 5)}
                </Text>
              </View>
            </Pressable>
          </Animated.View>
        ))}
      </View>
    </>
  );
}

const s = StyleSheet.create({
  cabecera: {
    gap: Espacio.l,
    paddingHorizontal: Espacio.l,
    paddingTop: Espacio.s,
    paddingBottom: Espacio.xl,
    borderBottomLeftRadius: 28,
    borderBottomRightRadius: 28,
    backgroundColor: Colores.navy,
    backgroundImage: Degradado.navy,
  },
  cabeceraNombre: { fontSize: 20, fontWeight: '800', color: Colores.blanco, letterSpacing: -0.3 },
  cabeceraFila: { flexDirection: 'row', alignItems: 'center', gap: Espacio.l },
  anilloValor: { fontSize: 22, fontWeight: '800', color: Colores.blanco, letterSpacing: -0.5 },
  anilloEtiqueta: { fontSize: 11, color: Colores.acentoClaro, fontWeight: '600', textTransform: 'uppercase', letterSpacing: 1 },
  filaCabecera: { flexDirection: 'row', alignItems: 'center', gap: 6 },
  filaCabeceraTexto: { flex: 1, fontSize: 13, color: Colores.textoNavy },
  diferencia: { flexDirection: 'row', alignItems: 'center', gap: 6, alignSelf: 'flex-start', paddingHorizontal: 10, paddingVertical: 5, borderRadius: 999 },
  diferenciaTexto: { fontSize: 12, fontWeight: '700' },
  contenido: { padding: Espacio.l, gap: Espacio.m },
  fila: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', gap: Espacio.s },
  botones: { flexDirection: 'row', gap: Espacio.m },
  aviso: { flexDirection: 'row', alignItems: 'center', gap: Espacio.s, backgroundColor: Colores.acentoSuave, padding: Espacio.m, borderRadius: 14 },
  acciones: { flexDirection: 'row', gap: Espacio.s },
  accion: { flex: 1, gap: 2, padding: Espacio.m, borderRadius: 18, backgroundColor: Colores.blanco, borderWidth: 1, borderColor: Colores.linea, boxShadow: Sombra.tarjeta },
  accionPrincipal: { borderWidth: 0, backgroundImage: Degradado.primario, boxShadow: '0 8px 20px rgba(47,117,181,0.3)' },
  accionIcono: { width: 40, height: 40, borderRadius: 12, alignItems: 'center', justifyContent: 'center', marginBottom: 6, backgroundColor: Colores.acentoSuave },
  accionTitulo: { fontSize: 15, fontWeight: '800', color: Colores.navy },
  accionDetalle: { fontSize: 12, color: Colores.textoSuave },
  semana: { flexDirection: 'row', gap: Espacio.s },
  datoSemana: { flex: 1, gap: 2 },
  datoSemanaValor: { fontSize: 18, fontWeight: '800', color: Colores.navy },
  kpis: { flexDirection: 'row', gap: Espacio.s },
  conceptoFila: { flexDirection: 'row', alignItems: 'center', gap: Espacio.m },
  anilloChico: { fontSize: 11, fontWeight: '800', color: Colores.navy },
  clave: { fontSize: 13, fontWeight: '800', color: Colores.acento, letterSpacing: 0.2 },
  buscador: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: Espacio.s,
    paddingHorizontal: Espacio.m,
    borderRadius: 14,
    backgroundColor: Colores.blanco,
    borderWidth: 1,
    borderColor: Colores.linea,
  },
  buscadorInput: { flex: 1, paddingVertical: 11, fontSize: 15, color: Colores.texto },
  reparto: { flexDirection: 'row', height: 12, borderRadius: 999, overflow: 'hidden', gap: 2 },
  leyendaFila: { flexDirection: 'row', alignItems: 'center', gap: Espacio.s },
  leyendaPunto: { width: 10, height: 10, borderRadius: 3 },
  dia: { marginTop: Espacio.s, fontSize: 13, fontWeight: '800', color: Colores.textoSuave, textTransform: 'capitalize' },
  movimiento: { flexDirection: 'row', alignItems: 'flex-start', gap: Espacio.m, padding: Espacio.m },
  movimientoBorde: { borderTopWidth: 1, borderTopColor: Colores.linea },
  monto: { fontSize: 15, fontWeight: '800', color: Colores.texto },
  ticket: { flexDirection: 'row', alignItems: 'center', gap: 4, marginTop: 4, alignSelf: 'flex-start' },
  ticketTexto: { fontSize: 13, fontWeight: '700', color: Colores.acento },
  galeria: { flexDirection: 'row', flexWrap: 'wrap', gap: Espacio.s },
  foto: { borderRadius: 14, backgroundColor: Colores.borde },
  fotoPie: {
    position: 'absolute',
    left: 0,
    right: 0,
    bottom: 0,
    paddingHorizontal: 8,
    paddingVertical: 6,
    borderBottomLeftRadius: 14,
    borderBottomRightRadius: 14,
    backgroundImage: 'linear-gradient(180deg, rgba(8,26,46,0) 0%, rgba(8,26,46,0.8) 100%)',
  },
  fotoPieTexto: { fontSize: 11, fontWeight: '700', color: Colores.blanco },
});
