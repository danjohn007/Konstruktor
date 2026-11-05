import { memo } from 'react';
import { StyleSheet, Text, View } from 'react-native';
import Animated, { cubicBezier, useReducedMotion, type CSSAnimationProperties } from 'react-native-reanimated';
import Svg, { Circle, Line, Path, Polyline, Rect, Text as SvgText } from 'react-native-svg';

import { Icono } from '@/components/ui';
import { Colores } from '@/lib/tema';

/**
 * Ciudad que se construye piso por piso, como en el login web: cada edificio es
 * una semana de obra y sus pisos aparecen como barras que crecen; la línea
 * punteada es lo programado y la grúa torre mueve la carga sobre la semana actual.
 * Lo fijo se dibuja en SVG; lo que se mueve son vistas animadas encima.
 *
 * - "intro": se arma una sola vez, rápido y con rebote (pantalla de arranque).
 * - "bucle": se arma y se desarma en ciclos de 10 s (login).
 */

type Modo = 'intro' | 'bucle';

// Coordenadas del dibujo (las mismas del SVG de la web); se escalan al ancho disponible.
const ANCHO = 560;
const ALTO = 350;
const SUELO = 316;
const PISO = 16;
const EDIFICIOS = [4, 6, 5, 9, 8, 11, 13];
const VENTANAS = [7, 21, 35];
const AMBAR = Colores.ambarMarca;

const xEdificio = (i: number) => 40 + i * 64;
const PUNTOS = EDIFICIOS.map((pisos, i) => [xEdificio(i) + 25, SUELO - pisos * PISO - 10]);

let CELOSIA_MASTIL = '';
for (let y = SUELO; y > 70; y -= 16) CELOSIA_MASTIL += `M496 ${y} L508 ${y - 16} `;
let CELOSIA_PLUMA = '';
for (let x = 372; x < 496; x += 12) CELOSIA_PLUMA += `M${x} 66 L${x + 6} 58 L${x + 12} 66 `;

const SALIDA = cubicBezier(0.2, 0.7, 0.2, 1);
const REBOTE = cubicBezier(0.34, 1.45, 0.64, 1);

function animPiso(modo: Modo, i: number, f: number, k: number): CSSAnimationProperties {
  if (modo === 'intro') {
    return {
      animationName: {
        from: { opacity: 0, transform: [{ translateY: -20 * k }] },
        to: { opacity: 1, transform: [{ translateY: 0 }] },
      },
      animationDuration: 520,
      animationDelay: 150 + i * 110 + f * 45,
      animationTimingFunction: REBOTE,
      animationFillMode: 'both',
    };
  }
  return {
    animationName: {
      '0%': { opacity: 0, transform: [{ translateY: -12 * k }] },
      '5%': { opacity: 1, transform: [{ translateY: 0 }] },
      '86%': { opacity: 1, transform: [{ translateY: 0 }] },
      '92%': { opacity: 0, transform: [{ translateY: 0 }] },
      '100%': { opacity: 0, transform: [{ translateY: 0 }] },
    },
    animationDuration: '10s',
    animationDelay: Math.round((i * 0.35 + f * 0.17) * 1000),
    animationIterationCount: 'infinite',
    animationTimingFunction: SALIDA,
    animationFillMode: 'backwards',
  };
}

function animProgramado(modo: Modo, ancho: number): CSSAnimationProperties {
  if (modo === 'intro') {
    return {
      animationName: { from: { width: 0 }, to: { width: ancho } },
      animationDuration: 900,
      animationDelay: 1300,
      animationTimingFunction: SALIDA,
      animationFillMode: 'both',
    };
  }
  return {
    animationName: {
      '0%': { width: 0, opacity: 1 },
      '42%': { width: 0, opacity: 1 },
      '62%': { width: ancho, opacity: 1 },
      '86%': { width: ancho, opacity: 1 },
      '92%': { width: ancho, opacity: 0 },
      '100%': { width: 0, opacity: 0 },
    },
    animationDuration: '10s',
    animationIterationCount: 'infinite',
    animationTimingFunction: 'ease-in-out',
  };
}

function animCarro(modo: Modo, k: number): CSSAnimationProperties {
  const lejos = { transform: [{ translateX: -56 * k }] };
  const sobreObra = { transform: [{ translateX: 0 }] };
  if (modo === 'intro') {
    return {
      animationName: { from: lejos, to: sobreObra },
      animationDuration: 1700,
      animationDelay: 300,
      animationTimingFunction: 'ease-in-out',
      animationFillMode: 'both',
    };
  }
  return {
    animationName: { '0%': lejos, '45%': sobreObra, '60%': sobreObra, '100%': lejos },
    animationDuration: '10s',
    animationIterationCount: 'infinite',
    animationTimingFunction: 'ease-in-out',
  };
}

