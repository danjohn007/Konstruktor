const MXN = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' });

export function money(n: unknown): string {
  return MXN.format(Number(n) || 0);
}

export function numero(n: unknown, dec = 2): string {
  return (Number(n) || 0).toLocaleString('es-MX', { minimumFractionDigits: dec, maximumFractionDigits: dec });
}

export function pct(n: unknown, dec = 0): string {
  return numero((Number(n) || 0) * 100, dec) + '%';
}

/** YYYY-MM-DD → dd/mm/aaaa */
export function fecha(iso?: string | null): string {
  if (!iso) return '';
  const [y, m, d] = iso.slice(0, 10).split('-');
  return `${d}/${m}/${y}`;
}

/** Fecha local en formato YYYY-MM-DD (toISOString usa UTC y de noche da mañana). */
export function fechaISO(d = new Date()): string {
  return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
}

export function hoyISO(): string {
  return fechaISO();
}

export function haceDiasISO(dias: number): string {
  const d = new Date();
  d.setDate(d.getDate() - dias);
  return fechaISO(d);
}

/** Acepta "1,234.50" o "1234,5" escritos en el teclado del teléfono. */
export function leerNumero(texto: string): number | null {
  const limpio = texto.trim().replace(/\s/g, '');
  if (!limpio) return null;
  const normal = limpio.includes('.') ? limpio.replace(/,/g, '') : limpio.replace(',', '.');
  const n = Number(normal);
  return Number.isFinite(n) ? n : null;
}

/** Monto corto para tarjetas: $1.2 M, $350 k, $980. */
export function moneyCorto(n: unknown): string {
  const v = Number(n) || 0;
  const a = Math.abs(v);
  const signo = v < 0 ? '−' : '';
  if (a >= 1e6) return `${signo}$${numero(a / 1e6, a >= 1e7 ? 0 : 1)} M`;
  if (a >= 1e3) return `${signo}$${numero(a / 1e3, 0)} k`;
  return `${signo}$${numero(a, 0)}`;
}

/** Días de hoy a la fecha (negativo si ya pasó). */
export function diasHasta(iso?: string | null): number | null {
  if (!iso) return null;
  const [y, m, d] = iso.slice(0, 10).split('-').map(Number);
  const hoy = new Date();
  const fin = new Date(y, m - 1, d);
  hoy.setHours(0, 0, 0, 0);
  return Math.round((fin.getTime() - hoy.getTime()) / 86400000);
}

export function saludo(d = new Date()): string {
  const h = d.getHours();
  return h >= 5 && h < 12 ? 'Buenos días' : h >= 12 && h < 19 ? 'Buenas tardes' : 'Buenas noches';
}

/** "Hoy", "Ayer" o la fecha, para agrupar listas por día. */
export function diaRelativo(iso: string): string {
  const dias = diasHasta(iso);
  if (dias === 0) return 'Hoy';
  if (dias === -1) return 'Ayer';
  const [y, m, d] = iso.slice(0, 10).split('-').map(Number);
  return new Date(y, m - 1, d).toLocaleDateString('es-MX', { weekday: 'long', day: 'numeric', month: 'short' });
}
