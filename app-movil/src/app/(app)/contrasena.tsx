import { router } from 'expo-router';
import { useState } from 'react';
import { Alert, KeyboardAvoidingView, Platform, Pressable, ScrollView, StyleSheet, Text, View } from 'react-native';

import { Boton, Campo, Icono, IconoCaja, estilos as ui } from '@/components/ui';
import { api, SinConexion } from '@/lib/api';
import { Colores, Espacio } from '@/lib/tema';

const MINIMO = 8; // el mismo que pide el servidor

/** Cambia la contraseña del usuario (sirve para la app y para el sistema web). */
export default function CambiarContrasena() {
  const [actual, setActual] = useState('');
  const [nueva, setNueva] = useState('');
  const [confirmar, setConfirmar] = useState('');
  const [ver, setVer] = useState(false);
  const [error, setError] = useState('');
  const [guardando, setGuardando] = useState(false);

  const largoOk = nueva.length >= MINIMO;
  const coinciden = nueva.length > 0 && nueva === confirmar;

  async function guardar() {
    setError('');
    if (!actual) return setError('Escribe tu contraseña actual.');
    if (!largoOk) return setError(`La nueva contraseña debe tener al menos ${MINIMO} caracteres.`);
    if (!coinciden) return setError('La confirmación no coincide con la nueva contraseña.');
    if (nueva === actual) return setError('La nueva contraseña debe ser distinta de la actual.');
    setGuardando(true);
    try {
      await api('/api/mi-password', 'PUT', { actual, nueva });
      Alert.alert('Contraseña actualizada', 'Úsala la próxima vez que entres a la app o al sistema web.');
      router.back();
    } catch (e) {
      setError(e instanceof SinConexion ? 'Necesitas señal para cambiar la contraseña.' : (e as Error).message);
      setGuardando(false);
    }
  }

  const ojo = (
    <Pressable onPress={() => setVer((v) => !v)} hitSlop={10} accessibilityRole="button" accessibilityLabel={ver ? 'Ocultar contraseñas' : 'Mostrar contraseñas'}>
      <Icono nombre={ver ? 'eye-off-outline' : 'eye-outline'} color={Colores.textoSuave} />
    </Pressable>
  );

  return (
    <KeyboardAvoidingView style={{ flex: 1 }} behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
      <ScrollView contentContainerStyle={s.contenido} keyboardShouldPersistTaps="handled">
        <View style={s.intro}>
          <IconoCaja nombre="shield-lock-outline" color={Colores.acento} fondo={Colores.acentoSuave} tam={48} />
          <Text style={[ui.textoSuave, { flex: 1 }]}>Tu sesión en este teléfono sigue abierta. La nueva contraseña aplica también en el sistema web.</Text>
        </View>
        <Campo etiqueta="Contraseña actual" icono="lock-outline" value={actual} onChangeText={setActual} secureTextEntry={!ver} autoComplete="current-password" textContentType="password" accesorio={ojo} />
        <Campo etiqueta="Nueva contraseña" icono="lock-plus-outline" value={nueva} onChangeText={setNueva} secureTextEntry={!ver} autoComplete="new-password" textContentType="newPassword" />
        <Campo etiqueta="Confirmar nueva contraseña" icono="lock-check-outline" value={confirmar} onChangeText={setConfirmar} secureTextEntry={!ver} autoComplete="new-password" onSubmitEditing={guardar} />

        <View style={{ gap: 6 }}>
          <Regla cumple={largoOk} texto={`Al menos ${MINIMO} caracteres`} />
          <Regla cumple={coinciden} texto="La confirmación coincide" />
        </View>

        {error ? (
          <View style={s.error}>
            <Icono nombre="alert-circle-outline" color={Colores.rojo} tam={18} />
            <Text style={s.errorTexto}>{error}</Text>
          </View>
        ) : null}
        <Boton titulo="Guardar contraseña" icono="check" onPress={guardar} cargando={guardando} />
      </ScrollView>
    </KeyboardAvoidingView>
  );
}

function Regla({ cumple, texto }: { cumple: boolean; texto: string }) {
  return (
    <View style={s.regla}>
      <Icono nombre={cumple ? 'check-circle' : 'circle-outline'} color={cumple ? Colores.verde : Colores.textoTenue} tam={18} />
      <Text style={[ui.textoSuave, cumple && { color: Colores.verde }]}>{texto}</Text>
    </View>
  );
}

const s = StyleSheet.create({
  contenido: { padding: Espacio.l, gap: Espacio.l, paddingBottom: Espacio.xl * 2 },
  intro: { flexDirection: 'row', alignItems: 'center', gap: Espacio.m, padding: Espacio.m, borderRadius: 16, backgroundColor: Colores.blanco, borderWidth: 1, borderColor: Colores.linea },
  regla: { flexDirection: 'row', alignItems: 'center', gap: Espacio.s },
  error: { flexDirection: 'row', alignItems: 'center', gap: Espacio.s, padding: Espacio.m, borderRadius: 12, backgroundColor: Colores.rojoSuave },
  errorTexto: { flex: 1, color: Colores.rojo, fontSize: 14 },
});
