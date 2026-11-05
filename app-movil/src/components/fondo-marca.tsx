import { memo } from 'react';
import { StyleSheet, useWindowDimensions, View } from 'react-native';
import Svg, { Path } from 'react-native-svg';

import { Colores } from '@/lib/tema';

const PASO = 32;

/** Fondo navy con dos brillos y una retícula de plano, el mismo del login web. */
export const FondoMarca = memo(function FondoMarca() {
  const { width, height } = useWindowDimensions();
  let d = '';
  for (let x = PASO; x < width; x += PASO) d += `M${x} 0V${height}`;
  for (let y = PASO; y < height; y += PASO) d += `M0 ${y}H${width}`;

  return (
    <View style={[StyleSheet.absoluteFill, s.fondo]}>
      <View style={[s.brillo, s.azul]} />
      <View style={[s.brillo, s.ambar]} />
      <Svg width={width} height={height} style={StyleSheet.absoluteFill}>
        <Path d={d} stroke="rgba(143,187,226,0.07)" strokeWidth={1} />
      </Svg>
    </View>
  );
});

const s = StyleSheet.create({
  fondo: { backgroundColor: Colores.navy, overflow: 'hidden', pointerEvents: 'none' },
  brillo: { position: 'absolute', borderRadius: 999 },
  azul: {
    top: -180,
    right: -160,
    width: 520,
    height: 520,
    backgroundImage: 'radial-gradient(circle, rgba(47,117,181,0.45) 0%, rgba(47,117,181,0) 70%)',
  },
  ambar: {
    bottom: -220,
    left: -200,
    width: 520,
    height: 520,
    backgroundImage: 'radial-gradient(circle, rgba(245,165,36,0.16) 0%, rgba(245,165,36,0) 70%)',
  },
});
