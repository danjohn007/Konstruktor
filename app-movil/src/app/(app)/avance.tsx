import { router, useLocalSearchParams } from 'expo-router';
import { useEffect, useState } from 'react';
import { Alert, KeyboardAvoidingView, Platform, ScrollView, StyleSheet, Text, View } from 'react-native';

import { FotoAdjunta } from '@/components/foto-adjunta';
import { SelectorConcepto } from '@/components/selector-concepto';
import { Anillo, Boton, Campo, Opciones, Tarjeta, estilos as ui } from '@/components/ui';
import { obtener } from '@/lib/api';
import { encolar } from '@/lib/cola';
import { fecha, haceDiasISO, leerNumero, numero, pct } from '@/lib/formato';
import { Colores, Espacio } from '@/lib/tema';
import type { Avance } from '@/lib/tipos';

const DIAS = [
  { valor: '0', texto: 'Hoy', icono: 'calendar-today' },
  { valor: '1', texto: 'Ayer', icono: 'calendar-arrow-left' },
  { valor: '2', texto: 'Antier', icono: 'calendar-blank-outline' },
] as const;

/** Hoja AVANCE: cantidad ejecutada de un concepto en un día, con foto opcional. */
export default function RegistrarAvance() {
  const params = useLocalSearchParams<{ obra: string; titulo?: string; concepto?: string }>();
  const [avance, setAvance] = useState<Avance | null>(null);
  const [conceptoId, setConceptoId] = useState<number | null>(params.concepto ? Number(params.concepto) : null);
  const [dia, setDia] = useState<'0' | '1' | '2'>('0');
  const [cantidad, setCantidad] = useState('');
  const [notas, setNotas] = useState('');
  const [foto, setFoto] = useState<string | null>(null);
  const [guardando, setGuardando] = useState(false);

  useEffect(() => {
    obtener<Avance>(`/api/cotizaciones/${params.obra}/avance`)
      .then((r) => setAvance(r.datos))
      .catch((e: Error) => Alert.alert('No se pudo cargar el catálogo', e.message));
  }, [params.obra]);

  const concepto = avance?.conceptos.find((c) => c.id === conceptoId);
  const opciones = (avance?.conceptos ?? []).map((c) => ({
    id: c.id,
    clave: c.clave,
    descripcion: c.descripcion,
    detalle: `${numero(c.ejecutado)} de ${numero(c.cantidad)} ${c.unidad} ejecutados`,
  }));

  async function guardar() {
    const q = leerNumero(cantidad);
    if (!concepto) return Alert.alert('Falta el concepto', 'Elige qué concepto avanzó.');
    if (q === null || q <= 0) return Alert.alert('Cantidad inválida', `Escribe cuántos ${concepto.unidad} se ejecutaron.`);
    const restante = concepto.cantidad - concepto.ejecutado;
    if (q > restante + 0.0001) {
      const seguir = await new Promise<boolean>((resolve) =>
        Alert.alert('Excede el contrato', `Con esto el concepto pasaría de ${numero(concepto.cantidad)} ${concepto.unidad} contratados. ¿Registrar de todos modos?`, [
          { text: 'Corregir', style: 'cancel', onPress: () => resolve(false) },
          { text: 'Registrar', onPress: () => resolve(true) },
        ]),
      );
      if (!seguir) return;
    }

    setGuardando(true);
    try {
      const fechaAvance = haceDiasISO(Number(dia));
      const estado = await encolar(
        {
          tipo: 'avance',
          cotizacionId: Number(params.obra),
          obra: params.titulo || `Obra ${params.obra}`,
          resumen: `${concepto.clave} · ${numero(q)} ${concepto.unidad} (${fecha(fechaAvance)})`,
          cuerpo: { concepto_id: concepto.id, fecha: fechaAvance, cantidad: q, notas: notas.trim() || null },
        },
        foto,
      );
      if (estado === 'pendiente') {
        Alert.alert('Guardado en el teléfono', 'No hay señal o el servidor no respondió. Se enviará automáticamente al recuperar la conexión.');
      }
      router.back();
    } catch (e) {
      Alert.alert('No se pudo guardar', (e as Error).message);
      setGuardando(false);
    }
  }

  return (
    <KeyboardAvoidingView style={{ flex: 1 }} behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
      <ScrollView contentContainerStyle={s.contenido} keyboardShouldPersistTaps="handled">
        <SelectorConcepto etiqueta="Concepto" conceptos={opciones} valor={conceptoId} onCambio={setConceptoId} />
        {concepto && (
          <Tarjeta style={s.resumen}>
            <Anillo valor={concepto.pct} programado={concepto.pct_programado} tam={58} grosor={6}>
              <Text style={s.anillo}>{pct(concepto.pct)}</Text>
            </Anillo>
            <View style={{ flex: 1, gap: 2 }}>
              <Text style={[ui.texto, { fontWeight: '700' }]}>
                {numero(concepto.ejecutado)} de {numero(concepto.cantidad)} {concepto.unidad}
              </Text>
              <Text style={ui.textoSuave}>
                Faltan {numero(Math.max(0, concepto.cantidad - concepto.ejecutado))} · debería ir en {numero(concepto.programado)}
              </Text>
            </View>
          </Tarjeta>
        )}
        <Opciones etiqueta="Fecha" opciones={DIAS} valor={dia} onCambio={setDia} />
        <Campo
          etiqueta={`Cantidad ejecutada${concepto ? ` (${concepto.unidad})` : ''}`}
          value={cantidad}
          onChangeText={setCantidad}
          keyboardType="decimal-pad"
          placeholder="0.00"
        />
        <Campo etiqueta="Notas (opcional)" value={notas} onChangeText={setNotas} multiline maxLength={255} placeholder="Ej. eje 3 de C a E" />
        <FotoAdjunta etiqueta="Foto del avance (opcional)" uri={foto} onCambio={setFoto} />
        <View style={{ height: Espacio.s }} />
        <Boton titulo="Guardar avance" icono="check" onPress={guardar} cargando={guardando} deshabilitado={!avance} />
        <Text style={[ui.textoSuave, { textAlign: 'center', color: Colores.textoSuave }]}>Si no hay señal se guarda y se envía después.</Text>
      </ScrollView>
    </KeyboardAvoidingView>
  );
}

const s = StyleSheet.create({
  contenido: { padding: Espacio.l, gap: Espacio.l, paddingBottom: Espacio.xl * 2 },
  resumen: { flexDirection: 'row', alignItems: 'center', gap: Espacio.m },
  anillo: { fontSize: 12, fontWeight: '800', color: Colores.navy },
});
