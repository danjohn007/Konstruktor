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
        Response::view('dashboard/index', ['user' => Auth::user(), 'titulo' => 'Inicio']);
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

        Response::json([
            'por_estado' => $porEstado,
            'total_cotizaciones' => array_sum(array_column($porEstado, 'n')),
            'insumos' => (int) $db->query('SELECT COUNT(*) FROM insumos WHERE activo = 1')->fetchColumn(),
            'clientes' => (int) $db->query('SELECT COUNT(*) FROM clientes')->fetchColumn(),
            'recientes' => $recientes,
        ]);
    }
}