const BALIZA: CSSAnimationProperties = {
  animationName: {
    '0%': { opacity: 1 },
    '49%': { opacity: 1 },
    '50%': { opacity: 0.15 },
    '99%': { opacity: 0.15 },
    '100%': { opacity: 1 },
  },
  animationDuration: 1600,
  animationIterationCount: 'infinite',
  animationTimingFunction: 'linear',
};

function animKpi(modo: Modo): CSSAnimationProperties {
  const entrada = { from: { opacity: 0 }, to: { opacity: 1 } };
  if (modo === 'intro') {
    return { animationName: entrada, animationDuration: 600, animationDelay: 1900, animationTimingFunction: SALIDA, animationFillMode: 'both' };
  }
  const flotar = {
    '0%': { transform: [{ translateY: 0 }] },
    '50%': { transform: [{ translateY: -8 }] },
    '100%': { transform: [{ translateY: 0 }] },
  };
  return {
    animationName: [entrada, flotar],
    animationDuration: [600, 6000],
    animationDelay: [800, 800],
    animationIterationCount: [1, 'infinite'],
    animationTimingFunction: [SALIDA, 'ease-in-out'],
    animationFillMode: ['both', 'none'],
  };
}

export const EscenaObra = memo(function EscenaObra({ ancho, modo, leyenda = false }: { ancho: number; modo: Modo; leyenda?: boolean }) {
  const reducido = useReducedMotion();
  const k = ancho / ANCHO;
  const alto = ALTO * k;
  // Con "reducir movimiento" activado se muestra la escena terminada, sin animación.
  const anim = (a: CSSAnimationProperties) => (reducido ? null : a);

  return (
    <View style={{ width: ancho }}>
      <View style={{ width: ancho, height: alto }}>
        {/* Lo fijo: suelo, semanas y la estructura de la grúa */}
        <Svg width={ancho} height={alto} viewBox={`0 0 ${ANCHO} ${ALTO}`} style={StyleSheet.absoluteFill}>
          <Line x1={16} y1={SUELO} x2={544} y2={SUELO} stroke="rgba(199,211,227,0.35)" strokeWidth={1.5} />
          {EDIFICIOS.map((_, i) => (
            <SvgText key={i} x={xEdificio(i) + 25} y={SUELO + 20} fill={Colores.textoNavyTenue} fontSize={11} fontWeight="600" textAnchor="middle">
              {`S${i + 1}`}
            </SvgText>
          ))}
          <Path d={`M496 ${SUELO} V58 M508 ${SUELO} V58`} stroke={AMBAR} strokeWidth={2.4} strokeLinecap="round" />
          <Path d={CELOSIA_MASTIL} stroke={AMBAR} strokeWidth={1} opacity={0.75} />
          <Rect x={490} y={48} width={24} height={12} rx={2} fill={AMBAR} />
          <Path d="M372 58 H546 M372 66 H508" stroke={AMBAR} strokeWidth={2.4} strokeLinecap="round" />
          <Path d={CELOSIA_PLUMA} stroke={AMBAR} strokeWidth={1} opacity={0.75} fill="none" />
          <Path d="M502 48 L452 58 M502 48 L540 58" stroke="rgba(245,165,36,0.7)" strokeWidth={1} />
          <Rect x={528} y={58} width={16} height={12} rx={1.5} fill={Colores.ambar} />
        </Svg>

        {/* Pisos: aparecen uno a uno */}
        {EDIFICIOS.map((pisos, i) =>
          Array.from({ length: pisos }, (_, f) => {
            const actual = i === EDIFICIOS.length - 1;
            return (
              <Animated.View
                key={`${i}-${f}`}
                style={[
                  s.losa,
                  actual && s.losaActual,
                  { left: xEdificio(i) * k, top: (SUELO - (f + 1) * PISO + 2) * k, width: 50 * k, height: 14 * k, borderRadius: 1.5 * k },
                  anim(animPiso(modo, i, f, k)),
                ]}>
                {VENTANAS.map((dx, n) => (
                  <View
                    key={dx}
                    style={[s.ventana, (i * 7 + f * 3 + n) % 6 === 0 && s.encendida, { left: dx * k, top: 4 * k, width: 8 * k, height: 6 * k, borderRadius: k }]}
                  />
                ))}
              </Animated.View>
            );
          }),
        )}

        {/* Lo programado: se descubre de izquierda a derecha */}
        <Animated.View style={[s.capa, { width: ancho, height: alto, overflow: 'hidden' }, anim(animProgramado(modo, ancho))]}>
          <Svg width={ancho} height={alto} viewBox={`0 0 ${ANCHO} ${ALTO}`}>
            <Polyline points={PUNTOS.map((p) => p.join(',')).join(' ')} fill="none" stroke="rgba(255,255,255,0.7)" strokeWidth={1.6} strokeDasharray="5 5" />
            {PUNTOS.map(([x, y]) => (
              <Circle key={x} cx={x} cy={y} r={3} fill={Colores.navyProfundo} stroke={Colores.blanco} strokeWidth={1.5} />
            ))}
          </Svg>
        </Animated.View>

        {/* Carro de la grúa con la viga colgando */}
        <Animated.View style={[s.capa, { left: 428 * k, top: 66 * k, width: 36 * k, height: 42 * k }, anim(animCarro(modo, k))]}>
          <Svg width="100%" height="100%" viewBox="428 66 36 42">
            <Rect x={440} y={66} width={12} height={5} rx={1} fill={AMBAR} />
            <Line x1={446} y1={71} x2={446} y2={96} stroke="rgba(255,255,255,0.7)" strokeWidth={1} />
            <Path d="M442 96 h8 l-4 5 z" fill={AMBAR} />
            <Rect x={430} y={101} width={32} height={5} rx={1} fill={Colores.dorado} />
          </Svg>
        </Animated.View>

        <Animated.View style={[s.baliza, { left: 499.5 * k, top: 41.5 * k, width: 5 * k, height: 5 * k }, anim(BALIZA)]} />

        <Animated.View style={[s.kpi, { top: 52 * k }, anim(animKpi(modo))]}>
          <View style={s.kpiIcono}>
            <Icono nombre="chart-line-variant" color={Colores.verde} tam={18} />
          </View>
          <View>
            <Text style={s.kpiTitulo}>Avance real 64%</Text>
            <Text style={s.kpiDetalle}>+3 pts arriba del programa</Text>
          </View>
        </Animated.View>
      </View>

      {leyenda ? (
        <View style={s.leyenda}>
          <View style={s.leyendaItem}>
            <View style={s.cuadro} />
            <Text style={s.leyendaTexto}>Pisos ejecutados por semana</Text>
          </View>
          <View style={s.leyendaItem}>
            <View style={s.linea}>
              {[0, 1, 2].map((n) => (
                <View key={n} style={s.guion} />
              ))}
            </View>
            <Text style={s.leyendaTexto}>Programado</Text>
          </View>
        </View>
      ) : null}
    </View>
  );
});

