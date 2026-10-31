<?php

namespace App\Services;

use App\Core\Database;

/**
 * Seguimiento semanal de la obra. Replica las hojas del Excel 2024:
 * - AVANCE: lo ejecutado y lo gastado por concepto contra lo presupuestado.
 * - MATERIAL y MANO DE OBRA: el costo de cada concepto repartido por igual entre
 *   sus días de programa y sumado por semana (programa de compra de material).
 * - FLUJO DE EFECTIVO: anticipo y estimaciones contra egresos programados, y lo real.
 */
class Programa
{
    /** Semanas máximas que se calculan (dos años). */
    private const MAX_SEMANAS = 104;

    /**
     * Movimientos que entran o salen de la caja pero no son costo de la obra ni cobro al cliente:
     * el retiro de utilidad y los préstamos (dados o devueltos). Cuentan en el saldo y en el flujo real.
     */
    public const NO_COSTO = ['UTILIDAD', 'PRESTAMO'];

    public static function esCosto(array $m): bool
    {
        return $m['tipo'] === 'egreso' && !in_array($m['categoria'], self::NO_COSTO, true);
    }

    public static function esCobro(array $m): bool
    {
        return $m['tipo'] === 'ingreso' && !in_array($m['categoria'], self::NO_COSTO, true);
    }

    public static function lunes(string $fecha): string
    {
        $d = new \DateTimeImmutable(substr($fecha, 0, 10));

        return $d->modify('-' . ((int) $d->format('N') - 1) . ' days')->format('Y-m-d');
    }

    private static function diasEntre(string $desde, string $hasta): int
    {
        return (int) (new \DateTimeImmutable($desde))->diff(new \DateTimeImmutable($hasta))->format('%r%a');
    }

    private static function sumarDias(string $fecha, int $dias): string
    {
        return (new \DateTimeImmutable($fecha))->modify(($dias >= 0 ? '+' : '') . $dias . ' days')->format('Y-m-d');
    }

    private static function conceptos(array $cot): array
    {
        return array_merge(...array_map(fn($p) => $p['conceptos'], $cot['partidas']));
    }

    /** Lunes de cada semana entre dos fechas (incluidas), con tope de MAX_SEMANAS. */
    private static function semanas(string $desde, string $hasta): array
    {
        $lunes = self::lunes($desde);
        $n = min(self::MAX_SEMANAS, intdiv(max(0, self::diasEntre($lunes, $hasta)), 7) + 1);

        return array_map(fn($i) => self::sumarDias($lunes, $i * 7), range(0, $n - 1));
    }

    /** Índice de la semana de una fecha; lo que cae antes o después del rango va a la primera o la última. */
    private static function indiceSemana(array $semanas, string $fecha): int
    {
        $dias = self::diasEntre($semanas[0], substr($fecha, 0, 10));

        return max(0, min(count($semanas) - 1, intdiv(max(0, $dias), 7)));
    }

    /**
     * Fracción del concepto que cae en cada semana: el Excel reparte el importe
     * por igual entre todos los días de calendario entre inicio y término.
     */
    private static function reparto(array $c, array $semanas): array
    {
        $dias = self::diasEntre($c['inicio'], $c['termino']) + 1;
        $out = array_fill(0, count($semanas), 0.0);
        foreach ($semanas as $i => $lunes) {
            $desde = max($lunes, $c['inicio']);
            $hasta = min(self::sumarDias($lunes, 6), $c['termino']);
            if ($desde <= $hasta) {
                $out[$i] = (self::diasEntre($desde, $hasta) + 1) / $dias;
            }
        }

        return $out;
    }

    /** Fracción del programa de un concepto que ya debería estar ejecutada en una fecha. */
    public static function fraccionProgramada(array $c, string $fecha): float
    {
        if ($fecha < $c['inicio']) {
            return 0.0;
        }
        if ($fecha >= $c['termino']) {
            return 1.0;
        }

        return (self::diasEntre($c['inicio'], $fecha) + 1) / (self::diasEntre($c['inicio'], $c['termino']) + 1);
    }

    private static function movimientos(int $cotizacionId): array
    {
        $stmt = Database::pdo()->prepare('SELECT id, fecha, tipo, categoria, concepto_id, monto FROM movimientos WHERE cotizacion_id = ?');
        $stmt->execute([$cotizacionId]);

        return $stmt->fetchAll();
    }

    // ------------------------------------------------------------------ avance

