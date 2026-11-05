import { Image } from 'expo-image';
import * as SplashScreen from 'expo-splash-screen';
import { useEffect, useEffectEvent } from 'react';
import { Pressable, StyleSheet, Text, useWindowDimensions, View } from 'react-native';
import Animated, { cubicBezier, FadeOut, useReducedMotion, type CSSAnimationProperties } from 'react-native-reanimated';
import { useSafeAreaInsets } from 'react-native-safe-area-context';

import { FondoMarca } from '@/components/fondo-marca';
import { Colores, Degradado } from '@/lib/tema';

/**
 * Presentación al abrir la app: el logo aparece sobre el fondo de marca y debajo el lema.
 * Arranca sobre el mismo navy del splash nativo, así que el paso es invisible.
 * Se salta con un toque.
 */

const DURACION = 2200;
const BARRA = 120;

const SALIDA = cubicBezier(0.2, 0.7, 0.2, 1);

function aparecer(delay: number, dy = 14, escala = 1): CSSAnimationProperties {
  return {
    animationName: {
      from: { opacity: 0, transform: [{ translateY: dy }, { scale: escala }] },
      to: { opacity: 1, transform: [{ translateY: 0 }, { scale: 1 }] },
    },
    animationDuration: 700,
    animationDelay: delay,
    animationTimingFunction: SALIDA,
    animationFillMode: 'both',
  };
}

export function Intro({ onFin }: { onFin: () => void }) {
  const reducido = useReducedMotion();
  const { width } = useWindowDimensions();
  const insets = useSafeAreaInsets();
  const terminar = useEffectEvent(onFin);
  const anim = (a: CSSAnimationProperties) => (reducido ? null : a);

  useEffect(() => {
    const t = setTimeout(() => terminar(), reducido ? 1200 : DURACION);
    return () => clearTimeout(t);
  }, [reducido]);

  const carga: CSSAnimationProperties = {
    animationName: { from: { width: 0 }, to: { width: BARRA } },
    animationDuration: DURACION - 300,
    animationDelay: 150,
    animationTimingFunction: 'ease-in-out',
    animationFillMode: 'both',
  };

  return (
    <Animated.View exiting={FadeOut.duration(450)} style={s.raiz} onLayout={() => SplashScreen.hideAsync().catch(() => {})}>
      <Pressable style={StyleSheet.absoluteFill} onPress={onFin} accessibilityRole="button" accessibilityLabel="Saltar presentación">
        <FondoMarca />

        <View style={[s.centro, { paddingTop: insets.top, paddingBottom: insets.bottom + 48 }]}>
          <Animated.View style={[s.tarjeta, anim(aparecer(150, 10, 0.94))]}>
            <Image source={require('@/assets/images/logo.png')} style={s.logo} contentFit="contain" accessibilityLabel="Konstruktor" />
          </Animated.View>
          <Animated.View style={anim(aparecer(650))}>
            <Text style={s.lema}>
              Del presupuesto a la entrega, <Text style={s.lemaAcento}>piso por piso.</Text>
            </Text>
          </Animated.View>
        </View>

        <View style={[s.barraFondo, { bottom: insets.bottom + 32, left: (width - BARRA) / 2 }]}>
          <Animated.View style={[s.barra, reducido ? { width: BARRA } : carga]} />
        </View>
      </Pressable>
    </Animated.View>
  );
}

const s = StyleSheet.create({
  raiz: { position: 'absolute', top: 0, right: 0, bottom: 0, left: 0, backgroundColor: Colores.navy, zIndex: 10 },
  centro: { flex: 1, alignItems: 'center', justifyContent: 'center', paddingHorizontal: 32, gap: 24 },
  tarjeta: {
    width: 236,
    height: 100,
    alignItems: 'center',
    justifyContent: 'center',
    borderRadius: 20,
    backgroundColor: Colores.blanco,
    boxShadow: '0 14px 44px rgba(47,117,181,0.45)',
  },
  logo: { width: 200, height: 77 },
  lema: { fontSize: 15, lineHeight: 22, fontWeight: '600', textAlign: 'center', color: Colores.textoNavy },
  lemaAcento: { color: Colores.dorado },
  barraFondo: { position: 'absolute', width: BARRA, height: 3, borderRadius: 2, overflow: 'hidden', backgroundColor: 'rgba(255,255,255,0.14)' },
  barra: { height: 3, borderRadius: 2, backgroundImage: Degradado.carga },
});
