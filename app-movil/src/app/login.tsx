import { Image } from 'expo-image';
import { useState } from 'react';
import { KeyboardAvoidingView, Platform, Pressable, ScrollView, StyleSheet, Text, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { Boton, Campo, Tarjeta, estilos as ui } from '@/components/ui';
import { SERVIDOR_POR_DEFECTO, SinConexion } from '@/lib/api';
import { useSesion } from '@/lib/sesion';
import { Colores, Espacio } from '@/lib/tema';

export default function Login() {
  const { entrar, servidor: servidorGuardado } = useSesion();
  const [servidor, setServidor] = useState(servidorGuardado || SERVIDOR_POR_DEFECTO);
  const [verServidor, setVerServidor] = useState(!(servidorGuardado || SERVIDOR_POR_DEFECTO));
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
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
    <SafeAreaView style={s.fondo}>
      <KeyboardAvoidingView style={{ flex: 1 }} behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
        <ScrollView contentContainerStyle={s.contenido} keyboardShouldPersistTaps="handled">
          <View style={s.logoCaja}>
            <Image source={require('@/assets/images/logo.png')} style={s.logo} contentFit="contain" accessibilityLabel="Konstruktor" />
          </View>
          <Text style={s.subtitulo}>Control de obra en campo</Text>

          <Tarjeta style={{ gap: Espacio.l }}>
            {verServidor ? (
              <Campo
                etiqueta="Servidor"
                value={servidor}
                onChangeText={setServidor}
                placeholder="https://tu-dominio.com"
                autoCapitalize="none"
                autoCorrect={false}
                keyboardType="url"
                ayuda="La dirección del sistema web de Konstruktor."
              />
            ) : null}
            <Campo etiqueta="Correo" value={email} onChangeText={setEmail} autoCapitalize="none" autoCorrect={false} keyboardType="email-address" autoComplete="email" textContentType="username" />
            <Campo etiqueta="Contraseña" value={password} onChangeText={setPassword} secureTextEntry autoComplete="current-password" textContentType="password" onSubmitEditing={enviar} />
            {error ? <Text style={s.error}>{error}</Text> : null}
            <Boton titulo="Entrar" onPress={enviar} cargando={cargando} />
            {!verServidor ? (
              <Pressable onPress={() => setVerServidor(true)} hitSlop={8}>
                <Text style={[ui.textoSuave, { textAlign: 'center' }]}>Cambiar servidor</Text>
              </Pressable>
            ) : null}
          </Tarjeta>
          <Text style={[ui.textoSuave, s.pie]}>Usa el mismo correo y contraseña del sistema web.</Text>
        </ScrollView>
      </KeyboardAvoidingView>
    </SafeAreaView>
  );
}

const s = StyleSheet.create({
  fondo: { flex: 1, backgroundColor: Colores.navy },
  contenido: { flexGrow: 1, justifyContent: 'center', padding: Espacio.xl, gap: Espacio.l },
  logoCaja: { backgroundColor: Colores.blanco, borderRadius: 16, padding: Espacio.m, alignSelf: 'center' },
  logo: { width: 240, height: 93 },
  subtitulo: { color: 'rgba(255,255,255,0.75)', textAlign: 'center', fontSize: 15 },
  error: { color: Colores.rojo, fontSize: 14 },
  pie: { textAlign: 'center', color: 'rgba(255,255,255,0.6)' },
});
