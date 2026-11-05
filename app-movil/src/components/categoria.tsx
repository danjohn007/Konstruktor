import { IconoCaja, type NombreIcono } from '@/components/ui';
import { ColorCategoria } from '@/lib/tema';

const ICONO: Record<string, NombreIcono> = {
  MAT: 'cube-outline',
  MO: 'account-hard-hat-outline',
  EQ: 'excavator',
  SUBCONTRATO: 'handshake-outline',
  INDIRECTO: 'clipboard-text-outline',
  OTRO: 'dots-horizontal-circle-outline',
  PRESTAMO: 'bank-transfer',
  ANTICIPO: 'cash-plus',
  ESTIMACION: 'file-document-check-outline',
  UTILIDAD: 'cash-refund',
};

export function iconoCategoria(categoria: string): NombreIcono {
  return ICONO[categoria] ?? 'tag-outline';
}

export function colorCategoria(categoria: string): string {
  return ColorCategoria[categoria] ?? ColorCategoria.OTRO;
}

/** Ícono de la categoría de un movimiento, en su color. */
export function IconoCategoria({ categoria, tam = 40 }: { categoria: string; tam?: number }) {
  const color = colorCategoria(categoria);
  return <IconoCaja nombre={iconoCategoria(categoria)} color={color} fondo={color + '1f'} tam={tam} />;
}
