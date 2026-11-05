import { useState } from 'react';
import { StyleSheet, Text, View } from 'react-native';
import Animated, { cubicBezier, useReducedMotion } from 'react-native-reanimated';
import Svg, { Circle, Defs, Line, LinearGradient, Path, Stop, Text as SvgText } from 'react-native-svg';

import { hoyISO } from '@/lib/formato';
import { Colores } from '@/lib/tema';
import type { Semana } from '@/lib/tipos';

const ALTO = 170;
const MARGEN = { izq: 30, der: 10, arr: 10, aba: 22 };

/** Índice de la semana de hoy; si la obra ya terminó, la última; si no ha empezado, -1. */
export function semanaActual(semanas: Semana[]): number {
  const hoy = hoyISO();
  const i = semanas.findIndex((w) => w.inicio <= hoy && hoy <= w.fin);
  if (i >= 0) return i;
  return semanas.length && hoy > semanas[semanas.length - 1].fin ? semanas.length - 1 : -1;
}

/**
 * Curva S de la obra: avance acumulado programado (punteado) contra el real (verde),
 * en % del contrato. Se dibuja de izquierda a derecha al aparecer.
 */
export function CurvaAvance({ semanas, contrato }: { semanas: Semana[]; contrato: number }) {
  const [ancho, setAncho] = useState(0);
  const reducido = useReducedMotion();
  const n = semanas.length;
  const actual = semanaActual(semanas);

  if (n < 2) return null;

  const total = Math.max(contrato, semanas[n - 1].programado_acum, ...semanas.map((w) => w.ejecutado_acum)) || 1;
  const x = (i: number) => MARGEN.izq + (i * (ancho - MARGEN.izq - MARGEN.der)) / (n - 1);
  const y = (v: number) => MARGEN.arr + (1 - v / total) * (ALTO - MARGEN.arr - MARGEN.aba);
  const base = ALTO - MARGEN.aba;

  const programado = semanas.map((w, i) => `${i ? 'L' : 'M'}${x(i)} ${y(w.programado_acum)}`).join(' ');
  const reales = semanas.slice(0, actual + 1);
  const real = reales.map((w, i) => `${i ? 'L' : 'M'}${x(i)} ${y(w.ejecutado_acum)}`).join(' ');
  const area = reales.length ? `${real} L${x(reales.length - 1)} ${base} L${x(0)} ${base} Z` : '';
  const etiquetas = [...new Set([0, Math.floor((n - 1) / 2), n - 1])];

  return (
    <View onLayout={(e) => setAncho(e.nativeEvent.layout.width)} style={{ height: ALTO + 26 }}>
      {ancho > 0 && (
        <Animated.View
          style={[
            { width: ancho, height: ALTO, overflow: 'hidden' },
            !reducido && {
              animationName: { from: { width: 0 }, to: { width: ancho } },
              animationDuration: 1100,
              animationTimingFunction: cubicBezier(0.2, 0.7, 0.2, 1),
              animationFillMode: 'both',
            },
          ]}>
          <Svg width={ancho} height={ALTO}>
            <Defs>
              <LinearGradient id="areaReal" x1="0" y1="0" x2="0" y2="1">
                <Stop offset="0" stopColor={Colores.verde} stopOpacity={0.28} />
                <Stop offset="1" stopColor={Colores.verde} stopOpacity={0} />
              </LinearGradient>
            </Defs>
            {[0, 0.25, 0.5, 0.75, 1].map((f) => (
              <Line key={f} x1={MARGEN.izq} x2={ancho - MARGEN.der} y1={y(total * f)} y2={y(total * f)} stroke={Colores.linea} strokeWidth={1} />
            ))}
            {[0, 0.5, 1].map((f) => (
              <SvgText key={f} x={MARGEN.izq - 6} y={y(total * f) + 4} fontSize={10} fill={Colores.textoTenue} textAnchor="end">
                {`${Math.round((f * total * 100) / (contrato || total))}%`}
              </SvgText>
            ))}
            {actual >= 0 && <Line x1={x(actual)} x2={x(actual)} y1={MARGEN.arr} y2={base} stroke={Colores.acentoClaro} strokeWidth={1} strokeDasharray="3 3" />}
            <Path d={programado} fill="none" stroke={Colores.navy} strokeWidth={1.8} strokeDasharray="5 4" opacity={0.55} />
            {area ? <Path d={area} fill="url(#areaReal)" /> : null}
            {real ? <Path d={real} fill="none" stroke={Colores.verde} strokeWidth={2.6} strokeLinejoin="round" strokeLinecap="round" /> : null}
            {actual >= 0 && (
              <Circle cx={x(actual)} cy={y(semanas[actual].ejecutado_acum)} r={4.5} fill={Colores.blanco} stroke={Colores.verde} strokeWidth={2.5} />
            )}
            {etiquetas.map((i) => (
              <SvgText key={i} x={x(i)} y={ALTO - 6} fontSize={10} fill={Colores.textoTenue} textAnchor={i === 0 ? 'start' : i === n - 1 ? 'end' : 'middle'}>
                {`S${semanas[i].numero}`}
              </SvgText>
            ))}
          </Svg>
        </Animated.View>
      )}
      <View style={s.leyenda}>
        <View style={s.item}>
          <View style={[s.linea, { backgroundColor: Colores.verde }]} />
          <Text style={s.texto}>Real</Text>
        </View>
        <View style={s.item}>
          <View style={s.punteada}>
            {[0, 1, 2].map((k) => (
              <View key={k} style={s.guion} />
            ))}
          </View>
          <Text style={s.texto}>Programado</Text>
        </View>
        {actual >= 0 && (
          <View style={s.item}>
            <View style={[s.linea, { width: 2, height: 10, backgroundColor: Colores.acentoClaro }]} />
            <Text style={s.texto}>Hoy</Text>
          </View>
        )}
      </View>
    </View>
  );
}

const s = StyleSheet.create({
  leyenda: { flexDirection: 'row', justifyContent: 'center', gap: 16, marginTop: 6 },
  item: { flexDirection: 'row', alignItems: 'center', gap: 6 },
  linea: { width: 16, height: 3, borderRadius: 2 },
  punteada: { flexDirection: 'row', gap: 2 },
  guion: { width: 4, height: 2, backgroundColor: Colores.navy, opacity: 0.55 },
  texto: { fontSize: 12, color: Colores.textoSuave },
});