    /** Hoja AVANCE: ejecutado, programado y costo real por concepto, más el resumen por semana. */
    public static function avance(array $cot): array
    {
        $db = Database::pdo();
        $hoy = date('Y-m-d');
        $stmt = $db->prepare(
            'SELECT a.*, u.nombre AS usuario, co.clave AS concepto_clave, co.descripcion AS concepto_descripcion, co.unidad
             FROM avances a JOIN conceptos co ON co.id = a.concepto_id LEFT JOIN usuarios u ON u.id = a.usuario_id
             WHERE a.cotizacion_id = ? ORDER BY a.fecha DESC, a.id DESC'
        );
        $stmt->execute([$cot['id']]);
        $registros = $stmt->fetchAll();
        $movs = self::movimientos((int) $cot['id']);

        $ejecutado = [];
        $ultimo = [];
        foreach ($registros as $r) {
            $ejecutado[$r['concepto_id']] = ($ejecutado[$r['concepto_id']] ?? 0) + (float) $r['cantidad'];
            $ultimo[$r['concepto_id']] ??= $r['fecha'];
        }
        $costo = [];
        $generales = 0.0;
        $gastoTotal = 0.0;
        foreach ($movs as $m) {
            if (!self::esCosto($m)) {
                continue;
            }
            $gastoTotal += (float) $m['monto'];
            if ($m['concepto_id']) {
                $costo[$m['concepto_id']] = ($costo[$m['concepto_id']] ?? 0) + (float) $m['monto'];
            } else {
                $generales += (float) $m['monto'];
            }
        }
        // Como en el Excel: el P.U. real reparte los gastos generales (sin concepto) en proporción al gasto total.
        $pctGenerales = $gastoTotal > 0 ? $generales / $gastoTotal : 0.0;

        $conceptos = [];
        $tot = ['ejecutado' => 0.0, 'programado' => 0.0, 'costo_real' => 0.0];
        foreach (self::conceptos($cot) as $c) {
            $a = $c['analisis'];
            $q = $a['cantidad'];
            $ej = round($ejecutado[$c['id']] ?? 0.0, 4);
            $prog = round($q * self::fraccionProgramada($c, $hoy), 4);
            $cr = round($costo[$c['id']] ?? 0.0, 2);
            $cu = $q > 0 ? $cr / $q : 0.0;
            $conceptos[] = [
                'id' => (int) $c['id'],
                'partida_id' => (int) $c['partida_id'],
                'clave' => $c['clave'],
                'descripcion' => $c['descripcion'],
                'unidad' => $c['unidad'],
                'cantidad' => $q,
                'pu' => $a['pu'],
                'importe' => $a['importe'],
                'inicio' => $c['inicio'],
                'termino' => $c['termino'],
                'dias' => $a['dias'],
                'ejecutado' => $ej,
                'pct' => $q > 0 ? round($ej / $q, 4) : 0,
                'importe_ejecutado' => round($ej * $a['pu'], 2),
                'programado' => $prog,
                'pct_programado' => $q > 0 ? round($prog / $q, 4) : 0,
                'atrasado' => $ej + 0.0001 < $prog,
                'excedente' => $ej > $q + 0.0001,
                'ultimo_avance' => $ultimo[$c['id']] ?? null,
                'costo_presupuesto' => round(($a['costo_directo'] + $a['indirectos']) * $q, 2),
                'costo_real' => $cr,
                'diferencia' => round($a['importe'] - $cr, 2),
                'costo_unitario_real' => round($cu, 2),
                'pu_real' => $pctGenerales < 1 ? round($cu / (1 - $pctGenerales), 2) : null,
            ];
            $tot['ejecutado'] += $ej * $a['pu'];
            $tot['programado'] += $prog * $a['pu'];
            $tot['costo_real'] += $cr;
        }

        $contrato = $cot['totales']['subtotal'];
        $fechas = array_merge(array_column($registros, 'fecha'), array_column($movs, 'fecha'));
        $semanas = [];
        if ($cot['totales']['inicio'] || $fechas) {
            $desde = min(array_filter([$cot['totales']['inicio'], ...$fechas]));
            $hasta = max(array_filter([$cot['totales']['termino'], ...$fechas]));
            $semanas = self::resumenSemanal($cot, $registros, $movs, self::semanas($desde, $hasta));
        }

        return [
            'contrato' => $contrato,
            'ejecutado' => round($tot['ejecutado'], 2),
            'pct_ejecutado' => $contrato > 0 ? round($tot['ejecutado'] / $contrato, 4) : 0,
            'programado_hoy' => round($tot['programado'], 2),
            'pct_programado' => $contrato > 0 ? round($tot['programado'] / $contrato, 4) : 0,
            'costo_real_conceptos' => round($tot['costo_real'], 2),
            'gastos_generales' => round($generales, 2),
            'gasto_total' => round($gastoTotal, 2),
            'pct_generales' => round($pctGenerales, 4),
            'conceptos' => $conceptos,
            'semanas' => $semanas,
            'registros' => $registros,
        ];
    }

