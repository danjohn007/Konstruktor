<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Services\Calculadora;
use App\Services\Archivos;
use App\Services\Estimaciones;
use App\Services\Programa;

/** Control de obra (hojas AVANCE y CONTROL del Excel): ingresos, egresos y avance. */
class ObraController
{
    // UTILIDAD y PRESTAMO no son costo ni cobro (Programa::NO_COSTO): solo mueven la caja.
    public const CATEGORIAS = [
        'ingreso' => ['ANTICIPO', 'ESTIMACION', 'PRESTAMO', 'OTRO'],
        'egreso' => ['MAT', 'MO', 'EQ', 'SUBCONTRATO', 'INDIRECTO', 'OTRO', 'UTILIDAD', 'PRESTAMO'],
    ];

    public function apiControl(string $cotizacionId): void
    {
        Auth::requireLogin();
        $cot = Calculadora::cotizacion((int) $cotizacionId);
        if (!$cot) {
            Response::json(['error' => 'Cotización no encontrada'], 404);
            return;
        }
        $db = Database::pdo();

        $movs = $db->prepare(
            'SELECT m.*, p.clave AS partida_clave, p.nombre AS partida_nombre, e.numero AS estimacion_numero,
                    co.clave AS concepto_clave, co.descripcion AS concepto_descripcion, u.nombre AS usuario
             FROM movimientos m LEFT JOIN partidas p ON p.id = m.partida_id LEFT JOIN estimaciones e ON e.id = m.estimacion_id
             LEFT JOIN conceptos co ON co.id = m.concepto_id LEFT JOIN usuarios u ON u.id = m.usuario_id
             WHERE m.cotizacion_id = ? ORDER BY m.fecha DESC, m.id DESC'
        );
        $movs->execute([$cotizacionId]);
        $movs = $movs->fetchAll();

        // Presupuesto de costo por tipo y por partida (costo directo + indirectos de cada concepto)
        $presTipo = ['MAT' => 0.0, 'MO' => 0.0, 'EQ' => 0.0, 'INDIRECTO' => 0.0];
        $partidas = [];
        $utilidad = 0.0;
        $programadoHoy = 0.0;
        $hoy = strtotime(date('Y-m-d'));
        foreach ($cot['partidas'] as $p) {
            $presPartida = 0.0;
            foreach ($p['conceptos'] as $c) {
                $a = $c['analisis'];
                $q = $a['cantidad'];
                $presTipo['MAT'] += $a['materiales'] * $q;
                $presTipo['MO'] += $a['mano_obra'] * $q;
                $presTipo['EQ'] += ($a['equipo'] + $a['herramienta']) * $q;
                $presTipo['INDIRECTO'] += $a['indirectos'] * $q;
                $utilidad += $a['utilidad'] * $q;
                $presPartida += ($a['costo_directo'] + $a['indirectos']) * $q;

                // Importe que el programa dice que ya debería estar ejecutado hoy
                $ini = strtotime($c['inicio']);
                $fin = strtotime($c['termino']);
                $frac = $hoy < $ini ? 0 : ($hoy >= $fin ? 1 : (($hoy - $ini) / 86400 + 1) / (($fin - $ini) / 86400 + 1));
                $programadoHoy += $a['importe'] * $frac;
            }
            $partidas[$p['id']] = ['clave' => $p['clave'], 'nombre' => $p['nombre'], 'presupuesto' => round($presPartida, 2), 'gastado' => 0.0];
        }

        $categoriasCosto = array_values(array_diff(self::CATEGORIAS['egreso'], Programa::NO_COSTO));
        $gastoTipo = array_fill_keys($categoriasCosto, 0.0);
        $cobrado = 0.0;
        $gastado = 0.0;
        $sinPartida = 0.0;
        $caja = 0.0;
        $utilidadRetirada = 0.0;
        $prestado = 0.0;
        foreach ($movs as $m) {
            $monto = (float) $m['monto'];
            $caja += $m['tipo'] === 'ingreso' ? $monto : -$monto;
            if ($m['categoria'] === 'PRESTAMO') {
                $prestado += $m['tipo'] === 'egreso' ? $monto : -$monto;
                continue;
            }
            if ($m['categoria'] === 'UTILIDAD') {
                $utilidadRetirada += $monto;
                continue;
            }
            if ($m['tipo'] === 'ingreso') {
                $cobrado += $monto;
                continue;
            }
            $gastado += $monto;
            $gastoTipo[$m['categoria']] = ($gastoTipo[$m['categoria']] ?? 0) + $monto;
            if ($m['partida_id'] && isset($partidas[$m['partida_id']])) {
                $partidas[$m['partida_id']]['gastado'] += $monto;
            } else {
                $sinPartida += $monto;
            }
        }

        $est = $db->prepare('SELECT COALESCE(SUM(importe),0) FROM estimaciones WHERE cotizacion_id = ?');
        $est->execute([$cotizacionId]);
        $estimado = (float) $est->fetchColumn();
        $contrato = $cot['totales']['subtotal'];

        Response::json([
            'contrato' => $contrato,
            'contrato_con_iva' => $cot['totales']['total'],
            'anticipo' => Estimaciones::anticipo($cot),
            'estimado' => round($estimado, 2),
            'pct_estimado' => $contrato > 0 ? round($estimado / $contrato, 4) : 0,
            'programado_hoy' => round($programadoHoy, 2),
            'pct_programado' => $contrato > 0 ? round($programadoHoy / $contrato, 4) : 0,
            'cobrado' => round($cobrado, 2),
            'gastado' => round($gastado, 2),
            'saldo' => round($caja, 2),
            'costo_presupuestado' => round(array_sum($presTipo), 2),
            'utilidad_presupuestada' => round($utilidad, 2),
            'utilidad_retirada' => round($utilidadRetirada, 2),
            'prestamos_por_recuperar' => round($prestado, 2),
            'por_tipo' => array_map(fn($k) => [
                'categoria' => $k,
                'presupuesto' => round($presTipo[$k] ?? 0, 2),
                'gastado' => round($gastoTipo[$k] ?? 0, 2),
            ], $categoriasCosto),
            'por_partida' => array_values(array_map(fn($p) => $p + ['gastado' => round($p['gastado'], 2)], $partidas)),
            'sin_partida' => round($sinPartida, 2),
            'movimientos' => $movs,
            'partidas' => array_map(fn($p) => ['id' => $p['id'], 'clave' => $p['clave'], 'nombre' => $p['nombre']], $cot['partidas']),
            'conceptos' => array_merge(...array_map(fn($p) => array_map(
                fn($c) => ['id' => $c['id'], 'partida_id' => $c['partida_id'], 'clave' => $c['clave'], 'descripcion' => $c['descripcion']],
                $p['conceptos']
            ), $cot['partidas'])),
        ]);
    }

