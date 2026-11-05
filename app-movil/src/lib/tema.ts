/**
 * Colores de la marca, los mismos del sistema web (assets/css/app.css y COLORES de assets/js/app.js).
 * Ningún componente debe escribir un color a mano: si falta uno, se agrega aquí desde la paleta web.
 */
export const Colores = {
  navy: '#0f2742', // --navy-900
  navyClaro: '#173a5e', // --navy-800
  navyProfundo: '#081a2e', // --navy-950
  navySuave: '#e8eef6', // .tono-navy
  acento: '#2f75b5', // --brand-600
  acentoMedio: '#3d83c2', // --brand-500
  acentoOscuro: '#265f94', // --brand-700
  acentoClaro: '#8fbbe2', // --brand-300
  acentoBorde: '#bcd6ee', // --brand-200
  acentoSuave: '#ddebf7', // --brand-100
  dorado: '#f5c26b', // acento del login web
  ambarMarca: '#f5a524', // grúa del login web
  texto: '#1e293b', // --ink
  textoSuave: '#64748b', // --muted
  textoTenue: '#94a3b8', // --slate-400
  borde: '#e5eaf1', // --border
  linea: '#eef2f6', // separadores y rejillas (COLORES.linea)
  pista: '#e2e8f0', // fondo de barras y esqueletos (--slate-200)
  presionado: '#f8fafc', // --slate-50
  fondo: '#f4f7fb', // --surface
  blanco: '#ffffff',
  // Sobre el navy de encabezados y login.
  textoNavy: '#c7d3e3', // --navy-200
  textoNavySuave: '#a3b4cc', // --navy-300
  textoNavyTenue: '#7d93b2', // --navy-400
  verdeNavy: '#d1f5e1', // --success-100
  // Estados.
  verde: '#1a7f55', // --success-700
  verdeVivo: '#22a06b', // --success-600
  verdeSuave: '#d1f5e1', // --success-100
  rojo: '#c4373c', // --danger-700
  rojoVivo: '#e5484d', // --danger-600
  rojoSuave: '#ffdcdd', // --danger-100
  rojoFondo: '#fff0f0', // --danger-50
  ambar: '#b45f0a', // --warning-700
  ambarVivo: '#f7881f', // --warning-600
  ambarSuave: '#ffe8cc', // --warning-100
  violeta: '#6048d0', // --purple-600
  violetaSuave: '#f2f0fd', // --purple-50
  teal: '#00a0a8', // --teal-600
} as const;

export const Espacio = { xs: 4, s: 8, m: 12, l: 16, xl: 24 } as const;
export const Radio = 12;
export const RadioTarjeta = 18;

/** Sombras suaves en capas, como las tarjetas del sistema web. */
export const Sombra = {
  tarjeta: '0 1px 2px rgba(16,24,40,0.05), 0 6px 18px rgba(16,24,40,0.06)',
  flotante: '0 12px 32px rgba(15,39,66,0.18)',
} as const;

/** Degradados de la marca (backgroundImage). */
export const Degradado = {
  encabezado: `linear-gradient(160deg, ${Colores.navyClaro} 0%, ${Colores.navy} 55%, ${Colores.navyProfundo} 100%)`,
  navy: `linear-gradient(180deg, ${Colores.navy} 0%, ${Colores.navyProfundo} 100%)`,
  primario: `linear-gradient(135deg, ${Colores.acentoMedio} 0%, ${Colores.acento} 60%, ${Colores.acentoOscuro} 100%)`,
  carga: `linear-gradient(90deg, ${Colores.acentoClaro} 0%, ${Colores.dorado} 100%)`,
  dorado: `linear-gradient(135deg, ${Colores.dorado} 0%, ${Colores.ambarMarca} 100%)`,
} as const;

/** Color de cada categoría de gasto: los mismos de las gráficas web (MAT marca, MO naranja, EQ morado). */
export const ColorCategoria: Record<string, string> = {
  MAT: Colores.acento,
  MO: Colores.ambarVivo,
  EQ: Colores.violeta,
  SUBCONTRATO: Colores.teal,
  INDIRECTO: Colores.acentoClaro,
  OTRO: Colores.textoSuave,
  PRESTAMO: Colores.textoTenue,
  ANTICIPO: Colores.verdeVivo,
  ESTIMACION: Colores.verdeVivo,
  UTILIDAD: Colores.textoTenue,
};
