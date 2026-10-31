<?php

namespace App\Services;

use App\Core\Database;

/**
 * Cálculo de precios unitarios, importes, explosión de insumos y programa.
 * Replica las hojas PU, CATALOGO y PRESUPUESTO del Excel (mismos redondeos).
 */
class Calculadora
{
    /** Resultado de un renglón de números generadores: producto de las medidas capturadas. */
    public static function resultadoVolumen(array $v): float
    {
        $factores = array_filter(
            [$v['largo'] ?? null, $v['ancho'] ?? null, $v['alto'] ?? null, $v['piezas'] ?? null],
            fn($x) => $x !== null && $x !== ''
        );

        return $factores ? round(array_product(array_map('floatval', $factores)), 4) : 0.0;
    }

    public static function cantidadConcepto(array $concepto, array $volumenes): float
    {
        if (!(int) $concepto['usa_volumenes']) {
            return (float) $concepto['cantidad'];
        }

        return round(array_sum(array_map([self::class, 'resultadoVolumen'], $volumenes)), 2);
    }

    /** Cantidad efectiva de un insumo por unidad de concepto. */
    public static function cantidadInsumo(array $ci, array $concepto): float
    {
        if ((int) $ci['por_rendimiento']) {
            $rend = (float) $concepto['rendimiento'];
            return $rend > 0 ? 1 / $rend : 0.0;
        }

        return (float) $ci['cantidad'];
    }

    /**
     * Análisis de un concepto. $insumos: filas de concepto_insumos con tipo del insumo.
     * $p: porcentajes de la cotización.
     */
    public static function analisis(array $concepto, array $insumos, array $volumenes, array $p): array
    {
        $tot = ['MAT' => 0.0, 'MO' => 0.0, 'EQ' => 0.0];
        $lineas = [];
        foreach ($insumos as $ci) {
            $cant = self::cantidadInsumo($ci, $concepto);
            $importe = round($cant * (float) $ci['precio'], 2);
            $tot[$ci['tipo']] += $importe;
            $lineas[] = $ci + ['cantidad_efectiva' => $cant, 'importe' => $importe];
        }

        $herramienta = round((float) $p['pct_herramienta'] * ($tot['MAT'] + $tot['MO']), 2);
        $directo = round($tot['MAT'] + $tot['MO'] + $tot['EQ'] + $herramienta, 2);
        $indirectos = round($directo * (float) $p['pct_indirectos'], 2);
        $utilidad = round($directo * (float) $p['pct_utilidad'], 2);
        $pu = round($directo + $indirectos + $utilidad, 2);
        $cantidad = self::cantidadConcepto($concepto, $volumenes);
        $rend = (float) $concepto['rendimiento'];

        return [
            'lineas' => $lineas,
            'materiales' => round($tot['MAT'], 2),
            'mano_obra' => round($tot['MO'], 2),
            'equipo' => round($tot['EQ'], 2),
            'herramienta' => $herramienta,
            'costo_directo' => $directo,
            'indirectos' => $indirectos,
            'utilidad' => $utilidad,
            'pu' => $pu,
            'cantidad' => $cantidad,
            'importe' => round($pu * $cantidad, 2),
            'dias' => $rend > 0 ? round($cantidad / $rend, 2) : 0.0,
            'sin_analisis' => $directo == 0.0,
        ];
    }

