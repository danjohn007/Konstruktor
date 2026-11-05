import { useNetInfo } from '@react-native-community/netinfo';
import * as Application from 'expo-application';
import Constants from 'expo-constants';
import { router, useFocusEffect } from 'expo-router';
import { useCallback, useEffect, useState, type ReactNode } from 'react';
import { ActivityIndicator, Alert, Linking, Pressable, ScrollView, StyleSheet, Switch, Text, View } from 'react-native';

import { Avatar, Boton, Icono, IconoCaja, Insignia, estilos as ui, type NombreIcono } from '@/components/ui';
import { ApiError, borrarCache, contarCache, descargarParaSinSenal, servidorActual, SinConexion } from '@/lib/api';
import { sincronizar, usePendientes } from '@/lib/cola';
import { guardarVerIntro, verIntro } from '@/lib/preferencias';
import { useSesion } from '@/lib/sesion';
import { Colores, Degradado, Espacio, Sombra } from '@/lib/tema';

/** WhatsApp de la oficina (el mismo de la landing). 52 + 10 dígitos. */
const WHATSAPP_OFICINA = '524424499749';

const ROL: Record<string, string> = {
  Admin: 'Administrador',
  Cotizador: 'Cotizador',
  Ingeniero: 'Ingeniero residente',
};

