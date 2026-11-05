import { useState } from 'react';
import { StyleSheet, Text, View } from 'react-native';
import Animated, { cubicBezier, useReducedMotion } from 'react-native-reanimated';
import Svg, { Line, Rect, Text as SvgText } from 'react-native-svg';

import { semanaActual } from '@/components/curva-avance';
import { moneyCorto } from '@/lib/formato';
import { Colores } from '@/lib/tema';
import type { Semana } from '@/lib/tipos';

const ALTO = 150;
const MARGEN = { izq: 38, der: 4, arr: 8, aba: 22 };
const MAX_SEMANAS = 12;

/**
 * Cobrado contra gastado de cada semana, hasta la actual (las futuras aún no tienen movimientos).
 * Muestra las últimas 12 para que las barras no se vuelvan hilos.
 */
export function FlujoSemanal({ semanas }: { semanas: Semana[] }) {
  const [ancho, setAncho] = useState(0);
  const reducido = useReducedMotion();
  const actual = semanaActual(semanas);
  const visibles = actual >= 0 ? semanas.slice(Math.max(0, actual + 1 - MAX_SEMANAS), actual + 1) : [];
  const tope = Math.max(0, ...visibles.flatMap((w) => [w.cobrado, w.gastado]));

  if (!visibles.length || tope <= 0) return <Text style={s.vacio}>Aún no hay cobros ni gastos registrados.</Text>;

  const n = visibles.length;
  const columna = (ancho - MARGEN.izq - MARGEN.der) / n;
  const barra = Math.min(14, columna * 0.32);
  const y = (v: number) => MARGEN.arr + (1 - v / tope) * (ALTO - MARGEN.arr - MARGEN.aba);
  const base = ALTO - MARGEN.aba;
  const etiquetas = [...new Set([0, Math.floor((n - 1) / 2), n - 1])];

  return (
    <View onLayout={(e) => setAncho(e.nativeEvent.layout.width)} style={{ height: ALTO + 26 }}>
      {ancho > 0 && (
        <Animated.View
          style={[
            { width: ancho, height: ALTO, overflow: 'hidden' },
            !reducido && {
              animationName: { from: { width: 0 }, to: { width: ancho } },
              animationDuration: 900,
              animationTimingFunction: cubicBezier(0.2, 0.7, 0.2, 1),
              animationFillMode: 'both',
            },
          ]}>
          <Svg width={ancho} height={ALTO}>
            {[0, 0.5, 1].map((f) => (
              <Line key={f} x1={MARGEN.izq} x2={ancho - MARGEN.der} y1={y(tope * f)} y2={y(tope * f)} stroke={Colores.linea} strokeWidth={1} />
            ))}
            {[0.5, 1].map((f) => (
              <SvgText key={f} x={MARGEN.izq - 6} y={y(tope * f) + 4} fontSize={10} fill={Colores.textoTenue} textAnchor="end">
                {moneyCorto(tope * f)}
              </SvgText>
            ))}
            {visibles.map((w, i) => {
              const centro = MARGEN.izq + columna * (i + 0.5);
              return [
                { v: w.cobrado, color: Colores.verde, x: centro - barra - 1 },
                { v: w.gastado, color: Colores.violeta, x: centro + 1 },
              ].map((b, k) =>
                b.v > 0 ? <Rect key={`${w.numero}-${k}`} x={b.x} y={y(b.v)} width={barra} height={base - y(b.v)} rx={Math.min(3, barra / 2)} fill={b.color} /> : null,
              );
            })}
            {etiquetas.map((i) => (
              <SvgText key={i} x={MARGEN.izq + columna * (i + 0.5)} y={ALTO - 6} fontSize={10} fill={Colores.textoTenue} textAnchor="middle">
                {`S${visibles[i].numero}`}
              </SvgText>
            ))}
          </Svg>
        </Animated.View>
      )}
      <View style={s.leyenda}>
        <View style={s.item}>
          <View style={[s.punto, { backgroundColor: Colores.verde }]} />
          <Text style={s.texto}>Cobrado</Text>
        </View>
        <View style={s.item}>
          <View style={[s.punto, { backgroundColor: Colores.violeta }]} />
          <Text style={s.texto}>Gastado</Text>
        </View>
      </View>
    </View>
  );
}

const s = StyleSheet.create({
  leyenda: { flexDirection: 'row', justifyContent: 'center', gap: 16, marginTop: 6 },
  item: { flexDirection: 'row', alignItems: 'center', gap: 6 },
  punto: { width: 10, height: 10, borderRadius: 3 },
  texto: { fontSize: 12, color: Colores.textoSuave },
  vacio: { fontSize: 13, color: Colores.textoSuave, textAlign: 'center', paddingVertical: 12 },
});
