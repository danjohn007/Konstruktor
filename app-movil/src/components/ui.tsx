import MaterialCommunityIcons from '@expo/vector-icons/MaterialCommunityIcons';
import { useEffect, useState, type ComponentProps, type ReactNode } from 'react';
import {
  ActivityIndicator,
  Pressable,
  StyleSheet,
  Text,
  TextInput,
  View,
  type DimensionValue,
  type StyleProp,
  type TextInputProps,
  type ViewStyle,
} from 'react-native';
import Animated, { Easing, useAnimatedProps, useSharedValue, withTiming } from 'react-native-reanimated';
import Svg, { Circle, G } from 'react-native-svg';

import { Colores, Degradado, Espacio, Radio, RadioTarjeta, Sombra } from '@/lib/tema';

export type NombreIcono = ComponentProps<typeof MaterialCommunityIcons>['name'];

export function Icono({ nombre, color = Colores.texto, tam = 20 }: { nombre: NombreIcono; color?: string; tam?: number }) {
  return <MaterialCommunityIcons name={nombre} size={tam} color={color} />;
}

/** Ícono dentro de un cuadro de color suave (KPIs, filas de ajustes, categorías). */
export function IconoCaja({ nombre, color, fondo, tam = 36 }: { nombre: NombreIcono; color: string; fondo: string; tam?: number }) {
  return (
    <View style={[estilos.iconoCaja, { width: tam, height: tam, borderRadius: tam * 0.32, backgroundColor: fondo }]}>
      <Icono nombre={nombre} color={color} tam={tam * 0.52} />
    </View>
  );
}

export function Tarjeta({ children, style }: { children: ReactNode; style?: StyleProp<ViewStyle> }) {
  return <View style={[estilos.tarjeta, style]}>{children}</View>;
}

type BotonProps = {
  titulo: string;
  onPress: () => void;
  icono?: NombreIcono;
  /** El ícono va después del texto (p. ej. una flecha de "continuar"). */
  iconoAlFinal?: boolean;
  tipo?: 'primario' | 'secundario' | 'peligro';
  cargando?: boolean;
  deshabilitado?: boolean;
  style?: StyleProp<ViewStyle>;
};

export function Boton({ titulo, onPress, icono, iconoAlFinal, tipo = 'primario', cargando, deshabilitado, style }: BotonProps) {
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
        tipo === 'peligro' && { borderColor: Colores.rojoSuave, backgroundColor: Colores.rojoFondo },
        pressed && { transform: [{ scale: 0.98 }], opacity: 0.9 },
        deshabilitado && { opacity: 0.5 },
        style,
      ]}>
      {cargando ? <ActivityIndicator color={color} /> : icono && !iconoAlFinal && <Icono nombre={icono} color={color} />}
      <Text style={[estilos.botonTexto, { color }]}>{titulo}</Text>
      {!cargando && icono && iconoAlFinal ? <Icono nombre={icono} color={color} /> : null}
    </Pressable>
  );
}

export function Campo({
  etiqueta,
  ayuda,
  icono,
  accesorio,
  ...props
}: TextInputProps & { etiqueta: string; ayuda?: string; icono?: NombreIcono; accesorio?: ReactNode }) {
  const [enfocado, setEnfocado] = useState(false);
  return (
    <View style={{ gap: Espacio.xs }}>
      <Text style={estilos.etiqueta}>{etiqueta}</Text>
      {/* El borde y el aro de foco van en el contenedor: en Android, cambiarle el estilo al TextInput al enfocarlo le reinicia el relleno. */}
      <View style={[estilos.campoCaja, enfocado && estilos.inputActivo, props.multiline && { alignItems: 'flex-start' }]}>
        {icono ? (
          <View style={estilos.campoLado}>
            <Icono nombre={icono} color={enfocado ? Colores.acento : Colores.textoTenue} />
          </View>
        ) : null}
        <TextInput
          placeholderTextColor={Colores.textoTenue}
          {...props}
          onFocus={(e) => {
            setEnfocado(true);
            props.onFocus?.(e);
          }}
          onBlur={(e) => {
            setEnfocado(false);
            props.onBlur?.(e);
          }}
          style={[estilos.campoTexto, !icono && { paddingLeft: Espacio.m }, !accesorio && { paddingRight: Espacio.m }, props.multiline && { minHeight: 84, textAlignVertical: 'top' }, props.style]}
        />
        {accesorio ? <View style={estilos.campoLado}>{accesorio}</View> : null}
      </View>
      {ayuda ? <Text style={estilos.ayuda}>{ayuda}</Text> : null}
    </View>
  );
}

