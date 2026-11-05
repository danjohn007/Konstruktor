import { Image } from 'expo-image';
import { useEffect, useState } from 'react';
import { Keyboard, KeyboardAvoidingView, Platform, Pressable, ScrollView, StyleSheet, Text, useWindowDimensions, View } from 'react-native';
import Animated, { FadeIn, FadeInDown, LinearTransition } from 'react-native-reanimated';
import { useSafeAreaInsets } from 'react-native-safe-area-context';

import { EscenaObra } from '@/components/escena-obra';
import { FondoMarca } from '@/components/fondo-marca';
import { Boton, Campo, Icono, estilos as ui } from '@/components/ui';
import { SERVIDOR_POR_DEFECTO, SinConexion } from '@/lib/api';
import { useSesion } from '@/lib/sesion';
import { Colores, Espacio } from '@/lib/tema';

/** Con el teclado abierto se oculta la escena y el formulario sube, para que nunca quede tapado. */
function useTecladoVisible() {
  const [visible, setVisible] = useState(false);
  useEffect(() => {
    const ios = Platform.OS === 'ios';
    const abrir = Keyboard.addListener(ios ? 'keyboardWillShow' : 'keyboardDidShow', () => setVisible(true));
    const cerrar = Keyboard.addListener(ios ? 'keyboardWillHide' : 'keyboardDidHide', () => setVisible(false));
    return () => {
      abrir.remove();
      cerrar.remove();
    };
  }, []);
  return visible;
}

export default function Login() {
  const { entrar, servidor: servidorGuardado } = useSesion();
  const { width } = useWindowDimensions();
  const insets = useSafeAreaInsets();
  const teclado = useTecladoVisible();
  const [servidor, setServidor] = useState(servidorGuardado || SERVIDOR_POR_DEFECTO);
  const [verServidor, setVerServidor] = useState(!(servidorGuardado || SERVIDOR_POR_DEFECTO));
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [verClave, setVerClave] = useState(false);
  const [error, setError] = useState('');
  const [cargando, setCargando] = useState(false);

  async function enviar() {
    setError('');
    if (!email.trim() || !password) {
      setError('Escribe tu correo y tu contraseña');
      return;
    }
    setCargando(true);
    try {
      await entrar(servidor, email.trim(), password);
    } catch (e) {
      setError(e instanceof SinConexion ? 'No se pudo conectar con el servidor. Revisa la señal y la dirección.' : (e as Error).message);
      setCargando(false);
    }
  }

  return (
    <View style={s.fondo}>
      <FondoMarca />
      <KeyboardAvoidingView style={{ flex: 1 }} behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
        <ScrollView contentContainerStyle={[s.contenido, { paddingTop: insets.top + Espacio.l }]} keyboardShouldPersistTaps="handled">
          {!teclado && (
            <Animated.View entering={FadeIn.duration(500)} style={s.marca}>
              <View style={s.cabecera}>
                <View style={s.logoCaja}>
                  <Image source={require('@/assets/images/logo.png')} style={s.logo} contentFit="contain" accessibilityLabel="Konstruktor" />
                </View>
                <View style={s.sello}>
                  <Icono nombre="office-building-outline" color={Colores.dorado} tam={14} />
                  <Text style={s.selloTexto}>Control de obra</Text>
                </View>
              </View>
              <EscenaObra ancho={Math.min(width - Espacio.l * 2, 440)} modo="bucle" leyenda />
              <Text style={s.lema}>
                Del presupuesto a la entrega, <Text style={s.lemaAcento}>piso por piso.</Text>
              </Text>
            </Animated.View>
          )}

          <Animated.View
            entering={FadeInDown.duration(650).delay(120)}
            layout={LinearTransition.duration(250)}
            style={[s.formulario, { paddingBottom: insets.bottom + Espacio.l }, !teclado && { marginTop: 'auto' }]}>
            <View style={{ gap: 2 }}>
              <Text style={s.saludo}>BIENVENIDO</Text>
              <Text style={s.titulo}>Inicia sesión</Text>
              <Text style={ui.textoSuave}>Entra a tus obras, avances y gastos.</Text>
            </View>

            {verServidor ? (
              <Campo
                etiqueta="Servidor"
                icono="server-network"
                value={servidor}
                onChangeText={setServidor}
                placeholder="https://tu-dominio.com"
                autoCapitalize="none"
                autoCorrect={false}
                keyboardType="url"
                ayuda="La dirección del sistema web de Konstruktor."
              />
            ) : null}
            <Campo
              etiqueta="Correo electrónico"
              icono="email-outline"
              value={email}
              onChangeText={setEmail}
              placeholder="tu@correo.com"
              autoCapitalize="none"
              autoCorrect={false}
              keyboardType="email-address"
              autoComplete="email"
              textContentType="username"
            />
            <Campo
              etiqueta="Contraseña"
              icono="lock-outline"
              value={password}
              onChangeText={setPassword}
              placeholder="••••••••"
              secureTextEntry={!verClave}
              autoComplete="current-password"
              textContentType="password"
              onSubmitEditing={enviar}
              accesorio={
                <Pressable onPress={() => setVerClave((v) => !v)} hitSlop={10} accessibilityRole="button" accessibilityLabel={verClave ? 'Ocultar contraseña' : 'Mostrar contraseña'}>
                  <Icono nombre={verClave ? 'eye-off-outline' : 'eye-outline'} color={Colores.textoSuave} />
                </Pressable>
              }
            />
            {error ? (
              <View style={s.error}>
                <Icono nombre="alert-circle-outline" color={Colores.rojo} tam={18} />
                <Text style={s.errorTexto}>{error}</Text>
              </View>
            ) : null}
            <Boton titulo="Entrar" icono="arrow-right" iconoAlFinal onPress={enviar} cargando={cargando} />
            {!verServidor ? (
              <Pressable onPress={() => setVerServidor(true)} hitSlop={8}>
                <Text style={[ui.textoSuave, { textAlign: 'center' }]}>Cambiar servidor</Text>
              </Pressable>
            ) : null}
            <Text style={s.pie}>
              {servidor.startsWith('https://') ? (
                <>
                  <Icono nombre="lock" color={Colores.textoSuave} tam={12} /> Conexión cifrada ·{' '}
                </>
              ) : null}
              Usa el mismo correo y contraseña del sistema web
            </Text>
          </Animated.View>
        </ScrollView>
      </KeyboardAvoidingView>
    </View>
  );
}