    /** Carga una cotización completa con todos sus cálculos. */
    public static function cotizacion(int $id): ?array
    {
        $db = Database::pdo();
        $stmt = $db->prepare(
            'SELECT c.*, cl.nombre AS cliente_nombre, cl.rfc AS cliente_rfc, cl.contacto AS cliente_contacto
             FROM cotizaciones c LEFT JOIN clientes cl ON cl.id = c.cliente_id WHERE c.id = ?'
        );
        $stmt->execute([$id]);
        $cot = $stmt->fetch();
        if (!$cot) {
            return null;
        }

        $partidas = $db->prepare('SELECT * FROM partidas WHERE cotizacion_id = ? ORDER BY orden, id');
        $partidas->execute([$id]);
        $partidas = $partidas->fetchAll();

        $conceptos = $db->prepare('SELECT * FROM conceptos WHERE cotizacion_id = ? ORDER BY orden, id');
        $conceptos->execute([$id]);
        $conceptos = $conceptos->fetchAll();

        $ins = $db->prepare(
            'SELECT ci.*, i.clave, i.descripcion, i.unidad, i.tipo, i.precio AS precio_catalogo
             FROM concepto_insumos ci JOIN insumos i ON i.id = ci.insumo_id
             JOIN conceptos co ON co.id = ci.concepto_id
             WHERE co.cotizacion_id = ? ORDER BY FIELD(i.tipo, \'MAT\', \'MO\', \'EQ\'), ci.id'
        );
        $ins->execute([$id]);
        $porConcepto = [];
        foreach ($ins->fetchAll() as $r) {
            $porConcepto[$r['concepto_id']][] = $r;
        }

        $vol = $db->prepare(
            'SELECT v.* FROM volumenes v JOIN conceptos co ON co.id = v.concepto_id
             WHERE co.cotizacion_id = ? ORDER BY v.id'
        );
        $vol->execute([$id]);
        $volPorConcepto = [];
        foreach ($vol->fetchAll() as $r) {
            $r['resultado'] = self::resultadoVolumen($r);
            $volPorConcepto[$r['concepto_id']][] = $r;
        }

        $inicioObra = $cot['fecha_inicio'] ?: date('Y-m-d');
        $subtotal = 0.0;
        $sinAnalisis = 0;
        $explosion = [];
        $porPartida = [];

        foreach ($conceptos as &$co) {
            $a = self::analisis($co, $porConcepto[$co['id']] ?? [], $volPorConcepto[$co['id']] ?? [], $cot);
            $co['analisis'] = $a;
            $co['volumenes'] = $volPorConcepto[$co['id']] ?? [];

            $duracion = max(1, (int) ceil($a['dias']));
            $co['inicio'] = $co['fecha_inicio'] ?: $inicioObra;
            $co['termino'] = date('Y-m-d', strtotime($co['inicio'] . ' +' . ($duracion - 1) . ' days'));

            $subtotal += $a['importe'];
            if ($a['sin_analisis']) {
                $sinAnalisis++;
            }
            $porPartida[$co['partida_id']][] = $co;

            foreach ($a['lineas'] as $l) {
                $k = $l['insumo_id'];
                $explosion[$k] ??= [
                    'clave' => $l['clave'], 'descripcion' => $l['descripcion'], 'unidad' => $l['unidad'],
                    'tipo' => $l['tipo'], 'precio' => (float) $l['precio'], 'cantidad' => 0.0, 'importe' => 0.0,
                ];
                $explosion[$k]['cantidad'] += $l['cantidad_efectiva'] * $a['cantidad'];
                $explosion[$k]['importe'] += $l['importe'] * $a['cantidad'];
            }
        }
        unset($co);

        foreach ($partidas as &$pa) {
            $pa['conceptos'] = $porPartida[$pa['id']] ?? [];
            $pa['importe'] = round(array_sum(array_map(fn($c) => $c['analisis']['importe'], $pa['conceptos'])), 2);
        }
        unset($pa);

        $explosion = array_values($explosion);
        usort($explosion, fn($a, $b) => [$a['tipo'], $a['clave']] <=> [$b['tipo'], $b['clave']]);
        foreach ($explosion as &$e) {
            $e['cantidad'] = round($e['cantidad'], 4);
            $e['importe'] = round($e['importe'], 2);
        }
        unset($e);

        $subtotal = round($subtotal, 2);
        $iva = round($subtotal * (float) $cot['pct_iva'], 2);
        $fechas = array_merge(array_column($conceptos, 'inicio'), array_column($conceptos, 'termino'));

        $cot['partidas'] = $partidas;
        $cot['explosion'] = $explosion;
        $cot['totales'] = [
            'subtotal' => $subtotal,
            'iva' => $iva,
            'total' => round($subtotal + $iva, 2),
            'anticipo' => round($subtotal * (float) $cot['pct_anticipo'], 2),
            'conceptos' => count($conceptos),
            'sin_analisis' => $sinAnalisis,
            'inicio' => $fechas ? min($fechas) : null,
            'termino' => $fechas ? max($fechas) : null,
        ];
        $cot['valida_hasta'] = date('Y-m-d', strtotime($cot['fecha_cotizacion'] . ' +' . (int) $cot['vigencia_dias'] . ' days'));

        return $cot;
    }

    /** Guarda subtotal y total en la cotización (para listados y tablero). */
    public static function refrescarTotales(int $cotizacionId): void
    {
        $cot = self::cotizacion($cotizacionId);
        if ($cot) {
            Database::pdo()->prepare('UPDATE cotizaciones SET subtotal = ?, total = ? WHERE id = ?')
                ->execute([$cot['totales']['subtotal'], $cot['totales']['total'], $cotizacionId]);
        }
    }
}
