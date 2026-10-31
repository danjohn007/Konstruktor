<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Services\Archivos;
use App\Services\Calculadora;
use App\Services\Programa;

/** Avance diario de obra (hoja AVANCE) y programa de compras / flujo de efectivo (hojas MATERIAL y MANO DE OBRA). */
class AvanceController
{
    public function apiAvance(string $cotizacionId): void
    {
        Auth::requireLogin();
        $cot = Calculadora::cotizacion((int) $cotizacionId);
        $cot ? Response::json(Programa::avance($cot)) : Response::json(['error' => 'Cotización no encontrada'], 404);
    }

    public function apiFlujo(string $cotizacionId): void
    {
        Auth::requireLogin();
        $cot = Calculadora::cotizacion((int) $cotizacionId);
        $cot ? Response::json(Programa::flujo($cot)) : Response::json(['error' => 'Cotización no encontrada'], 404);
    }

    public function apiStore(string $cotizacionId): void
    {
        Auth::requireLogin();
        if (CotizacionesController::estado((int) $cotizacionId) !== 'aceptada') {
            Response::json(['error' => 'El avance solo se registra en obras con cotización aceptada'], 409);
            return;
        }
        $input = Request::json();
        $db = Database::pdo();

        // La app manda un uuid por registro: si reintenta sin señal, no se duplica.
        $uuid = Request::uuid($input['uuid'] ?? null);
        if ($uuid !== null) {
            $existe = $db->prepare('SELECT id FROM avances WHERE uuid = ? AND cotizacion_id = ?');
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
        $db->prepare('INSERT INTO avances (cotizacion_id, concepto_id, fecha, cantidad, notas, uuid, usuario_id) VALUES (?,?,?,?,?,?,?)')
            ->execute([$cotizacionId, $d['concepto_id'], $d['fecha'], $d['cantidad'], $d['notas'], $uuid, Auth::user()['id']]);
        Response::json(['id' => (int) $db->lastInsertId()], 201);
    }

    public function apiUpdate(string $id): void
    {
        $avance = $this->editable((int) $id);
        if (!$avance) {
            return;
        }
        $d = $this->validar(Request::json(), (int) $avance['cotizacion_id']);
        if ($d === null) {
            return;
        }
        Database::pdo()->prepare('UPDATE avances SET concepto_id = ?, fecha = ?, cantidad = ?, notas = ? WHERE id = ?')
            ->execute([$d['concepto_id'], $d['fecha'], $d['cantidad'], $d['notas'], $id]);
        Response::json(['ok' => true]);
    }

    public function apiDelete(string $id): void
    {
        $avance = $this->editable((int) $id);
        if (!$avance) {
            return;
        }
        Database::pdo()->prepare('DELETE FROM avances WHERE id = ?')->execute([$id]);
        Archivos::borrar($avance['foto']);
        Response::json(['ok' => true]);
    }

    /** Cada quien corrige sus propios registros; el Admin, todos. */
    private function editable(int $id): ?array
    {
        Auth::requireLogin();
        $stmt = Database::pdo()->prepare('SELECT * FROM avances WHERE id = ?');
        $stmt->execute([$id]);
        $avance = $stmt->fetch();
        if (!$avance) {
            Response::json(['error' => 'Registro de avance no encontrado'], 404);
            return null;
        }
        if (!Auth::hasRole('Admin') && (int) $avance['usuario_id'] !== (int) Auth::user()['id']) {
            Response::json(['error' => 'Solo quien lo registró o un Admin puede modificar este avance'], 403);
            return null;
        }

        return $avance;
    }

    private function validar(array $d, int $cotizacionId): ?array
    {
        $conceptoId = (int) ($d['concepto_id'] ?? 0);
        $fecha = Request::str($d['fecha'] ?? null);
        $cantidad = Request::num($d['cantidad'] ?? null);

        $ok = Database::pdo()->prepare('SELECT COUNT(*) FROM conceptos WHERE id = ? AND cotizacion_id = ?');
        $ok->execute([$conceptoId, $cotizacionId]);
        if (!(int) $ok->fetchColumn()) {
            Response::json(['error' => 'El concepto no pertenece a esta obra'], 422);
            return null;
        }
        if ($fecha === null || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha) || $fecha > date('Y-m-d', strtotime('+1 day'))) {
            Response::json(['error' => 'Fecha inválida (no puede ser futura)'], 422);
            return null;
        }
        if ($cantidad === null || $cantidad <= 0) {
            Response::json(['error' => 'La cantidad ejecutada debe ser mayor a cero'], 422);
            return null;
        }

        $notas = Request::str($d['notas'] ?? null);

        return [
            'concepto_id' => $conceptoId,
            'fecha' => $fecha,
            'cantidad' => $cantidad,
            'notas' => $notas !== null ? mb_substr($notas, 0, 255) : null,
        ];
    }
}