    private static function resumenSemanal(array $cot, array $registros, array $movs, array $semanas): array
    {
        $n = count($semanas);
        $programado = array_fill(0, $n, 0.0);
        $ejecutado = array_fill(0, $n, 0.0);
        $gastado = array_fill(0, $n, 0.0);
        $cobrado = array_fill(0, $n, 0.0);
        $pu = [];
        foreach (self::conceptos($cot) as $c) {
            $pu[$c['id']] = $c['analisis']['pu'];
            foreach (self::reparto($c, $semanas) as $i => $f) {
                $programado[$i] += $c['analisis']['importe'] * $f;
            }
        }
        foreach ($registros as $r) {
            $ejecutado[self::indiceSemana($semanas, $r['fecha'])] += (float) $r['cantidad'] * ($pu[$r['concepto_id']] ?? 0);
        }
        foreach ($movs as $m) {
            $i = self::indiceSemana($semanas, $m['fecha']);
            if (self::esCosto($m)) {
                $gastado[$i] += (float) $m['monto'];
            } elseif (self::esCobro($m)) {
                $cobrado[$i] += (float) $m['monto'];
            }
        }

        $out = [];
        $acum = ['programado' => 0.0, 'ejecutado' => 0.0, 'gastado' => 0.0];
        foreach ($semanas as $i => $lunes) {
            $acum['programado'] += $programado[$i];
            $acum['ejecutado'] += $ejecutado[$i];
            $acum['gastado'] += $gastado[$i];
            $out[] = [
                'numero' => $i + 1,
                'inicio' => $lunes,
                'fin' => self::sumarDias($lunes, 6),
                'programado' => round($programado[$i], 2),
                'ejecutado' => round($ejecutado[$i], 2),
                'gastado' => round($gastado[$i], 2),
                'cobrado' => round($cobrado[$i], 2),
                'programado_acum' => round($acum['programado'], 2),
                'ejecutado_acum' => round($acum['ejecutado'], 2),
                'gastado_acum' => round($acum['gastado'], 2),
            ];
        }

        return $out;
    }

    // ------------------------------------------------- compras, nómina y flujo

