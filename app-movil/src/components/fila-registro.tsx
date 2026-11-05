import { Image } from 'expo-image';
import { router } from 'expo-router';
import { Pressable, StyleSheet, Text, View } from 'react-native';

import { Icono, estilos as ui } from '@/components/ui';
import { encabezadosAuth, servidorActual } from '@/lib/api';
import { diaRelativo, numero } from '@/lib/formato';
import { Colores, Espacio } from '@/lib/tema';
import type { RegistroAvance } from '@/lib/tipos';

/** Un avance registrado (con su miniatura); al tocarlo abre su ficha. */
export function FilaRegistro({ registro: r, nav, sinClave }: { registro: RegistroAvance; nav: { obra: string; titulo: string }; sinClave?: boolean }) {
  return (
    <Pressable
      onPress={() => router.push({ pathname: '/registro', params: { ...nav, id: String(r.id) } })}
      style={({ pressed }) => [s.registro, pressed && { opacity: 0.85 }]}>
      {r.foto ? (
        <Image
          source={{ uri: `${servidorActual()}/archivos/avances/${r.id}`, headers: encabezadosAuth() }}
          style={s.registroFoto}
          contentFit="cover"
          cachePolicy="memory-disk"
          transition={200}
        />
      ) : (
        <View style={[s.registroFoto, s.registroSinFoto]}>
          <Icono nombre="progress-check" color={Colores.acento} />
        </View>
      )}
      <View style={{ flex: 1, gap: 2 }}>
        <Text style={[ui.texto, { fontWeight: '700' }]} numberOfLines={1}>
          {sinClave ? '' : `${r.concepto_clave} · `}
          {numero(r.cantidad)} {r.unidad}
        </Text>
        <Text style={ui.textoSuave} numberOfLines={1}>
          {r.notas || r.concepto_descripcion}
        </Text>
        <Text style={s.registroMeta}>
          {diaRelativo(r.fecha)}
          {r.usuario ? ` · ${r.usuario}` : ''}
        </Text>
      </View>
      <Icono nombre="chevron-right" color={Colores.textoTenue} />
    </Pressable>
  );
}

const s = StyleSheet.create({
  registro: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: Espacio.m,
    padding: Espacio.m,
    borderRadius: 16,
    backgroundColor: Colores.blanco,
    borderWidth: 1,
    borderColor: Colores.linea,
  },
  registroFoto: { width: 52, height: 52, borderRadius: 12, backgroundColor: Colores.borde },
  registroSinFoto: { alignItems: 'center', justifyContent: 'center', backgroundColor: Colores.acentoSuave },
  registroMeta: { fontSize: 12, color: Colores.textoTenue },
});