export default function Ajustes() {
  const { usuario, salir } = useSesion();
  const pendientes = usePendientes();
  const red = useNetInfo();
  const [guardadas, setGuardadas] = useState<number | null>(null);
  const [intro, setIntro] = useState(true);
  const [enviando, setEnviando] = useState(false);
  const [descarga, setDescarga] = useState<{ hechas: number; total: number } | null>(null);
  const enLinea = red.isConnected !== false && red.isInternetReachable !== false;
  const conError = pendientes.filter((p) => p.error).length;

  useFocusEffect(
    useCallback(() => {
      contarCache().then(setGuardadas);
    }, []),
  );
  useEffect(() => {
    verIntro().then(setIntro);
  }, []);

  async function enviar() {
    setEnviando(true);
    await sincronizar();
    setEnviando(false);
  }

  async function guardarParaSinSenal() {
    setDescarga({ hechas: 0, total: 0 });
    try {
      const n = await descargarParaSinSenal((hechas, total) => setDescarga({ hechas, total }));
      Alert.alert(
        'Listo para usar sin señal',
        `Se guardaron ${n} obra${n === 1 ? '' : 's'} con su avance y gastos. Las fotos se ven sin señal solo si ya las habías abierto antes.`,
      );
    } catch (e) {
      Alert.alert(
        'No se pudo guardar',
        e instanceof SinConexion ? 'Se necesita señal para descargar. Inténtalo de nuevo con conexión.' : e instanceof ApiError ? e.message : 'Error desconocido',
      );
    } finally {
      setDescarga(null);
      setGuardadas(await contarCache());
    }
  }

  function confirmarBorrar() {
    Alert.alert('Borrar datos descargados', 'Se borra la copia de obras, avances y gastos que se guarda para consultar sin señal. Lo pendiente de enviar no se toca.', [
      { text: 'Cancelar', style: 'cancel' },
      {
        text: 'Borrar',
        style: 'destructive',
        onPress: async () => {
          await borrarCache();
          setGuardadas(0);
        },
      },
    ]);
  }

  function cambiarIntro(v: boolean) {
    setIntro(v);
    guardarVerIntro(v);
  }

  function confirmarSalida() {
    const aviso = pendientes.length ? `\n\nHay ${pendientes.length} registro(s) sin enviar que se perderán.` : '';
    Alert.alert('Cerrar sesión', `¿Salir de la cuenta de ${usuario?.nombre}?${aviso}`, [
      { text: 'Cancelar', style: 'cancel' },
      { text: 'Salir', style: 'destructive', onPress: salir },
    ]);
  }

  // La versión sale de app.json; en Expo Go los datos nativos serían los de Expo Go, así que el número de compilación solo se muestra en el APK.
  const enExpoGo = Constants.executionEnvironment === 'storeClient';
  const version = `${Constants.expoConfig?.version ?? '1.0.0'}${!enExpoGo && Application.nativeBuildVersion ? ` (${Application.nativeBuildVersion})` : ''}`;

  return (
    <ScrollView style={{ backgroundColor: Colores.fondo }} contentContainerStyle={s.contenido}>
      <View style={s.perfil}>
        <Avatar nombre={usuario?.nombre} tam={64} />
        <View style={{ flex: 1, gap: 4 }}>
          <Text style={s.nombre} numberOfLines={1}>
            {usuario?.nombre}
          </Text>
          <Text style={s.correo} numberOfLines={1}>
            {usuario?.email}
          </Text>
          <Insignia texto={ROL[usuario?.rol_nombre ?? ''] ?? usuario?.rol_nombre ?? ''} color={Colores.dorado} fondo="rgba(245,194,107,0.14)" icono="shield-account-outline" />
        </View>
      </View>

      <Grupo titulo="Sincronización">
        <Fila
          icono={enLinea ? 'wifi' : 'wifi-off'}
          color={enLinea ? Colores.verde : Colores.ambar}
          fondo={enLinea ? Colores.verdeSuave : Colores.ambarSuave}
          titulo={enLinea ? 'En línea' : 'Sin conexión'}
          detalle={enLinea ? 'Lo que registres se envía al momento.' : 'Lo que registres se guarda y se envía al volver la señal.'}
        />
        <Fila
          icono="cloud-upload-outline"
          color={conError ? Colores.rojo : Colores.acento}
          fondo={conError ? Colores.rojoSuave : Colores.acentoSuave}
          titulo={pendientes.length ? `${pendientes.length} pendiente${pendientes.length === 1 ? '' : 's'} de enviar` : 'Todo está enviado'}
          detalle={conError ? `${conError} necesita${conError === 1 ? '' : 'n'} revisión` : undefined}
          onPress={pendientes.length ? () => router.push('/pendientes') : undefined}
          derecha={
            pendientes.length ? (
              enviando ? (
                <ActivityIndicator color={Colores.acento} />
              ) : (
                <Pressable onPress={enviar} hitSlop={8} style={s.botonChico}>
                  <Text style={s.botonChicoTexto}>Enviar</Text>
                </Pressable>
              )
            ) : (
              <Icono nombre="check-circle" color={Colores.verde} />
            )
          }
        />
        <Fila
          icono="cloud-download-outline"
          color={Colores.acento}
          fondo={Colores.acentoSuave}
          titulo="Guardar para usar sin señal"
          detalle={
            descarga
              ? descarga.total
                ? `Descargando obra ${descarga.hechas} de ${descarga.total}…`
                : 'Descargando…'
              : 'Obras, avance y gastos. Hazlo antes de ir a una zona sin señal.'
          }
          derecha={
            descarga ? (
              <ActivityIndicator color={Colores.acento} />
            ) : (
              <Pressable onPress={guardarParaSinSenal} disabled={!enLinea} hitSlop={8} style={[s.botonChico, !enLinea && { opacity: 0.45 }]}>
                <Text style={s.botonChicoTexto}>Guardar</Text>
              </Pressable>
            )
          }
        />
        <Fila
          icono="database-outline"
          color={Colores.violeta}
          fondo={Colores.violetaSuave}
          titulo="Datos para usar sin señal"
          detalle={guardadas === null ? '…' : `${guardadas} consulta${guardadas === 1 ? '' : 's'} guardada${guardadas === 1 ? '' : 's'} en el teléfono`}
          derecha={
            guardadas ? (
              <Pressable onPress={confirmarBorrar} hitSlop={8} style={[s.botonChico, { backgroundColor: Colores.rojoSuave }]}>
                <Text style={[s.botonChicoTexto, { color: Colores.rojo }]}>Borrar</Text>
              </Pressable>
            ) : undefined
          }
        />
      </Grupo>

      <Grupo titulo="Cuenta">
        <Fila icono="lock-reset" color={Colores.acento} fondo={Colores.acentoSuave} titulo="Cambiar contraseña" detalle="La misma para la app y el sistema web" onPress={() => router.push('/contrasena')} />
      </Grupo>

      <Grupo titulo="Preferencias">
        <Fila
          icono="movie-open-play-outline"
          color={Colores.ambar}
          fondo={Colores.ambarSuave}
          titulo="Presentación al abrir"
          detalle="El logo de Konstruktor al iniciar la app"
          derecha={<Switch value={intro} onValueChange={cambiarIntro} trackColor={{ true: Colores.acento, false: Colores.borde }} thumbColor={Colores.blanco} />}
        />
      </Grupo>

      <Grupo titulo="Ayuda">
        <Fila
          icono="whatsapp"
          color={Colores.verde}
          fondo={Colores.verdeSuave}
          titulo="Escribir a la oficina"
          detalle="Por WhatsApp"
          onPress={() => Linking.openURL(`https://wa.me/${WHATSAPP_OFICINA}?text=${encodeURIComponent(`Hola, soy ${usuario?.nombre ?? ''} (app de obra).`)}`)}
        />
        <Fila icono="monitor-dashboard" color={Colores.navy} fondo={Colores.navySuave} titulo="Abrir el sistema web" detalle={servidorActual().replace(/^https?:\/\//, '')} onPress={() => Linking.openURL(servidorActual())} />
      </Grupo>

      <Grupo titulo="Acerca de">
        <Fila icono="information-outline" color={Colores.textoSuave} fondo={Colores.fondo} titulo="Konstruktor · Control de obra" detalle={`Versión ${version}`} />
      </Grupo>

      <Boton titulo="Cerrar sesión" icono="logout" tipo="peligro" onPress={confirmarSalida} />
    </ScrollView>
  );
}

function Grupo({ titulo, children }: { titulo: string; children: ReactNode }) {
  const hijos = (Array.isArray(children) ? children : [children]).filter(Boolean);
  return (
    <View style={{ gap: Espacio.s }}>
      <Text style={s.grupoTitulo}>{titulo}</Text>
      <View style={s.grupo}>
        {hijos.map((h, i) => (
          <View key={i} style={i > 0 && s.separador}>
            {h}
          </View>
        ))}
      </View>
    </View>
  );
}

function Fila({
  icono,
  color,
  fondo,
  titulo,
  detalle,
  derecha,
  onPress,
}: {
  icono: NombreIcono;
  color: string;
  fondo: string;
  titulo: string;
  detalle?: string;
  derecha?: ReactNode;
  onPress?: () => void;
}) {
  const contenido = (
    <View style={s.fila}>
      <IconoCaja nombre={icono} color={color} fondo={fondo} />
      <View style={{ flex: 1, gap: 2 }}>
        <Text style={[ui.texto, { fontWeight: '700' }]}>{titulo}</Text>
        {detalle ? <Text style={ui.textoSuave}>{detalle}</Text> : null}
      </View>
      {derecha ?? (onPress ? <Icono nombre="chevron-right" color={Colores.textoTenue} /> : null)}
    </View>
  );
  return onPress ? (
    <Pressable onPress={onPress} style={({ pressed }) => pressed && { backgroundColor: Colores.presionado }}>
      {contenido}
    </Pressable>
  ) : (
    contenido
  );
}

const s = StyleSheet.create({
  contenido: { padding: Espacio.l, gap: Espacio.l, paddingBottom: Espacio.xl * 2 },
  perfil: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: Espacio.l,
    padding: Espacio.l,
    borderRadius: 22,
    backgroundImage: Degradado.encabezado,
    boxShadow: Sombra.flotante,
  },
  nombre: { fontSize: 19, fontWeight: '800', color: Colores.blanco },
  correo: { fontSize: 13, color: Colores.acentoClaro },
  grupoTitulo: { marginLeft: 4, fontSize: 12, fontWeight: '800', color: Colores.textoSuave, textTransform: 'uppercase', letterSpacing: 1 },
  grupo: { borderRadius: 18, backgroundColor: Colores.blanco, borderWidth: 1, borderColor: Colores.linea, overflow: 'hidden', boxShadow: Sombra.tarjeta },
  separador: { borderTopWidth: 1, borderTopColor: Colores.linea },
  fila: { flexDirection: 'row', alignItems: 'center', gap: Espacio.m, padding: Espacio.m, minHeight: 64 },
  botonChico: { paddingHorizontal: 12, paddingVertical: 6, borderRadius: 999, backgroundColor: Colores.acentoSuave },
  botonChicoTexto: { fontSize: 13, fontWeight: '800', color: Colores.acento },
});
