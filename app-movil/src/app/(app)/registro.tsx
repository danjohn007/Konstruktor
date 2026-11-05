import { Image } from 'expo-image';
import { router, useLocalSearchParams } from 'expo-router';
import { useEffect, useState, type ReactNode } from 'react';
import { Pressable, ScrollView, StyleSheet, Text, View } from 'react-native';

import { Boton, Icono, IconoCaja, Tarjeta, Vacio, estilos as ui, type NombreIcono } from '@/components/ui';
import { encabezadosAuth, obtener, servidorActual } from '@/lib/api';
import { diaRelativo, fecha, money, numero, pct } from '@/lib/formato';
import { Colores, Espacio } from '@/lib/tema';
import type { Avance } from '@/lib/tipos';

/** Ficha de un avance ya registrado (solo lectura): qué se hizo, cuánto, cuándo, quién y su foto. */
export default function Registro() {
  const params = useLocalSearchParams<{ obra: string; titulo?: string; id: string }>();
  const nav = { obra: params.obra, titulo: params.titulo ?? '' };
  const [avance, setAvance] = useState<Avance | null>(null);
  const [error, setError] = useState('');
  const [errorFoto, setErrorFoto] = useState(false);

  useEffect(() => {
    obtener<Avance>(`/api/cotizaciones/${params.obra}/avance`)
      .then((r) => setAvance(r.datos))
      .catch((e: Error) => setError(e.message));
  }, [params.obra]);

  const r = avance?.registros.find((x) => x.id === Number(params.id));
  const c = r ? avance?.conceptos.find((x) => x.id === Number(r.concepto_id)) : undefined;

  if (error) return <Vacio icono="alert-circle-outline" texto={error} />;
  if (avance && !r) return <Vacio icono="help-circle-outline" texto="Este registro ya no existe. Quizá se corrigió desde la web." />;
  if (!r) return <Text style={[ui.textoSuave, { textAlign: 'center', padding: Espacio.xl }]}>Cargando…</Text>;

  const uri = `${servidorActual()}/archivos/avances/${r.id}`;

  return (
    <ScrollView style={{ backgroundColor: Colores.fondo }} contentContainerStyle={s.contenido}>
      {r.foto && !errorFoto ? (
        <Pressable onPress={() => router.push({ pathname: '/comprobante', params: { tipo: 'avances', id: String(r.id) } })} accessibilityLabel="Ver foto completa">
          <Image source={{ uri, headers: encabezadosAuth() }} style={s.foto} contentFit="cover" cachePolicy="memory-disk" transition={250} onError={() => setErrorFoto(true)} />
          <View style={s.ampliar}>
            <Icono nombre="arrow-expand" color={Colores.blanco} tam={16} />
          </View>
        </Pressable>
      ) : (
        <View style={[s.foto, s.sinFoto]}>
          <Icono nombre="image-off-outline" color={Colores.textoTenue} tam={34} />
          <Text style={ui.textoSuave}>{r.foto ? 'No se pudo cargar la foto' : 'Este registro no tiene foto'}</Text>
        </View>
      )}

      <View style={{ gap: 4 }}>
        <Text style={s.clave}>{r.concepto_clave}</Text>
        <Text style={s.titulo}>{r.concepto_descripcion}</Text>
        <Text style={ui.textoSuave}>
          Avance del {fecha(r.fecha)} · {diaRelativo(r.fecha)}
        </Text>
      </View>

      <Tarjeta style={{ gap: 0, padding: 0 }}>
        <Fila icono="progress-check" color={Colores.verde} fondo={Colores.verdeSuave} etiqueta="Se ejecutó">
          <Text style={s.valor}>
            {numero(r.cantidad)} {r.unidad}
            {c ? <Text style={ui.textoSuave}>{`  ·  ${money(Number(r.cantidad) * c.pu)}`}</Text> : null}
          </Text>
        </Fila>
        <Fila icono="note-text-outline" color={Colores.acento} fondo={Colores.acentoSuave} etiqueta="Notas" borde>
          <Text style={[ui.texto, !r.notas && { color: Colores.textoTenue }]}>{r.notas || 'Sin notas'}</Text>
        </Fila>
        <Fila icono="account-hard-hat-outline" color={Colores.ambar} fondo={Colores.ambarSuave} etiqueta="Registró" borde>
          <Text style={ui.texto}>{r.usuario || '—'}</Text>
        </Fila>
        {c ? (
          <Fila icono="chart-donut" color={Colores.violeta} fondo={Colores.violetaSuave} etiqueta="Concepto a la fecha" borde>
            <Text style={ui.texto}>
              {numero(c.ejecutado)} de {numero(c.cantidad)} {c.unidad} ({pct(c.pct)})
            </Text>
          </Fila>
        ) : null}
      </Tarjeta>

      {c ? (
        <Boton titulo="Ver historial del concepto" icono="history" tipo="secundario" onPress={() => router.push({ pathname: '/concepto', params: { ...nav, concepto: String(c.id) } })} />
      ) : null}
      <Text style={[ui.textoSuave, { textAlign: 'center' }]}>Para corregir un registro, hazlo desde el sistema web.</Text>
    </ScrollView>
  );
}

function Fila({ icono, color, fondo, etiqueta, borde, children }: { icono: NombreIcono; color: string; fondo: string; etiqueta: string; borde?: boolean; children: ReactNode }) {
  return (
    <View style={[s.fila, borde && s.filaBorde]}>
      <IconoCaja nombre={icono} color={color} fondo={fondo} />
      <View style={{ flex: 1, gap: 2 }}>
        <Text style={ui.textoSuave}>{etiqueta}</Text>
        {children}
      </View>
    </View>
  );
}

const s = StyleSheet.create({
  contenido: { padding: Espacio.l, gap: Espacio.l, paddingBottom: Espacio.xl * 2 },
  foto: { width: '100%', aspectRatio: 4 / 3, borderRadius: 20, backgroundColor: Colores.borde },
  sinFoto: { alignItems: 'center', justifyContent: 'center', gap: Espacio.s },
  ampliar: { position: 'absolute', right: 12, bottom: 12, padding: 8, borderRadius: 999, backgroundColor: 'rgba(8,26,46,0.6)' },
  clave: { fontSize: 13, fontWeight: '800', color: Colores.acento, letterSpacing: 0.2 },
  titulo: { fontSize: 19, lineHeight: 25, fontWeight: '800', color: Colores.navy },
  valor: { fontSize: 17, fontWeight: '800', color: Colores.navy },
  fila: { flexDirection: 'row', alignItems: 'center', gap: Espacio.m, padding: Espacio.m },
  filaBorde: { borderTopWidth: 1, borderTopColor: Colores.linea },
});
