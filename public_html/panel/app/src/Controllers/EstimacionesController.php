<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Config;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Services\Calculadora;
use App\Services\Estimaciones;

class EstimacionesController
{
    public const ESTADOS = ['borrador', 'presentada', 'autorizada', 'pagada'];

    public function show(string $id): void
    {
        Auth::requireLogin();
        $est = Estimaciones::detalle((int) $id);
        if (!$est) {
            http_response_code(404);
            echo 'Estimación no encontrada';
            return;
        }
        Response::view('estimaciones/detalle', [
            'user' => Auth::user(),
            'titulo' => 'Estimación ' . $est['numero'] . ' · ' . $est['cotizacion']['folio'],
            'migas' => ['Cotizaciones' => 'cotizaciones', $est['cotizacion']['folio'] => 'cotizaciones/' . (int) $est['cotizacion']['id']],
            'estimacionId' => (int) $id,
        ]);
    }

    public function imprimir(string $id): void
    {
        Auth::requireLogin();
        $est = Estimaciones::detalle((int) $id);
        if (!$est) {
            http_response_code(404);
            echo 'Estimación no encontrada';
            return;
        }
        Response::view('estimaciones/imprimir', ['est' => $est, 'cfg' => Config::all()], false);
    }

    public function apiIndex(string $cotizacionId): void
    {
        Auth::requireLogin();
        $cot = Calculadora::cotizacion((int) $cotizacionId);
        if (!$cot) {
            Response::json(['error' => 'Cotización no encontrada'], 404);
            return;
        }

        $stmt = Database::pdo()->prepare('SELECT * FROM estimaciones WHERE cotizacion_id = ? ORDER BY numero');
        $stmt->execute([$cotizacionId]);
        $ests = $stmt->fetchAll();

        $estimado = array_sum(array_column($ests, 'importe'));
        Response::json([
            'estimaciones' => $ests,
            'contrato' => $cot['totales']['subtotal'],
            'anticipo' => Estimaciones::anticipo($cot),
            'estimado' => round($estimado, 2),
            'amortizado' => round(array_sum(array_column($ests, 'amortizacion')), 2),
            'por_estimar' => round($cot['totales']['subtotal'] - $estimado, 2),
            'estado_cotizacion' => $cot['estado'],
        ]);
    }

    public function apiStore(string $cotizacionId): void
    {
        Auth::requireLogin();
        $d = Request::json();

        if (CotizacionesController::estado((int) $cotizacionId) !== 'aceptada') {
            Response::json(['error' => 'Solo se estiman cotizaciones aceptadas. Cambia el estado en “Datos generales”.'], 409);
            return;
        }

        $db = Database::pdo();
        $abiertas = $db->prepare("SELECT COUNT(*) FROM estimaciones WHERE cotizacion_id = ? AND estado = 'borrador'");
        $abiertas->execute([$cotizacionId]);
        if ((int) $abiertas->fetchColumn() > 0) {
            Response::json(['error' => 'Ya hay una estimación en borrador; preséntala antes de abrir otra'], 409);
            return;
        }

        $db->prepare(
            'INSERT INTO estimaciones (cotizacion_id, numero, fecha, periodo_inicio, periodo_fin)
             SELECT ?, COALESCE(MAX(numero), 0) + 1, ?, ?, ? FROM estimaciones WHERE cotizacion_id = ?'
        )->execute([
            $cotizacionId,
            Request::str($d['fecha'] ?? null) ?? date('Y-m-d'),
            Request::str($d['periodo_inicio'] ?? null),
            Request::str($d['periodo_fin'] ?? null),
            $cotizacionId,
        ]);

        Response::json(['id' => (int) $db->lastInsertId()], 201);
    }

    public function apiShow(string $id): void
    {
        Auth::requireLogin();
        $est = Estimaciones::detalle((int) $id);
        $est ? Response::json($est) : Response::json(['error' => 'Estimación no encontrada'], 404);
    }

    public function apiUpdate(string $id): void
    {
        Auth::requireLogin();
        $d = Request::json();
        $fecha = Request::str($d['fecha'] ?? null);
        if ($fecha === null || !in_array($d['estado'] ?? '', self::ESTADOS, true)) {
            Response::json(['error' => 'Fecha y estado válidos son requeridos'], 422);
            return;
        }

        Database::pdo()->prepare('UPDATE estimaciones SET fecha=?, periodo_inicio=?, periodo_fin=?, estado=?, notas=? WHERE id=?')
            ->execute([$fecha, Request::str($d['periodo_inicio'] ?? null), Request::str($d['periodo_fin'] ?? null),
                $d['estado'], Request::str($d['notas'] ?? null), $id]);
        Response::json(['ok' => true]);
    }

