import { router, Stack, useLocalSearchParams } from 'expo-router';
import { useEffect, useState } from 'react';
import { Alert, KeyboardAvoidingView, Platform, ScrollView, StyleSheet, Text, View } from 'react-native';

import { FotoAdjunta } from '@/components/foto-adjunta';
import { SelectorConcepto } from '@/components/selector-concepto';
import { Boton, Campo, Opciones, estilos as ui } from '@/components/ui';
import { obtener } from '@/lib/api';
import { encolar } from '@/lib/cola';
import { haceDiasISO, leerNumero, money } from '@/lib/formato';
import { useSesion } from '@/lib/sesion';
import { Espacio } from '@/lib/tema';
import { CATEGORIAS, FORMAS_PAGO, NO_COSTO, NOMBRE_CATEGORIA, type Control } from '@/lib/tipos';

const DIAS = [
  { valor: '0', texto: 'Hoy' },
  { valor: '1', texto: 'Ayer' },
  { valor: '2', texto: 'Antier' },
] as const;

/** Hoja CONTROL: un gasto (con foto del ticket) o un ingreso de la obra. */
export default function RegistrarMovimiento() {
  const params = useLocalSearchParams<{ obra: string; titulo?: string; tipo: 'egreso' | 'ingreso' }>();
  const tipo = params.tipo === 'ingreso' ? 'ingreso' : 'egreso';
  // El ingeniero solo registra gastos de la obra (sin préstamos); el servidor también lo valida.
  const esIngeniero = useSesion().usuario?.rol_nombre === 'Ingeniero';
  const [control, setControl] = useState<Control | null>(null);
  const [categoria, setCategoria] = useState<string>(tipo === 'egreso' ? 'MAT' : 'ANTICIPO');
  const [dia, setDia] = useState<'0' | '1' | '2'>('0');
  const [monto, setMonto] = useState('');
  const [descripcion, setDescripcion] = useState('');
  const [conceptoId, setConceptoId] = useState<number | null>(null);
  const [proveedor, setProveedor] = useState('');
  const [rfc, setRfc] = useState('');
  const [formaPago, setFormaPago] = useState<string | null>('Efectivo');
  const [referencia, setReferencia] = useState('');
  const [foto, setFoto] = useState<string | null>(null);
  const [guardando, setGuardando] = useState(false);
  const esCosto = tipo === 'egreso' && !NO_COSTO.includes(categoria);

  useEffect(() => {
    if (tipo !== 'egreso') return;
    obtener<Control>(`/api/cotizaciones/${params.obra}/control`)
      .then((r) => setControl(r.datos))
      .catch(() => setControl(null)); // sin catálogo se puede registrar como gasto general
  }, [params.obra, tipo]);

  async function guardar() {
    const m = leerNumero(monto);
    if (m === null || m <= 0) return Alert.alert('Monto inválido', 'Escribe el monto pagado.');
    if (!descripcion.trim()) return Alert.alert('Falta la descripción', 'Ej. "35 varillas 3/8".');
    const r = rfc.trim().toUpperCase();
    if (r && !/^[A-ZÑ&]{3,4}\d{6}[A-Z\d]{3}$/.test(r)) return Alert.alert('RFC inválido', 'Revisa el RFC o déjalo vacío.');

    setGuardando(true);
    try {
      const estado = await encolar(
        {
          tipo: 'movimiento',
          cotizacionId: Number(params.obra),
          obra: params.titulo || `Obra ${params.obra}`,
          resumen: `${tipo === 'egreso' ? 'Gasto' : 'Ingreso'} ${money(m)} · ${descripcion.trim()}`,
          cuerpo: {
            tipo,
            categoria,
            fecha: haceDiasISO(Number(dia)),
            monto: m,
            descripcion: descripcion.trim(),
            concepto_id: esCosto ? conceptoId : null,
            proveedor: proveedor.trim() || null,
            rfc: r || null,
            forma_pago: formaPago,
            referencia: referencia.trim() || null,
          },
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

  const conceptos = (control?.conceptos ?? []).map((c) => ({ id: Number(c.id), clave: c.clave, descripcion: c.descripcion }));

  return (
    <KeyboardAvoidingView style={{ flex: 1 }} behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
      <Stack.Screen options={{ title: tipo === 'egreso' ? 'Registrar gasto' : 'Registrar ingreso' }} />
      <ScrollView contentContainerStyle={s.contenido} keyboardShouldPersistTaps="handled">
        <Campo etiqueta="Monto" value={monto} onChangeText={setMonto} keyboardType="decimal-pad" placeholder="$0.00" style={{ fontSize: 22, fontWeight: '700' }} />
        <Campo etiqueta="Descripción" value={descripcion} onChangeText={setDescripcion} placeholder={tipo === 'egreso' ? 'Ej. 35 varillas 3/8"' : 'Ej. Pago de estimación 2'} maxLength={255} />
        <Opciones
          etiqueta="Categoría"
          opciones={CATEGORIAS[tipo].filter((c) => !esIngeniero || !NO_COSTO.includes(c)).map((c) => ({ valor: c, texto: NOMBRE_CATEGORIA[c] }))}
          valor={categoria}
          onCambio={setCategoria}
        />
        <Opciones etiqueta="Fecha" opciones={DIAS} valor={dia} onCambio={setDia} />
        {esCosto && (
          <SelectorConcepto
            etiqueta="Concepto del catálogo"
            conceptos={conceptos}
            valor={conceptoId}
            onCambio={setConceptoId}
            permitirNinguno="Gasto general (sin concepto)"
          />
        )}
        <Opciones etiqueta="Forma de pago" opciones={FORMAS_PAGO.map((f) => ({ valor: f, texto: f }))} valor={formaPago} onCambio={setFormaPago} />
        <Campo etiqueta={tipo === 'egreso' ? 'Proveedor (opcional)' : 'Pagador (opcional)'} value={proveedor} onChangeText={setProveedor} />
        <View style={s.fila}>
          <View style={{ flex: 1 }}>
            <Campo etiqueta="RFC (opcional)" value={rfc} onChangeText={setRfc} autoCapitalize="characters" autoCorrect={false} maxLength={13} />
          </View>
          <View style={{ flex: 1 }}>
            <Campo etiqueta="Folio / factura" value={referencia} onChangeText={setReferencia} maxLength={60} />
          </View>
        </View>
        <FotoAdjunta etiqueta={tipo === 'egreso' ? 'Foto del ticket o factura' : 'Foto del comprobante (opcional)'} uri={foto} onCambio={setFoto} />
        <View style={{ height: Espacio.s }} />
        <Boton titulo="Guardar" icono="check" onPress={guardar} cargando={guardando} />
        <Text style={[ui.textoSuave, { textAlign: 'center' }]}>Si no hay señal se guarda y se envía después.</Text>
      </ScrollView>
    </KeyboardAvoidingView>
  );
}

const s = StyleSheet.create({
  contenido: { padding: Espacio.l, gap: Espacio.l, paddingBottom: Espacio.xl * 2 },
  fila: { flexDirection: 'row', gap: Espacio.m },
});