    /** Obras en ejecución (cotizaciones aceptadas) con su avance y gasto; es la pantalla de inicio de la app. */
    public function apiObras(): void
    {
        Auth::requireLogin();
        $db = Database::pdo();
        $estados = ($_GET['todas'] ?? '') === '1' ? "'aceptada','enviada'" : "'aceptada'";
        $ids = $db->query("SELECT id FROM cotizaciones WHERE estado IN ($estados) ORDER BY updated_at DESC")->fetchAll(\PDO::FETCH_COLUMN);

        $noCosto = "'" . implode("','", Programa::NO_COSTO) . "'";
        $movs = $db->prepare(
            "SELECT COALESCE(SUM(CASE WHEN tipo = 'ingreso' AND categoria NOT IN ($noCosto) THEN monto END), 0) AS cobrado,
                    COALESCE(SUM(CASE WHEN tipo = 'egreso' AND categoria NOT IN ($noCosto) THEN monto END), 0) AS gastado,
                    MAX(fecha) AS ultimo_movimiento
             FROM movimientos WHERE cotizacion_id = ?"
        );
        $avances = $db->prepare('SELECT concepto_id, SUM(cantidad) AS cantidad, MAX(fecha) AS fecha FROM avances WHERE cotizacion_id = ? GROUP BY concepto_id');
        $hoy = date('Y-m-d');
        $obras = [];
        foreach ($ids as $id) {
            $cot = Calculadora::cotizacion((int) $id);
            $avances->execute([$id]);
            $ejecutado = [];
            $ultimoAvance = null;
            foreach ($avances->fetchAll() as $a) {
                $ejecutado[$a['concepto_id']] = (float) $a['cantidad'];
                $ultimoAvance = max($ultimoAvance, $a['fecha']);
            }
            $importeEjecutado = 0.0;
            $importeProgramado = 0.0;
            foreach ($cot['partidas'] as $p) {
                foreach ($p['conceptos'] as $c) {
                    $importeEjecutado += ($ejecutado[$c['id']] ?? 0) * $c['analisis']['pu'];
                    $importeProgramado += $c['analisis']['importe'] * Programa::fraccionProgramada($c, $hoy);
                }
            }
            $movs->execute([$id]);
            $m = $movs->fetch();
            $contrato = $cot['totales']['subtotal'];
            $obras[] = [
                'id' => (int) $cot['id'],
                'folio' => $cot['folio'],
                'obra' => $cot['obra'],
                'direccion' => $cot['direccion'],
                'municipio' => $cot['municipio'],
                'cliente' => $cot['cliente_nombre'],
                'estado' => $cot['estado'],
                'contrato' => $contrato,
                'inicio' => $cot['totales']['inicio'],
                'termino' => $cot['totales']['termino'],
                'pct_ejecutado' => $contrato > 0 ? round($importeEjecutado / $contrato, 4) : 0,
                'pct_programado' => $contrato > 0 ? round($importeProgramado / $contrato, 4) : 0,
                'cobrado' => round((float) $m['cobrado'], 2),
                'gastado' => round((float) $m['gastado'], 2),
                'ultimo_registro' => max($ultimoAvance, $m['ultimo_movimiento']),
            ];
        }
        Response::json($obras);
    }