const s = StyleSheet.create({
  capa: { position: 'absolute', left: 0, top: 0 },
  losa: {
    position: 'absolute',
    backgroundImage: 'linear-gradient(180deg, rgba(61,131,194,0.78) 0%, rgba(47,117,181,0.45) 100%)',
    borderWidth: 0.8,
    borderColor: 'rgba(143,187,226,0.45)',
  },
  losaActual: { backgroundImage: 'none', backgroundColor: 'rgba(143,187,226,0.8)', borderColor: 'rgba(255,255,255,0.6)' },
  ventana: { position: 'absolute', backgroundColor: 'rgba(255,255,255,0.5)' },
  encendida: { backgroundColor: Colores.dorado, boxShadow: '0 0 4px rgba(245,194,107,0.9)' },
  baliza: { position: 'absolute', borderRadius: 999, backgroundColor: Colores.rojoVivo, boxShadow: `0 0 6px ${Colores.rojoVivo}` },
  kpi: {
    position: 'absolute',
    left: 4,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
    paddingVertical: 8,
    paddingHorizontal: 12,
    borderRadius: 14,
    backgroundColor: 'rgba(255,255,255,0.96)',
    boxShadow: '0 10px 28px rgba(3,12,24,0.35)',
  },
  kpiIcono: { width: 32, height: 32, borderRadius: 10, alignItems: 'center', justifyContent: 'center', backgroundColor: Colores.verdeSuave },
  kpiTitulo: { fontSize: 13, fontWeight: '700', color: Colores.navy },
  kpiDetalle: { fontSize: 11, color: Colores.textoSuave },
  leyenda: { flexDirection: 'row', justifyContent: 'center', flexWrap: 'wrap', gap: 16, marginTop: 4 },
  leyendaItem: { flexDirection: 'row', alignItems: 'center', gap: 6 },
  leyendaTexto: { fontSize: 11, color: Colores.textoNavySuave },
  cuadro: { width: 10, height: 10, borderRadius: 2, backgroundColor: Colores.acentoMedio },
  linea: { flexDirection: 'row', gap: 2 },
  guion: { width: 4, height: 2, backgroundColor: 'rgba(255,255,255,0.7)' },
});
