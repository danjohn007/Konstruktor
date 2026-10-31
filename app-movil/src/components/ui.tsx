import MaterialCommunityIcons from '@expo/vector-icons/MaterialCommunityIcons';
import type { ComponentProps, ReactNode } from 'react';
import {
  ActivityIndicator,
  Pressable,
  StyleSheet,
  Text,
  TextInput,
  View,
  type StyleProp,
  type TextInputProps,
  type ViewStyle,
} from 'react-native';

import { Colores, Espacio, Radio } from '@/lib/tema';

export type NombreIcono = ComponentProps<typeof MaterialCommunityIcons>['name'];

export function Icono({ nombre, color = Colores.texto, tam = 20 }: { nombre: NombreIcono; color?: string; tam?: number }) {
  return <MaterialCommunityIcons name={nombre} size={tam} color={color} />;
}

export function Tarjeta({ children, style }: { children: ReactNode; style?: StyleProp<ViewStyle> }) {
  return <View style={[estilos.tarjeta, style]}>{children}</View>;
}

type BotonProps = {
  titulo: string;
  onPress: () => void;
  icono?: NombreIcono;
  tipo?: 'primario' | 'secundario' | 'peligro';
  cargando?: boolean;
  deshabilitado?: boolean;
  style?: StyleProp<ViewStyle>;
};

export function Boton({ titulo, onPress, icono, tipo = 'primario', cargando, deshabilitado, style }: BotonProps) {
  const primario = tipo === 'primario';
  const color = primario ? Colores.blanco : tipo === 'peligro' ? Colores.rojo : Colores.acento;
  return (
    <Pressable
      accessibilityRole="button"
      onPress={onPress}
      disabled={deshabilitado || cargando}
      style={({ pressed }) => [
        estilos.boton,
        primario ? estilos.botonPrimario : estilos.botonSecundario,
        tipo === 'peligro' && { borderColor: Colores.rojo },
        (pressed || deshabilitado) && { opacity: 0.6 },
        style,
      ]}>
      {cargando ? <ActivityIndicator color={color} /> : icono && <Icono nombre={icono} color={color} />}
      <Text style={[estilos.botonTexto, { color }]}>{titulo}</Text>
    </Pressable>
  );
}

export function Campo({ etiqueta, ayuda, ...props }: TextInputProps & { etiqueta: string; ayuda?: string }) {
  return (
    <View style={{ gap: Espacio.xs }}>
      <Text style={estilos.etiqueta}>{etiqueta}</Text>
      <TextInput placeholderTextColor={Colores.textoSuave} {...props} style={[estilos.input, props.multiline && { minHeight: 72, textAlignVertical: 'top' }, props.style]} />
      {ayuda ? <Text style={estilos.ayuda}>{ayuda}</Text> : null}
    </View>
  );
}

/** Opciones en "chips" (categoría, forma de pago, pestañas). */
export function Opciones<T extends string>({
  opciones,
  valor,
  onCambio,
  etiqueta,
}: {
  opciones: readonly { valor: T; texto: string }[];
  valor: T | null;
  onCambio: (v: T) => void;
  etiqueta?: string;
}) {
  return (
    <View style={{ gap: Espacio.xs }}>
      {etiqueta ? <Text style={estilos.etiqueta}>{etiqueta}</Text> : null}
      <View style={estilos.opciones}>
        {opciones.map((o) => {
          const activo = o.valor === valor;
          return (
            <Pressable key={o.valor} onPress={() => onCambio(o.valor)} style={[estilos.chip, activo && estilos.chipActivo]} accessibilityState={{ selected: activo }}>
              <Text style={[estilos.chipTexto, activo && { color: Colores.blanco }]}>{o.texto}</Text>
            </Pressable>
          );
        })}
      </View>
    </View>
  );
}

/** Barra de avance con una marca donde debería ir según el programa. */
export function BarraAvance({ real, programado, alto = 10 }: { real: number; programado?: number; alto?: number }) {
  const atrasado = programado !== undefined && real + 0.0001 < programado;
  return (
    <View style={[estilos.barraFondo, { height: alto }]}>
      <View style={[estilos.barra, { width: `${Math.min(100, Math.max(0, real * 100))}%`, backgroundColor: atrasado ? Colores.ambar : Colores.verde }]} />
      {programado !== undefined && programado > 0 && (
        <View style={[estilos.marca, { left: `${Math.min(100, programado * 100)}%`, height: alto + 6 }]} />
      )}
    </View>
  );
}