    /** Captura la cantidad estimada de un concepto en esta estimación. */
    public function apiCantidad(string $id): void
    {
        Auth::requireLogin();
        $d = Request::json();
        $est = $this->estimacion($id);
        if (!$est) {
            Response::json(['error' => 'Estimación no encontrada'], 404);
            return;
        }
        if ($est['estado'] !== 'borrador') {
            Response::json(['error' => 'Solo se pueden modificar cantidades de una estimación en borrador'], 409);
            return;
        }

        $cantidad = Request::num($d['cantidad'] ?? null) ?? 0;
        if ($cantidad < 0) {
            Response::json(['error' => 'La cantidad no puede ser negativa'], 422);
            return;
        }

        $cot = Calculadora::cotizacion((int) $est['cotizacion_id']);
        $concepto = null;
        foreach ($cot['partidas'] as $p) {
            foreach ($p['conceptos'] as $c) {
                if ((int) $c['id'] === (int) ($d['concepto_id'] ?? 0)) {
                    $concepto = $c;
                }
            }
        }
        if (!$concepto) {
            Response::json(['error' => 'El concepto no pertenece a esta obra'], 422);
            return;
        }

        $db = Database::pdo();
        if ($cantidad == 0) {
            $db->prepare('DELETE FROM estimacion_conceptos WHERE estimacion_id = ? AND concepto_id = ?')->execute([$id, $concepto['id']]);
        } else {
            // El P.U. se fija la primera vez que se estima el concepto en esta estimación.
            $db->prepare(
                'INSERT INTO estimacion_conceptos (estimacion_id, concepto_id, cantidad, pu) VALUES (?,?,?,?)
                 ON DUPLICATE KEY UPDATE cantidad = VALUES(cantidad)'
            )->execute([$id, $concepto['id'], $cantidad, $concepto['analisis']['pu']]);
        }

        Estimaciones::recalcular((int) $est['cotizacion_id']);
        Response::json(['ok' => true]);
    }

    public function apiDelete(string $id): void
    {
        Auth::requireLogin();
        $est = $this->estimacion($id);
        if (!$est) {
            Response::json(['error' => 'Estimación no encontrada'], 404);
            return;
        }

        $db = Database::pdo();
        $max = $db->prepare('SELECT MAX(numero) FROM estimaciones WHERE cotizacion_id = ?');
        $max->execute([$est['cotizacion_id']]);
        if ($est['estado'] !== 'borrador' || (int) $max->fetchColumn() !== (int) $est['numero']) {
            Response::json(['error' => 'Solo se puede eliminar la última estimación y si sigue en borrador'], 409);
            return;
        }

        $db->prepare('DELETE FROM estimaciones WHERE id = ?')->execute([$id]);
        Estimaciones::recalcular((int) $est['cotizacion_id']);
        Response::json(['ok' => true]);
    }

    /** Marca la estimación como pagada y registra el ingreso en el control de la obra. */
    public function apiCobro(string $id): void
    {
        Auth::requireLogin();
        $d = Request::json();
        $est = $this->estimacion($id);
        if (!$est) {
            Response::json(['error' => 'Estimación no encontrada'], 404);
            return;
        }
        if ($est['estado'] === 'borrador') {
            Response::json(['error' => 'Presenta la estimación antes de registrar su cobro'], 409);
            return;
        }

        $db = Database::pdo();
        $ya = $db->prepare('SELECT COUNT(*) FROM movimientos WHERE estimacion_id = ?');
        $ya->execute([$id]);
        if ((int) $ya->fetchColumn() > 0) {
            Response::json(['error' => 'El cobro de esta estimación ya está registrado'], 409);
            return;
        }

        $db->beginTransaction();
        $db->prepare(
            "INSERT INTO movimientos (cotizacion_id, fecha, tipo, categoria, estimacion_id, descripcion, forma_pago, referencia, monto, usuario_id)
             VALUES (?, ?, 'ingreso', 'ESTIMACION', ?, ?, ?, ?, ?, ?)"
        )->execute([
            $est['cotizacion_id'],
            Request::str($d['fecha'] ?? null) ?? date('Y-m-d'),
            $id,
            'Cobro de estimación ' . $est['numero'],
            Request::str($d['forma_pago'] ?? null),
            Request::str($d['referencia'] ?? null),
            $est['neto'],
            Auth::user()['id'],
        ]);
        $db->prepare("UPDATE estimaciones SET estado = 'pagada' WHERE id = ?")->execute([$id]);
        $db->commit();

        Response::json(['ok' => true]);
    }

    private function estimacion(string $id): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM estimaciones WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }
}