const s = StyleSheet.create({
  fondo: { flex: 1, backgroundColor: Colores.navy },
  contenido: { flexGrow: 1 },
  marca: { alignItems: 'center', gap: Espacio.m, paddingHorizontal: Espacio.l, paddingBottom: Espacio.xl },
  cabecera: { alignSelf: 'stretch', flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between' },
  logoCaja: { backgroundColor: Colores.blanco, borderRadius: 12, paddingVertical: 6, paddingHorizontal: 10 },
  logo: { width: 104, height: 40 },
  sello: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    paddingVertical: 6,
    paddingHorizontal: 12,
    borderRadius: 999,
    borderWidth: 1,
    borderColor: 'rgba(255,255,255,0.14)',
    backgroundColor: 'rgba(255,255,255,0.06)',
  },
  selloTexto: { fontSize: 12, fontWeight: '600', color: Colores.textoNavy },
  lema: { alignSelf: 'stretch', fontSize: 24, lineHeight: 30, fontWeight: '800', letterSpacing: -0.5, color: Colores.blanco },
  lemaAcento: { color: Colores.dorado },
  formulario: {
    gap: Espacio.l,
    padding: Espacio.xl,
    borderTopLeftRadius: 28,
    borderTopRightRadius: 28,
    backgroundColor: Colores.blanco,
    boxShadow: '0 -12px 40px rgba(3,12,24,0.35)',
  },
  saludo: { fontSize: 12, fontWeight: '700', letterSpacing: 2.4, color: Colores.acento },
  titulo: { fontSize: 28, fontWeight: '800', letterSpacing: -0.5, color: Colores.navy },
  error: { flexDirection: 'row', alignItems: 'center', gap: Espacio.s, padding: Espacio.m, borderRadius: 10, backgroundColor: Colores.rojoSuave },
  errorTexto: { flex: 1, color: Colores.rojo, fontSize: 14 },
  pie: { fontSize: 12, lineHeight: 18, color: Colores.textoSuave, textAlign: 'center' },
});