/** Opciones en "chips" (categoría, forma de pago, filtros). */
export function Opciones<T extends string>({
  opciones,
  valor,
  onCambio,
  etiqueta,
}: {
  opciones: readonly { valor: T; texto: string; icono?: NombreIcono; cuenta?: number }[];
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
          const color = activo ? Colores.blanco : Colores.texto;
          return (
            <Pressable key={o.valor} onPress={() => onCambio(o.valor)} style={[estilos.chip, activo && estilos.chipActivo]} accessibilityState={{ selected: activo }}>
              {o.icono ? <Icono nombre={o.icono} color={activo ? Colores.blanco : Colores.textoSuave} tam={16} /> : null}
              <Text style={[estilos.chipTexto, { color }]}>{o.texto}</Text>
              {o.cuenta !== undefined ? <Text style={[estilos.chipCuenta, activo && { color: Colores.acentoSuave }]}>{o.cuenta}</Text> : null}
            </Pressable>
          );
        })}
      </View>
    </View>
  );
}

/** Pestañas con un indicador que se desliza a la elegida. */
export function Segmentos<T extends string>({ opciones, valor, onCambio }: { opciones: readonly { valor: T; texto: string; icono?: NombreIcono }[]; valor: T; onCambio: (v: T) => void }) {
  const [ancho, setAncho] = useState(0);
  const indice = Math.max(0, opciones.findIndex((o) => o.valor === valor));
  const anchoPestana = ancho > 0 ? (ancho - 8) / opciones.length : 0;
  return (
    <View style={estilos.segmentos} onLayout={(e) => setAncho(e.nativeEvent.layout.width)} accessibilityRole="tablist">
      {anchoPestana > 0 ? (
        <Animated.View
          style={[
            estilos.segmentoIndicador,
            { width: anchoPestana, transform: [{ translateX: indice * anchoPestana }] },
            { transitionProperty: 'transform', transitionDuration: 280, transitionTimingFunction: 'ease-out' },
          ]}
        />
      ) : null}
      {opciones.map((o) => {
        const activo = o.valor === valor;
        return (
          <Pressable key={o.valor} style={estilos.segmento} onPress={() => onCambio(o.valor)} accessibilityRole="tab" accessibilityState={{ selected: activo }}>
            {o.icono ? <Icono nombre={o.icono} tam={16} color={activo ? Colores.navy : Colores.textoSuave} /> : null}
            <Text style={[estilos.segmentoTexto, activo && { color: Colores.navy }]}>{o.texto}</Text>
          </Pressable>
        );
      })}
    </View>
  );
}

const CirculoAnimado = Animated.createAnimatedComponent(Circle);

/** Anillo de avance: se llena al aparecer; el punto marca dónde debería ir según el programa. */
export function Anillo({
  valor,
  programado,
  tam = 56,
  grosor = 6,
  color,
  pista = Colores.borde,
  children,
}: {
  valor: number;
  programado?: number;
  tam?: number;
  grosor?: number;
  color?: string;
  pista?: string;
  children?: ReactNode;
}) {
  const r = (tam - grosor) / 2;
  const circ = 2 * Math.PI * r;
  const real = Math.min(1, Math.max(0, valor || 0));
  const atrasado = programado !== undefined && real + 0.0001 < programado;
  const trazo = color ?? (atrasado ? Colores.ambarMarca : Colores.verde);
  const progreso = useSharedValue(0);

  useEffect(() => {
    progreso.set(withTiming(real, { duration: 900, easing: Easing.out(Easing.cubic) }));
  }, [progreso, real]);

  const props = useAnimatedProps(() => ({ strokeDashoffset: circ * (1 - progreso.get()) }));
  const angulo = programado !== undefined ? Math.min(1, Math.max(0, programado)) * 2 * Math.PI - Math.PI / 2 : 0;

  return (
    <View style={{ width: tam, height: tam, alignItems: 'center', justifyContent: 'center' }}>
      <Svg width={tam} height={tam} style={StyleSheet.absoluteFill}>
        <Circle cx={tam / 2} cy={tam / 2} r={r} stroke={pista} strokeWidth={grosor} fill="none" />
        <G rotation={-90} origin={`${tam / 2}, ${tam / 2}`}>
          <CirculoAnimado cx={tam / 2} cy={tam / 2} r={r} stroke={trazo} strokeWidth={grosor} fill="none" strokeLinecap="round" strokeDasharray={`${circ} ${circ}`} animatedProps={props} />
        </G>
        {programado !== undefined && programado > 0 ? (
          <Circle cx={tam / 2 + r * Math.cos(angulo)} cy={tam / 2 + r * Math.sin(angulo)} r={grosor * 0.55} fill={Colores.blanco} stroke={Colores.navy} strokeWidth={1.5} />
        ) : null}
      </Svg>
      {children}
    </View>
  );
}

