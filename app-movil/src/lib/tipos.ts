/** Respuestas de la API del sistema web que usa la app. */

export type Usuario = {
  id: number;
  nombre: string;
  email: string;
  rol_nombre: 'Admin' | 'Cotizador' | string;
};

export type Obra = {
  id: number;
  folio: string;
  obra: string;
  direccion: string | null;
  municipio: string | null;
  cliente: string | null;
  estado: string;
  contrato: number;
  inicio: string | null;
  termino: string | null;
  pct_ejecutado: number;
  pct_programado: number;
  cobrado: number;
  gastado: number;
  ultimo_registro: string | null;
};

export type ConceptoAvance = {
  id: number;
  partida_id: number;
  clave: string;
  descripcion: string;
  unidad: string;
  cantidad: number;
  pu: number;
  importe: number;
  inicio: string;
  termino: string;
  dias: number;
  ejecutado: number;
  pct: number;
  importe_ejecutado: number;
  programado: number;
  pct_programado: number;
  atrasado: boolean;
  excedente: boolean;
  ultimo_avance: string | null;
  costo_presupuesto: number;
  costo_real: number;
  diferencia: number;
  costo_unitario_real: number;
  pu_real: number | null;
};

export type Semana = {
  numero: number;
  inicio: string;
  fin: string;
  programado: number;
  ejecutado: number;
  gastado: number;
  cobrado: number;
  programado_acum: number;
  ejecutado_acum: number;
  gastado_acum: number;
};

export type RegistroAvance = {
  id: number;
  concepto_id: number;
  concepto_clave: string;
  concepto_descripcion: string;
  unidad: string;
  fecha: string;
  cantidad: string;
  notas: string | null;
  foto: string | null;
  usuario: string | null;
  usuario_id: number | null;
};

export type Avance = {
  contrato: number;
  ejecutado: number;
  pct_ejecutado: number;
  programado_hoy: number;
  pct_programado: number;
  costo_real_conceptos: number;
  gastos_generales: number;
  gasto_total: number;
  pct_generales: number;
  conceptos: ConceptoAvance[];
  semanas: Semana[];
  registros: RegistroAvance[];
};

export type Movimiento = {
  id: number;
  fecha: string;
  tipo: 'ingreso' | 'egreso';
  categoria: string;
  partida_id: number | null;
  concepto_id: number | null;
  concepto_clave: string | null;
  estimacion_id: number | null;
  descripcion: string;
  proveedor: string | null;
  rfc: string | null;
  forma_pago: string | null;
  referencia: string | null;
  monto: string;
  comprobante: string | null;
  usuario: string | null;
};

export type Control = {
  contrato: number;
  contrato_con_iva: number;
  anticipo: number;
  estimado: number;
  cobrado: number;
  gastado: number;
  saldo: number;
  costo_presupuestado: number;
  utilidad_presupuestada: number;
  utilidad_retirada: number;
  prestamos_por_recuperar: number;
  movimientos: Movimiento[];
  partidas: { id: number; clave: string; nombre: string }[];
  conceptos: { id: number; partida_id: number; clave: string; descripcion: string }[];
};

// El retiro de utilidad (UTILIDAD) se registra solo desde la web.
export const CATEGORIAS = {
  ingreso: ['ANTICIPO', 'PRESTAMO', 'OTRO'],
  egreso: ['MAT', 'MO', 'EQ', 'SUBCONTRATO', 'INDIRECTO', 'PRESTAMO', 'OTRO'],
} as const;

/** Categorías que mueven la caja pero no son costo ni cobro de la obra: no llevan concepto. */
export const NO_COSTO: readonly string[] = ['UTILIDAD', 'PRESTAMO'];

export const NOMBRE_CATEGORIA: Record<string, string> = {
  MAT: 'Materiales',
  MO: 'Mano de obra',
  EQ: 'Equipo',
  SUBCONTRATO: 'Subcontrato',
  INDIRECTO: 'Indirectos',
  OTRO: 'Otro',
  ANTICIPO: 'Anticipo',
  ESTIMACION: 'Estimación',
  PRESTAMO: 'Préstamo',
  UTILIDAD: 'Retiro de utilidad',
};

export const FORMAS_PAGO = ['Efectivo', 'Transferencia', 'Tarjeta', 'Cheque'] as const;
