import { router, Stack, useFocusEffect, useLocalSearchParams } from 'expo-router';
import { useCallback, useState } from 'react';
import { RefreshControl, ScrollView, StyleSheet, Text, View } from 'react-native';

import { FilaRegistro } from '@/components/fila-registro';
import { Anillo, AvisoSinSenal, BarraAvance, Boton, InsigniaEstado, Seccion, Tarjeta, Vacio, estilos as ui } from '@/components/ui';
import { obtener } from '@/lib/api';
import { fecha, money, numero, pct } from '@/lib/formato';
import { useSoloConsulta } from '@/lib/sesion';
import { Colores, Degradado, Espacio } from '@/lib/tema';
import type { Avance } from '@/lib/tipos';

/** Historial de un concepto: cuánto lleva contra el programa y cada registro con su foto. */
export default function Concepto() {
  const params = useLocalSearchParams<{ obra: string; titulo?: string; concepto: string }>();
  const nav = { obra: params.obra, titulo: params.titulo ?? '' };
  const soloConsulta = useSoloConsulta();
  const [avance, setAvance] = useState<Avance | null>(null);
  const [cache, setCache] = useState<string | undefined | null>(null);
  const [error, setError] = useState('');
  const [cargando, setCargando] = useState(false);

  const cargar = useCallback(async () => {
    setCargando(true);
    try {
      const r = await obtener<Avance>(`/api/cotizaciones/${params.obra}/avance`);
      setAvance(r.datos);
      setCache(r.deCache ? r.guardado : null);
      setError('');
    } catch (e) {
      setError((e as Error).message);
    } finally {
      setCargando(false);
    }
  }, [params.obra]);

  useFocusEffect(
    useCallback(() => {
      cargar();
    }, [cargar]),
  );

  const c = avance?.conceptos.find((x) => x.id === Number(params.concepto));
  const registros = (avance?.registros ?? []).filter((r) => Number(r.concepto_id) === Number(params.concepto));
  const faltan = c ? Math.max(0, c.cantidad - c.ejecutado) : 0;

  return (
    <>
      <Stack.Screen options={{ title: c?.clave ?? 'Concepto' }} />
      <ScrollView
        style={{ backgroundColor: Colores.fondo }}
        contentContainerStyle={{ paddingBottom: Espacio.xl * 2 }}
        refreshControl={<RefreshControl refreshing={cargando && avance !== null} onRefresh={cargar} colors={[Colores.acento]} />}>
        {c && (
          <View style={s.cabecera}>
            <Text style={s.descripcion}>{c.descripcion}</Text>
            <View style={s.cabeceraFila}>
              <Anillo valor={c.pct} programado={c.pct_programado} tam={96} grosor={9} pista="rgba(255,255,255,0.12)" color={c.excedente ? Colores.rojoVivo : undefined}>
                <Text style={s.anilloValor}>{pct(c.pct)}</Text>
              </Anillo>
              <View style={{ flex: 1, gap: 6 }}>
                <Text style={s.cifra}>
                  {numero(c.ejecutado)} <Text style={s.cifraUnidad}>de {numero(c.cantidad)} {c.unidad}</Text>
                </Text>
                <Text style={s.linea}>Debería ir en {numero(c.programado)} {c.unidad}</Text>
                <Text style={s.linea}>
                  Faltan {numero(faltan)} {c.unidad}
                </Text>
                <InsigniaEstado real={c.pct} programado={c.pct_programado} excedente={c.excedente} />
              </View>
            </View>
          </View>
        )}

        <View style={s.contenido}>
          {cache !== null && <AvisoSinSenal guardado={cache} />}
          {error ? <Text style={{ color: Colores.rojo }}>{error}</Text> : null}
          {avance && !c ? <Vacio icono="help-circle-outline" texto="Este concepto ya no está en la cotización." /> : null}

          {c && (
            <>
              {!soloConsulta && (
                <Boton titulo="Registrar avance de este concepto" icono="plus" onPress={() => router.push({ pathname: '/avance', params: { ...nav, concepto: String(c.id) } })} />
              )}

              <Tarjeta style={{ gap: Espacio.m }}>
                <Text style={ui.titulo}>Programa</Text>
                <View style={s.datos}>
                  <Dato etiqueta="Inicio" valor={fecha(c.inicio)} />
                  <Dato etiqueta="Término" valor={fecha(c.termino)} />
                  <Dato etiqueta="Duración" valor={`${c.dias} días`} />
                </View>
                <BarraAvance real={c.pct} programado={c.pct_programado} />
                <Text style={ui.textoSuave}>La marca indica dónde debería ir hoy según el programa.</Text>
              </Tarjeta>

              <Tarjeta style={{ gap: Espacio.m }}>
                <Text style={ui.titulo}>Importe</Text>
                <View style={s.datos}>
                  <Dato etiqueta="Precio unitario" valor={money(c.pu)} />
                  <Dato etiqueta="Importe" valor={money(c.importe)} />
                  <Dato etiqueta="Ejecutado" valor={money(c.importe_ejecutado)} color={Colores.verde} />
                </View>
                {c.costo_real > 0 && (
                  <>
                    <View style={s.separador} />
                    <View style={s.datos}>
                      <Dato etiqueta="Costo presupuestado" valor={money(c.costo_presupuesto)} />
                      <Dato etiqueta="Costo real a la fecha" valor={money(c.costo_real)} color={c.diferencia < 0 ? Colores.rojo : undefined} />
                    </View>
                    <Text style={[ui.textoSuave, c.diferencia < 0 && { color: Colores.rojo }]}>
                      {c.diferencia >= 0
                        ? `Quedan ${money(c.diferencia)} entre el importe del concepto y lo gastado.`
                        : `Lo gastado ya supera el importe del concepto por ${money(-c.diferencia)}.`}
                    </Text>
                  </>
                )}
              </Tarjeta>

              <Seccion titulo={`Historial · ${registros.length} registro${registros.length === 1 ? '' : 's'}`} />
              {registros.length === 0 ? <Vacio icono="history" texto="Aún no se registra avance de este concepto." /> : null}
              {registros.map((r) => (
                <FilaRegistro key={r.id} registro={r} nav={nav} sinClave />
              ))}
            </>
          )}
        </View>
      </ScrollView>
    </>
  );
}

function Dato({ etiqueta, valor, color }: { etiqueta: string; valor: string; color?: string }) {
  return (
    <View style={{ flex: 1, minWidth: 90, gap: 2 }}>
      <Text style={ui.textoSuave}>{etiqueta}</Text>
      <Text style={[s.datoValor, color ? { color } : null]} numberOfLines={1} adjustsFontSizeToFit>
        {valor}
      </Text>
    </View>
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
    backgroundImage: Degradado.navy,
  },
  descripcion: { fontSize: 17, lineHeight: 23, fontWeight: '700', color: Colores.blanco },
  cabeceraFila: { flexDirection: 'row', alignItems: 'center', gap: Espacio.l },
  anilloValor: { fontSize: 20, fontWeight: '800', color: Colores.blanco },
  cifra: { fontSize: 24, fontWeight: '800', color: Colores.blanco, letterSpacing: -0.4 },
  cifraUnidad: { fontSize: 14, fontWeight: '600', color: Colores.acentoClaro },
  linea: { fontSize: 13, color: Colores.textoNavy },
  contenido: { padding: Espacio.l, gap: Espacio.m },
  datos: { flexDirection: 'row', flexWrap: 'wrap', gap: Espacio.m },
  datoValor: { fontSize: 16, fontWeight: '800', color: Colores.navy },
  separador: { height: 1, backgroundColor: Colores.linea },
});
