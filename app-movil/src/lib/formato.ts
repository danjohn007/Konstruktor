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
