<?php

namespace App\Services;

use App\Core\Database;

/**
 * Estimaciones de obra: importe del periodo, amortización del anticipo,
 * IVA, fondo de garantía y neto a pagar, con acumulados por concepto.
 */
class Estimaciones
{
    /** Anticipo otorgado: % de anticipo sobre el importe contratado (sin IVA). */
    public static function anticipo(array $cot): float
    {
        return round($cot['totales']['subtotal'] * (float) $cot['pct_anticipo'], 2);
    }

    /**
     * Recalcula en orden todas las estimaciones de la obra. La amortización de cada
     * una depende de lo amortizado antes, por eso se recalculan en cadena.
     */
    public static function recalcular(int $cotizacionId): void
    {
        $db = Database::pdo();
        $cot = Calculadora::cotizacion($cotizacionId);
        if (!$cot) {
            return;
        }

        $anticipo = self::anticipo($cot);
        $amortizado = 0.0;

        $ests = $db->prepare('SELECT id FROM estimaciones WHERE cotizacion_id = ? ORDER BY numero');
        $ests->execute([$cotizacionId]);
        $lineas = $db->prepare('SELECT cantidad, pu FROM estimacion_conceptos WHERE estimacion_id = ?');
        $upd = $db->prepare('UPDATE estimaciones SET importe=?, amortizacion=?, iva=?, retencion=?, neto=? WHERE id=?');

        foreach ($ests->fetchAll(\PDO::FETCH_COLUMN) as $estId) {
            $lineas->execute([$estId]);
            $importe = 0.0;
            foreach ($lineas->fetchAll() as $l) {
                $importe += round((float) $l['cantidad'] * (float) $l['pu'], 2);
            }
            $t = self::totales(round($importe, 2), $cot, max(0.0, $anticipo - $amortizado));
            $amortizado += $t['amortizacion'];
            $upd->execute([$t['importe'], $t['amortizacion'], $t['iva'], $t['retencion'], $t['neto'], $estId]);
        }
    }

    public static function totales(float $importe, array $cot, float $anticipoPendiente): array
    {
        $amortizacion = min(round($importe * (float) $cot['pct_anticipo'], 2), round($anticipoPendiente, 2));
        $subtotal = round($importe - $amortizacion, 2);
        $iva = round($subtotal * (float) $cot['pct_iva'], 2);
        $retencion = round($importe * (float) $cot['pct_retencion'], 2);

        return [
            'importe' => $importe,
            'amortizacion' => $amortizacion,
            'subtotal' => $subtotal,
            'iva' => $iva,
            'retencion' => $retencion,
            'neto' => round($subtotal + $iva - $retencion, 2),
        ];
    }

    /** Estimación con su cotización, renglones por concepto y acumulados. */
    public static function detalle(int $estimacionId): ?array
    {
        $db = Database::pdo();
        $stmt = $db->prepare('SELECT * FROM estimaciones WHERE id = ?');
        $stmt->execute([$estimacionId]);
        $est = $stmt->fetch();
        if (!$est) {
            return null;
        }

        $cot = Calculadora::cotizacion((int) $est['cotizacion_id']);

        // Cantidades de esta estimación y de las anteriores, por concepto
        $rows = $db->prepare(
            'SELECT ec.concepto_id, e.numero, ec.cantidad, ec.pu
             FROM estimacion_conceptos ec JOIN estimaciones e ON e.id = ec.estimacion_id
             WHERE e.cotizacion_id = ? AND e.numero <= ?'
        );
        $rows->execute([$est['cotizacion_id'], $est['numero']]);
        $anterior = [];
        $esta = [];
        foreach ($rows->fetchAll() as $r) {
            if ((int) $r['numero'] === (int) $est['numero']) {
                $esta[$r['concepto_id']] = $r;
            } else {
                $anterior[$r['concepto_id']] = ($anterior[$r['concepto_id']] ?? 0) + (float) $r['cantidad'];
            }
        }

        $partidas = [];
        foreach ($cot['partidas'] as $p) {
            $conceptos = [];
            foreach ($p['conceptos'] as $c) {
                $contratado = $c['analisis']['cantidad'];
                $pu = isset($esta[$c['id']]) ? (float) $esta[$c['id']]['pu'] : $c['analisis']['pu'];
                $cantEsta = (float) ($esta[$c['id']]['cantidad'] ?? 0);
                $cantAnt = round($anterior[$c['id']] ?? 0, 4);
                $acumulado = round($cantAnt + $cantEsta, 4);
                $conceptos[] = [
                    'id' => $c['id'],
                    'clave' => $c['clave'],
                    'descripcion' => $c['descripcion'],
                    'unidad' => $c['unidad'],
                    'pu' => $pu,
                    'contratado' => $contratado,
                    'anterior' => $cantAnt,
                    'esta' => $cantEsta,
                    'acumulado' => $acumulado,
                    'por_ejecutar' => round($contratado - $acumulado, 4),
                    'importe' => round($cantEsta * $pu, 2),
                    'importe_acumulado' => round($acumulado * $pu, 2),
                    'excedente' => $acumulado > $contratado + 0.00001,
                ];
            }
            $partidas[] = ['id' => $p['id'], 'clave' => $p['clave'], 'nombre' => $p['nombre'], 'conceptos' => $conceptos];
        }

        $prev = $db->prepare(
            'SELECT COALESCE(SUM(importe),0) importe, COALESCE(SUM(amortizacion),0) amortizacion, COALESCE(SUM(neto),0) neto
             FROM estimaciones WHERE cotizacion_id = ? AND numero < ?'
        );
        $prev->execute([$est['cotizacion_id'], $est['numero']]);
        $prev = array_map('floatval', $prev->fetch());

        $cobro = $db->prepare('SELECT id FROM movimientos WHERE estimacion_id = ? LIMIT 1');
        $cobro->execute([$estimacionId]);

        $est['partidas'] = $partidas;
        $est['subtotal'] = round((float) $est['importe'] - (float) $est['amortizacion'], 2);
        $est['anteriores'] = $prev;
        $est['anticipo'] = self::anticipo($cot);
        $est['contrato'] = $cot['totales']['subtotal'];
        $est['cobro_registrado'] = (bool) $cobro->fetchColumn();
        $est['cotizacion'] = [
            'id' => $cot['id'], 'folio' => $cot['folio'], 'obra' => $cot['obra'], 'cliente_nombre' => $cot['cliente_nombre'],
            'cliente_rfc' => $cot['cliente_rfc'], 'direccion' => $cot['direccion'], 'municipio' => $cot['municipio'],
            'pct_anticipo' => $cot['pct_anticipo'], 'pct_iva' => $cot['pct_iva'], 'pct_retencion' => $cot['pct_retencion'],
        ];

        return $est;
    }
}