export function Insignia({ texto, color = Colores.acento, fondo = Colores.acentoSuave }: { texto: string; color?: string; fondo?: string }) {
  return (
    <View style={[estilos.insignia, { backgroundColor: fondo }]}>
      <Text style={[estilos.insigniaTexto, { color }]}>{texto}</Text>
    </View>
  );
}

/** Aviso de que se muestran datos guardados porque no hay señal. */
export function AvisoSinSenal({ guardado }: { guardado?: string }) {
  return (
    <View style={estilos.aviso}>
      <Icono nombre="wifi-off" color={Colores.ambar} tam={18} />
      <Text style={estilos.avisoTexto}>
        Sin conexión. Se muestran los datos guardados{guardado ? ` el ${new Date(guardado).toLocaleString('es-MX', { dateStyle: 'short', timeStyle: 'short' })}` : ''}.
      </Text>
    </View>
  );
}

export function Vacio({ icono, texto }: { icono: NombreIcono; texto: string }) {
  return (
    <View style={estilos.vacio}>
      <Icono nombre={icono} color={Colores.textoSuave} tam={40} />
      <Text style={estilos.vacioTexto}>{texto}</Text>
    </View>
  );
}

export function Dato({ etiqueta, valor, color }: { etiqueta: string; valor: string; color?: string }) {
  return (
    <View style={estilos.dato}>
      <Text style={estilos.datoEtiqueta}>{etiqueta}</Text>
      <Text style={[estilos.datoValor, color ? { color } : null]} numberOfLines={1} adjustsFontSizeToFit>
        {valor}
      </Text>
    </View>
  );
}

export const estilos = StyleSheet.create({
  tarjeta: {
    backgroundColor: Colores.blanco,
    borderRadius: Radio,
    padding: Espacio.l,
    gap: Espacio.s,
    boxShadow: '0 1px 3px rgba(16, 24, 40, 0.08)',
  },
  boton: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: Espacio.s,
    minHeight: 48,
    paddingHorizontal: Espacio.l,
    borderRadius: 10,
  },
  botonPrimario: { backgroundColor: Colores.acento },
  botonSecundario: { backgroundColor: Colores.blanco, borderWidth: 1, borderColor: Colores.acento },
  botonTexto: { fontSize: 16, fontWeight: '600' },
  etiqueta: { fontSize: 13, fontWeight: '600', color: Colores.textoSuave },
  ayuda: { fontSize: 12, color: Colores.textoSuave },
  input: {
    backgroundColor: Colores.blanco,
    borderWidth: 1,
    borderColor: Colores.borde,
    borderRadius: 10,
    paddingHorizontal: Espacio.m,
    paddingVertical: Espacio.m,
    fontSize: 16,
    color: Colores.texto,
  },
  opciones: { flexDirection: 'row', flexWrap: 'wrap', gap: Espacio.s },
  chip: {
    paddingHorizontal: Espacio.m,
    paddingVertical: Espacio.s,
    borderRadius: 999,
    borderWidth: 1,
    borderColor: Colores.borde,
    backgroundColor: Colores.blanco,
  },
  chipActivo: { backgroundColor: Colores.acento, borderColor: Colores.acento },
  chipTexto: { fontSize: 14, color: Colores.texto, fontWeight: '500' },
  barraFondo: { backgroundColor: Colores.borde, borderRadius: 999, justifyContent: 'center' },
  barra: { position: 'absolute', left: 0, top: 0, bottom: 0, borderRadius: 999 },
  marca: { position: 'absolute', width: 2, marginLeft: -1, backgroundColor: Colores.navy },
  insignia: { alignSelf: 'flex-start', paddingHorizontal: Espacio.s, paddingVertical: 2, borderRadius: 6 },
  insigniaTexto: { fontSize: 12, fontWeight: '600' },
  aviso: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: Espacio.s,
    backgroundColor: Colores.ambarSuave,
    padding: Espacio.m,
    borderRadius: 10,
  },
  avisoTexto: { flex: 1, color: Colores.ambar, fontSize: 13 },
  vacio: { alignItems: 'center', gap: Espacio.m, padding: Espacio.xl },
  vacioTexto: { color: Colores.textoSuave, textAlign: 'center', fontSize: 15 },
  dato: { flex: 1, minWidth: 140, gap: 2 },
  datoEtiqueta: { fontSize: 12, color: Colores.textoSuave },
  datoValor: { fontSize: 18, fontWeight: '700', color: Colores.texto },
  titulo: { fontSize: 17, fontWeight: '700', color: Colores.texto },
  texto: { fontSize: 15, color: Colores.texto },
  textoSuave: { fontSize: 13, color: Colores.textoSuave },
});