/** Barra de avance con una marca donde debería ir según el programa. */
export function BarraAvance({ real, programado, alto = 8, color }: { real: number; programado?: number; alto?: number; color?: string }) {
  const atrasado = programado !== undefined && real + 0.0001 < programado;
  return (
    <View style={[estilos.barraFondo, { height: alto }]}>
      <View
        style={[
          estilos.barra,
          { width: `${Math.min(100, Math.max(0, real * 100))}%`, backgroundColor: color ?? (atrasado ? Colores.ambarMarca : Colores.verde) },
        ]}
      />
      {programado !== undefined && programado > 0 && <View style={[estilos.marca, { left: `${Math.min(100, programado * 100)}%`, height: alto + 6 }]} />}
    </View>
  );
}

export function Insignia({ texto, color = Colores.acento, fondo = Colores.acentoSuave, icono }: { texto: string; color?: string; fondo?: string; icono?: NombreIcono }) {
  return (
    <View style={[estilos.insignia, { backgroundColor: fondo }]}>
      {icono ? <Icono nombre={icono} color={color} tam={12} /> : <View style={[estilos.insigniaPunto, { backgroundColor: color }]} />}
      <Text style={[estilos.insigniaTexto, { color }]}>{texto}</Text>
    </View>
  );
}

/** Estado de una obra o concepto según su avance contra el programa. */
export function InsigniaEstado({ real, programado, excedente }: { real: number; programado: number; excedente?: boolean }) {
  if (excedente) return <Insignia texto="Excedente" color={Colores.rojo} fondo={Colores.rojoSuave} />;
  if (real >= 1) return <Insignia texto="Terminado" color={Colores.acento} fondo={Colores.acentoSuave} />;
  if (real + 0.0001 < programado) return <Insignia texto="Atrasada" color={Colores.ambar} fondo={Colores.ambarSuave} />;
  return <Insignia texto="Al corriente" color={Colores.verde} fondo={Colores.verdeSuave} />;
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

export function Vacio({ icono, texto, accion }: { icono: NombreIcono; texto: string; accion?: ReactNode }) {
  return (
    <View style={estilos.vacio}>
      <View style={estilos.vacioIcono}>
        <Icono nombre={icono} color={Colores.acento} tam={30} />
      </View>
      <Text style={estilos.vacioTexto}>{texto}</Text>
      {accion}
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

/** Indicador con ícono: etiqueta arriba, valor grande abajo. */
export function Kpi({
  icono,
  etiqueta,
  valor,
  detalle,
  color = Colores.acento,
  fondo = Colores.acentoSuave,
  style,
}: {
  icono: NombreIcono;
  etiqueta: string;
  valor: string;
  detalle?: string;
  color?: string;
  fondo?: string;
  style?: StyleProp<ViewStyle>;
}) {
  return (
    <View style={[estilos.kpi, style]}>
      <IconoCaja nombre={icono} color={color} fondo={fondo} tam={34} />
      <Text style={estilos.kpiEtiqueta} numberOfLines={1}>
        {etiqueta}
      </Text>
      <Text style={estilos.kpiValor} numberOfLines={1} adjustsFontSizeToFit>
        {valor}
      </Text>
      {detalle ? (
        <Text style={estilos.kpiDetalle} numberOfLines={1}>
          {detalle}
        </Text>
      ) : null}
    </View>
  );
}

/** Título de sección con una acción opcional a la derecha. */
export function Seccion({ titulo, accion, onAccion }: { titulo: string; accion?: string; onAccion?: () => void }) {
  return (
    <View style={estilos.seccion}>
      <Text style={estilos.seccionTitulo}>{titulo}</Text>
      {accion && onAccion ? (
        <Pressable onPress={onAccion} hitSlop={10}>
          <Text style={estilos.seccionAccion}>{accion}</Text>
        </Pressable>
      ) : null}
    </View>
  );
}

export function iniciales(nombre?: string | null): string {
  const partes = (nombre ?? '').trim().split(/\s+/).filter(Boolean);
  return ((partes[0]?.[0] ?? '') + (partes.length > 1 ? partes[partes.length - 1][0] : '')).toUpperCase() || '?';
}

export function Avatar({ nombre, tam = 40 }: { nombre?: string | null; tam?: number }) {
  return (
    <View style={[estilos.avatar, { width: tam, height: tam, borderRadius: tam / 2 }]}>
      <Text style={[estilos.avatarTexto, { fontSize: tam * 0.38 }]}>{iniciales(nombre)}</Text>
    </View>
  );
}

const PULSO = {
  animationName: { from: { opacity: 1 }, to: { opacity: 0.45 } },
  animationDuration: 850,
  animationIterationCount: 'infinite',
  animationDirection: 'alternate',
  animationTimingFunction: 'ease-in-out',
} as const;

/** Bloque gris que late mientras carga el contenido real. */
export function Esqueleto({ alto = 14, ancho = '100%', radio = 8, style }: { alto?: number; ancho?: DimensionValue; radio?: number; style?: StyleProp<ViewStyle> }) {
  return <Animated.View style={[{ height: alto, width: ancho, borderRadius: radio, backgroundColor: Colores.pista }, PULSO, style]} />;
}

/** Tarjeta de relleno con la forma de una tarjeta de obra. */
export function TarjetaEsqueleto() {
  return (
    <Tarjeta style={{ gap: Espacio.m }}>
      <View style={{ flexDirection: 'row', gap: Espacio.m, alignItems: 'center' }}>
        <Esqueleto alto={56} ancho={56} radio={28} />
        <View style={{ flex: 1, gap: Espacio.s }}>
          <Esqueleto alto={12} ancho="40%" />
          <Esqueleto alto={16} ancho="80%" />
          <Esqueleto alto={12} ancho="55%" />
        </View>
      </View>
      <Esqueleto alto={8} />
    </Tarjeta>
  );
}

export const estilos = StyleSheet.create({
  tarjeta: {
    backgroundColor: Colores.blanco,
    borderRadius: RadioTarjeta,
    padding: Espacio.l,
    gap: Espacio.s,
    borderWidth: 1,
    borderColor: Colores.linea,
    boxShadow: Sombra.tarjeta,
  },
  iconoCaja: { alignItems: 'center', justifyContent: 'center' },
  boton: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: Espacio.s,
    minHeight: 50,
    paddingHorizontal: Espacio.l,
    borderRadius: 14,
  },
  botonPrimario: { backgroundImage: Degradado.primario, boxShadow: '0 6px 16px rgba(47,117,181,0.28)' },
  botonSecundario: { backgroundColor: Colores.blanco, borderWidth: 1, borderColor: Colores.acentoBorde },
  botonTexto: { fontSize: 16, fontWeight: '700' },
  etiqueta: { fontSize: 13, fontWeight: '600', color: Colores.textoSuave },
  ayuda: { fontSize: 12, color: Colores.textoSuave },
  input: {
    backgroundColor: Colores.blanco,
    borderWidth: 1,
    borderColor: Colores.borde,
    borderRadius: Radio,
    paddingHorizontal: Espacio.m,
    paddingVertical: 13,
    fontSize: 16,
    color: Colores.texto,
  },
  inputActivo: { borderColor: Colores.acento, boxShadow: '0 0 0 3px rgba(47,117,181,0.14)' },
  campoCaja: { flexDirection: 'row', alignItems: 'center', backgroundColor: Colores.blanco, borderWidth: 1, borderColor: Colores.borde, borderRadius: Radio },
  campoTexto: { flex: 1, paddingVertical: 13, fontSize: 16, color: Colores.texto },
  campoLado: { width: 44, alignSelf: 'stretch', alignItems: 'center', justifyContent: 'center' },
  opciones: { flexDirection: 'row', flexWrap: 'wrap', gap: Espacio.s },
  chip: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    paddingHorizontal: 14,
    paddingVertical: 9,
    borderRadius: 999,
    borderWidth: 1,
    borderColor: Colores.borde,
    backgroundColor: Colores.blanco,
  },
  chipActivo: { backgroundColor: Colores.navy, borderColor: Colores.navy },
  chipTexto: { fontSize: 14, fontWeight: '600' },
  chipCuenta: { fontSize: 12, fontWeight: '700', color: Colores.textoTenue },
  segmentos: { flexDirection: 'row', padding: 4, borderRadius: 14, backgroundColor: Colores.pista },
  segmentoIndicador: { position: 'absolute', top: 4, bottom: 4, left: 4, borderRadius: 10, backgroundColor: Colores.blanco, boxShadow: '0 1px 3px rgba(16,24,40,0.12)' },
  segmento: { flex: 1, flexDirection: 'row', alignItems: 'center', justifyContent: 'center', gap: 5, paddingVertical: 9 },
  segmentoTexto: { fontSize: 13, fontWeight: '700', color: Colores.textoSuave },
  barraFondo: { backgroundColor: Colores.pista, borderRadius: 999, justifyContent: 'center' },
  barra: { position: 'absolute', left: 0, top: 0, bottom: 0, borderRadius: 999 },
  marca: { position: 'absolute', width: 2, marginLeft: -1, borderRadius: 1, backgroundColor: Colores.navy },
  insignia: { flexDirection: 'row', alignItems: 'center', gap: 5, alignSelf: 'flex-start', paddingHorizontal: 8, paddingVertical: 3, borderRadius: 999 },
  insigniaPunto: { width: 6, height: 6, borderRadius: 3 },
  insigniaTexto: { fontSize: 12, fontWeight: '700' },
  aviso: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: Espacio.s,
    backgroundColor: Colores.ambarSuave,
    padding: Espacio.m,
    borderRadius: 14,
  },
  avisoTexto: { flex: 1, color: Colores.ambar, fontSize: 13 },
  vacio: { alignItems: 'center', gap: Espacio.m, padding: Espacio.xl },
  vacioIcono: { width: 64, height: 64, borderRadius: 32, alignItems: 'center', justifyContent: 'center', backgroundColor: Colores.acentoSuave },
  vacioTexto: { color: Colores.textoSuave, textAlign: 'center', fontSize: 15, lineHeight: 21 },
  dato: { flex: 1, minWidth: 140, gap: 2 },
  datoEtiqueta: { fontSize: 12, color: Colores.textoSuave },
  datoValor: { fontSize: 18, fontWeight: '700', color: Colores.texto },
  kpi: { flex: 1, gap: 4, padding: Espacio.m, borderRadius: 16, backgroundColor: Colores.blanco, borderWidth: 1, borderColor: Colores.linea, boxShadow: Sombra.tarjeta },
  kpiEtiqueta: { marginTop: 4, fontSize: 12, color: Colores.textoSuave },
  kpiValor: { fontSize: 18, fontWeight: '800', color: Colores.navy, letterSpacing: -0.3 },
  kpiDetalle: { fontSize: 11, color: Colores.textoTenue },
  seccion: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', marginTop: Espacio.s },
  seccionTitulo: { fontSize: 17, fontWeight: '800', color: Colores.navy, letterSpacing: -0.2 },
  seccionAccion: { fontSize: 14, fontWeight: '700', color: Colores.acento },
  avatar: { alignItems: 'center', justifyContent: 'center', backgroundImage: Degradado.dorado, borderWidth: 2, borderColor: 'rgba(255,255,255,0.35)' },
  avatarTexto: { fontWeight: '800', color: Colores.navyProfundo },
  titulo: { fontSize: 17, fontWeight: '700', color: Colores.texto },
  texto: { fontSize: 15, color: Colores.texto },
  textoSuave: { fontSize: 13, color: Colores.textoSuave },
});