    public function apiStore(string $cotizacionId): void
    {
        Auth::requireLogin();
        $input = Request::json();
        $db = Database::pdo();

        // La app manda un uuid por movimiento: si reintenta sin señal, no se duplica.
        $uuid = Request::uuid($input['uuid'] ?? null);
        if ($uuid !== null) {
            $existe = $db->prepare('SELECT id FROM movimientos WHERE uuid = ? AND cotizacion_id = ?');
            $existe->execute([$uuid, $cotizacionId]);
            if ($id = $existe->fetchColumn()) {
                Response::json(['id' => (int) $id, 'duplicado' => true]);
                return;
            }
        }

        $d = $this->validar($input, (int) $cotizacionId);
        if ($d === null) {
            return;
        }
        $db->prepare(
            'INSERT INTO movimientos (cotizacion_id, fecha, tipo, categoria, partida_id, descripcion, proveedor, rfc, forma_pago, referencia, monto, concepto_id, uuid, usuario_id)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
        )->execute([$cotizacionId, ...array_values($d), $uuid, Auth::user()['id']]);
        Response::json(['id' => (int) $db->lastInsertId()], 201);
    }

    public function apiUpdate(string $id): void
    {
        Auth::requireLogin();
        $db = Database::pdo();
        $mov = $db->prepare('SELECT * FROM movimientos WHERE id = ?');
        $mov->execute([$id]);
        $mov = $mov->fetch();
        if (!$mov) {
            Response::json(['error' => 'Movimiento no encontrado'], 404);
            return;
        }
        if ($mov['estimacion_id']) {
            Response::json(['error' => 'Este ingreso viene del cobro de una estimación; no se edita a mano'], 409);
            return;
        }
        $d = $this->validar(Request::json(), (int) $mov['cotizacion_id']);
        if ($d === null) {
            return;
        }
        $db->prepare(
            'UPDATE movimientos SET fecha=?, tipo=?, categoria=?, partida_id=?, descripcion=?, proveedor=?, rfc=?, forma_pago=?, referencia=?, monto=?, concepto_id=? WHERE id=?'
        )->execute([...array_values($d), $id]);
        Response::json(['ok' => true]);
    }

