<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Response;

class DashboardController
{
    public function index(): void
    {
        Auth::requireLogin();
        if (Auth::esIngeniero()) {
            Response::view('dashboard/ingeniero', ['user' => Auth::user(), 'titulo' => 'Inicio', 'subtitulo' => 'Tus obras y lo que has registrado']);
            return;
        }
        Response::view('dashboard/index', [
            'user' => Auth::user(),
            'titulo' => 'Inicio',
            'subtitulo' => 'Cartera de cotizaciones, obras en ejecución y flujo de caja',
            'graficas' => true,
        ]);
    }

    /** Inicio del ingeniero: sus registros recientes y su semana (avances y gastos que él capturó). */
    public function apiIngeniero(): void
    {
        Auth::requireLogin();
        $db = Database::pdo();
        $uid = Auth::user()['id'];
        $lunes = date('Y-m-d', strtotime('monday this week'));
        $consulta = function (string $sql, array $params) use ($db): array {
            $stmt = $db->prepare($sql);
            $stmt->execute($params);

            return $stmt->fetchAll();
        };
        $semana = $consulta(
            "SELECT (SELECT COUNT(*) FROM avances WHERE usuario_id = ? AND fecha >= ?) AS avances,
                    (SELECT COUNT(*) FROM movimientos WHERE usuario_id = ? AND tipo = 'egreso' AND fecha >= ?) AS gastos,
                    (SELECT COALESCE(SUM(monto), 0) FROM movimientos WHERE usuario_id = ? AND tipo = 'egreso' AND fecha >= ?) AS gastado,
                    (SELECT COUNT(*) FROM movimientos WHERE usuario_id = ? AND tipo = 'egreso' AND comprobante IS NULL) AS sin_comprobante",
            [$uid, $lunes, $uid, $lunes, $uid, $lunes, $uid]
        )[0];
        Response::json([
            'semana' => array_map('floatval', $semana),
            'avances' => $consulta(
                'SELECT a.fecha, a.cantidad, a.notas, a.foto IS NOT NULL AS foto, c.id AS cotizacion_id, c.folio, co.clave, co.descripcion, co.unidad
                 FROM avances a JOIN cotizaciones c ON c.id = a.cotizacion_id JOIN conceptos co ON co.id = a.concepto_id
                 WHERE a.usuario_id = ? ORDER BY a.fecha DESC, a.id DESC LIMIT 12', [$uid]),
            'gastos' => $consulta(
                "SELECT m.fecha, m.categoria, m.descripcion, m.monto, m.comprobante IS NOT NULL AS comprobante, c.id AS cotizacion_id, c.folio
                 FROM movimientos m JOIN cotizaciones c ON c.id = m.cotizacion_id
                 WHERE m.usuario_id = ? AND m.tipo = 'egreso' ORDER BY m.fecha DESC, m.id DESC LIMIT 12", [$uid]),
        ]);
    }

    public function apiResumen(): void
    {
        Auth::requireLogin();
        $db = Database::pdo();

        $porEstado = [];
        foreach ($db->query('SELECT estado, COUNT(*) n, SUM(total) monto FROM cotizaciones GROUP BY estado') as $r) {
            $porEstado[$r['estado']] = ['n' => (int) $r['n'], 'monto' => (float) $r['monto']];
        }

        $recientes = $db->query(
            'SELECT c.id, c.folio, c.obra, c.estado, c.total, c.fecha_cotizacion, cl.nombre AS cliente
             FROM cotizaciones c LEFT JOIN clientes cl ON cl.id = c.cliente_id
             ORDER BY c.updated_at DESC LIMIT 8'
        )->fetchAll();

        // Últimos 12 meses (incluido el actual), aunque alguno no tenga movimiento.
        $meses = [];
        for ($i = 11; $i >= 0; $i--) {
            $meses[] = date('Y-m', strtotime(date('Y-m-01') . " -$i month"));
        }
        $desde = $meses[0] . '-01';

        $porMes = array_fill_keys($meses, ['borrador' => 0.0, 'enviada' => 0.0, 'aceptada' => 0.0, 'rechazada' => 0.0]);
        $stmt = $db->prepare(
            "SELECT DATE_FORMAT(fecha_cotizacion, '%Y-%m') mes, estado, SUM(total) monto
             FROM cotizaciones WHERE fecha_cotizacion >= ? GROUP BY mes, estado"
        );
        $stmt->execute([$desde]);
        foreach ($stmt as $r) {
            if (isset($porMes[$r['mes']][$r['estado']])) {
                $porMes[$r['mes']][$r['estado']] = round((float) $r['monto'], 2);
            }
        }

        // Hoja CONTROL de todas las obras: lo que entró y salió de caja cada mes.
        $caja = array_fill_keys($meses, ['ingresos' => 0.0, 'egresos' => 0.0]);
        $stmt = $db->prepare(
            "SELECT DATE_FORMAT(fecha, '%Y-%m') mes, tipo, SUM(monto) monto FROM movimientos WHERE fecha >= ? GROUP BY mes, tipo"
        );
        $stmt->execute([$desde]);
        foreach ($stmt as $r) {
            if (isset($caja[$r['mes']])) {
                $caja[$r['mes']][$r['tipo'] === 'ingreso' ? 'ingresos' : 'egresos'] = round((float) $r['monto'], 2);
            }
        }

        Response::json([
            'por_estado' => $porEstado,
            'total_cotizaciones' => array_sum(array_column($porEstado, 'n')),
            'insumos' => (int) $db->query('SELECT COUNT(*) FROM insumos WHERE activo = 1')->fetchColumn(),
            'clientes' => (int) $db->query('SELECT COUNT(*) FROM clientes')->fetchColumn(),
            'recientes' => $recientes,
            'por_mes' => array_map(fn($m) => ['mes' => $m] + $porMes[$m], $meses),
            'caja_mes' => array_map(fn($m) => ['mes' => $m] + $caja[$m], $meses),
        ]);
    }
}