    /** Hojas MATERIAL y MANO DE OBRA (por semana) y FLUJO DE EFECTIVO programado contra real. */
    public static function flujo(array $cot): array
    {
        $conceptos = self::conceptos($cot);
        $movs = self::movimientos((int) $cot['id']);
        if (!$conceptos || !$cot['totales']['inicio']) {
            return ['semanas' => [], 'materiales' => [], 'mano_obra' => [], 'totales' => null];
        }

        $fechas = array_column($movs, 'fecha');
        $semanas = self::semanas(
            min([$cot['totales']['inicio'], ...$fechas]),
            max([$cot['totales']['termino'], ...$fechas])
        );
        $n = count($semanas);
        $cero = array_fill(0, $n, 0.0);
        $col = ['venta' => $cero, 'materiales' => $cero, 'mano_obra' => $cero, 'equipo' => $cero, 'indirectos' => $cero];
        $materiales = [];
        $manoObra = [];

        foreach ($conceptos as $c) {
            $a = $c['analisis'];
            $q = $a['cantidad'];
            $reparto = self::reparto($c, $semanas);
            foreach ($reparto as $i => $f) {
                $col['venta'][$i] += $a['importe'] * $f;
                $col['materiales'][$i] += $a['materiales'] * $q * $f;
                $col['mano_obra'][$i] += $a['mano_obra'] * $q * $f;
                $col['equipo'][$i] += ($a['equipo'] + $a['herramienta']) * $q * $f;
                $col['indirectos'][$i] += $a['indirectos'] * $q * $f;
            }
            foreach ($a['lineas'] as $l) {
                if ($l['tipo'] === 'EQ') {
                    continue;
                }
                $k = $l['insumo_id'];
                if ($l['tipo'] === 'MAT') {
                    $materiales[$k] ??= ['clave' => $l['clave'], 'descripcion' => $l['descripcion'], 'unidad' => $l['unidad'],
                        'precio' => (float) $l['precio'], 'cantidad' => 0.0, 'importe' => 0.0, 'semanas' => $cero];
                    $fila = &$materiales[$k];
                } else {
                    $manoObra[$k] ??= ['clave' => $l['clave'], 'descripcion' => $l['descripcion'], 'unidad' => $l['unidad'],
                        'precio' => (float) $l['precio'], 'cantidad' => 0.0, 'importe' => 0.0, 'semanas' => $cero];
                    $fila = &$manoObra[$k];
                }
                $cantidad = $l['cantidad_efectiva'] * $q;
                $fila['cantidad'] += $cantidad;
                $fila['importe'] += $l['importe'] * $q;
                foreach ($reparto as $i => $f) {
                    $fila['semanas'][$i] += $cantidad * $f;
                }
                unset($fila);
            }
        }

        // Ingresos programados: anticipo al arrancar y el resto en estimaciones iguales repartidas en el programa.
        $anticipo = Estimaciones::anticipo($cot);
        $numEst = max(1, (int) $cot['num_estimaciones']);
        $porEstimacion = ($cot['totales']['subtotal'] - $anticipo) / $numEst;
        $iniObra = self::indiceSemana($semanas, $cot['totales']['inicio']);
        $finObra = self::indiceSemana($semanas, $cot['totales']['termino']);
        $ingresos = $cero;
        $ingresos[$iniObra] += $anticipo;
        $duracion = $finObra - $iniObra + 1;
        for ($k = 1; $k <= $numEst; $k++) {
            $ingresos[$iniObra + (int) ceil($k * $duracion / $numEst) - 1] += $porEstimacion;
        }

        // El flujo real es la caja: incluye retiros de utilidad y préstamos.
        $realIng = $cero;
        $realEgr = $cero;
        foreach ($movs as $m) {
            $i = self::indiceSemana($semanas, $m['fecha']);
            if ($m['tipo'] === 'ingreso') {
                $realIng[$i] += (float) $m['monto'];
            } else {
                $realEgr[$i] += (float) $m['monto'];
            }
        }

        $filas = [];
        $saldo = 0.0;
        $saldoReal = 0.0;
        foreach ($semanas as $i => $lunes) {
            $egresos = $col['materiales'][$i] + $col['mano_obra'][$i] + $col['equipo'][$i] + $col['indirectos'][$i];
            $saldo += $ingresos[$i] - $egresos;
            $saldoReal += $realIng[$i] - $realEgr[$i];
            $filas[] = [
                'numero' => $i + 1,
                'inicio' => $lunes,
                'fin' => self::sumarDias($lunes, 6),
                'venta' => round($col['venta'][$i], 2),
                'materiales' => round($col['materiales'][$i], 2),
                'mano_obra' => round($col['mano_obra'][$i], 2),
                'equipo' => round($col['equipo'][$i], 2),
                'indirectos' => round($col['indirectos'][$i], 2),
                'egresos' => round($egresos, 2),
                'ingresos' => round($ingresos[$i], 2),
                'saldo' => round($saldo, 2),
                'real_ingresos' => round($realIng[$i], 2),
                'real_egresos' => round($realEgr[$i], 2),
                'real_saldo' => round($saldoReal, 2),
            ];
        }

        $limpiar = function (array $filas): array {
            $filas = array_values($filas);
            usort($filas, fn($a, $b) => $a['clave'] <=> $b['clave']);
            foreach ($filas as &$f) {
                $f['cantidad'] = round($f['cantidad'], 4);
                $f['importe'] = round($f['importe'], 2);
                $f['semanas'] = array_map(fn($x) => round($x, 4), $f['semanas']);
            }

            return $filas;
        };

        return [
            'semanas' => $filas,
            'materiales' => $limpiar($materiales),
            'mano_obra' => $limpiar($manoObra),
            'totales' => [
                'venta' => $cot['totales']['subtotal'],
                'anticipo' => $anticipo,
                'num_estimaciones' => $numEst,
                'por_estimacion' => round($porEstimacion, 2),
                'materiales' => round(array_sum($col['materiales']), 2),
                'mano_obra' => round(array_sum($col['mano_obra']), 2),
                'equipo' => round(array_sum($col['equipo']), 2),
                'indirectos' => round(array_sum($col['indirectos']), 2),
                'saldo_minimo' => $filas ? min(array_column($filas, 'saldo')) : 0,
            ],
        ];
    }
}