    public function apiDelete(string $id): void
    {
        Auth::requireRole('Admin');
        $db = Database::pdo();
        $comprobante = $db->prepare('SELECT comprobante FROM movimientos WHERE id = ?');
        $comprobante->execute([$id]);
        $comprobante = $comprobante->fetchColumn() ?: null;
        // Si era el cobro de una estimación, ésta vuelve a "autorizada".
        $db->prepare("UPDATE estimaciones e JOIN movimientos m ON m.estimacion_id = e.id SET e.estado = 'autorizada' WHERE m.id = ?")
            ->execute([$id]);
        $db->prepare('DELETE FROM movimientos WHERE id = ?')->execute([$id]);
        Archivos::borrar($comprobante);
        Response::json(['ok' => true]);
    }

    private function validar(array $d, int $cotizacionId): ?array
    {
        $tipo = $d['tipo'] ?? '';
        $categoria = $d['categoria'] ?? '';
        $monto = Request::num($d['monto'] ?? null);
        $fecha = Request::str($d['fecha'] ?? null);
        $descripcion = Request::str($d['descripcion'] ?? null);

        if (!isset(self::CATEGORIAS[$tipo]) || !in_array($categoria, self::CATEGORIAS[$tipo], true)) {
            Response::json(['error' => 'Tipo o categoría inválidos'], 422);
            return null;
        }
        if ($fecha === null || $descripcion === null || $monto === null || $monto <= 0) {
            Response::json(['error' => 'Fecha, descripción y un monto mayor a cero son requeridos'], 422);
            return null;
        }

        // Un retiro de utilidad o un préstamo no es gasto de ninguna partida ni concepto.
        $esCosto = Programa::esCosto(['tipo' => $tipo, 'categoria' => $categoria]);
        $partidaId = $esCosto ? Request::num($d['partida_id'] ?? null) : null;
        // Un gasto ligado a un concepto (columna CATALOGO del Excel) cuenta para la partida de ese concepto.
        $conceptoId = $esCosto ? Request::num($d['concepto_id'] ?? null) : null;
        if ($conceptoId !== null) {
            $c = Database::pdo()->prepare('SELECT partida_id FROM conceptos WHERE id = ? AND cotizacion_id = ?');
            $c->execute([$conceptoId, $cotizacionId]);
            $partidaId = $c->fetchColumn();
            if ($partidaId === false) {
                Response::json(['error' => 'El concepto no pertenece a esta obra'], 422);
                return null;
            }
            $partidaId = (int) $partidaId;
        }
        if ($partidaId !== null) {
            $ok = Database::pdo()->prepare('SELECT COUNT(*) FROM partidas WHERE id = ? AND cotizacion_id = ?');
            $ok->execute([$partidaId, $cotizacionId]);
            if (!(int) $ok->fetchColumn()) {
                Response::json(['error' => 'La partida no pertenece a esta obra'], 422);
                return null;
            }
        }

        return [
            'fecha' => $fecha,
            'tipo' => $tipo,
            'categoria' => $categoria,
            'partida_id' => $tipo === 'egreso' ? $partidaId : null,
            'descripcion' => $descripcion,
            'proveedor' => Request::str($d['proveedor'] ?? null),
            'rfc' => strtoupper((string) Request::str($d['rfc'] ?? null)) ?: null,
            'forma_pago' => Request::str($d['forma_pago'] ?? null),
            'referencia' => Request::str($d['referencia'] ?? null),
            'monto' => $monto,
            'concepto_id' => $conceptoId,
        ];
    }
}
