<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;

class ClientesController
{
    private const CAMPOS = ['nombre', 'rfc', 'contacto', 'telefono', 'email', 'direccion'];

    public function index(): void
    {
        Auth::requireLogin();
        Response::view('clientes/index', ['user' => Auth::user(), 'titulo' => 'Clientes', 'subtitulo' => 'Datos de contacto y facturación de quienes te encargan obra']);
    }

    public function apiIndex(): void
    {
        Auth::requireLogin();
        $db = Database::pdo();
        $q = trim($_GET['q'] ?? '');

        // Con cuántas cotizaciones y obras cuenta cada cliente y cuánto le hemos contratado.
        $sql = "SELECT cl.*, COALESCE(c.cotizaciones, 0) AS cotizaciones, COALESCE(c.obras, 0) AS obras,
                       COALESCE(c.contratado, 0) AS contratado, c.ultima
                FROM clientes cl LEFT JOIN (
                    SELECT cliente_id, COUNT(*) AS cotizaciones, SUM(estado = 'aceptada') AS obras,
                           SUM(CASE WHEN estado = 'aceptada' THEN total ELSE 0 END) AS contratado, MAX(fecha_cotizacion) AS ultima
                    FROM cotizaciones GROUP BY cliente_id
                ) c ON c.cliente_id = cl.id";
        if ($q !== '') {
            $stmt = $db->prepare("$sql WHERE cl.nombre LIKE ? OR cl.rfc LIKE ? OR cl.contacto LIKE ? ORDER BY cl.nombre");
            $like = "%$q%";
            $stmt->execute([$like, $like, $like]);
        } else {
            $stmt = $db->query("$sql ORDER BY cl.nombre");
        }

        Response::json($stmt->fetchAll());
    }

    public function apiStore(): void
    {
        Auth::requireLogin();
        $d = Request::json();

        if (Request::str($d['nombre'] ?? null) === null) {
            Response::json(['error' => 'El nombre es requerido'], 422);
            return;
        }

        $db = Database::pdo();
        $db->prepare('INSERT INTO clientes (nombre, rfc, contacto, telefono, email, direccion) VALUES (?,?,?,?,?,?)')
            ->execute($this->valores($d));

        Response::json(['id' => (int) $db->lastInsertId()], 201);
    }

    public function apiUpdate(string $id): void
    {
        Auth::requireLogin();
        $d = Request::json();

        if (Request::str($d['nombre'] ?? null) === null) {
            Response::json(['error' => 'El nombre es requerido'], 422);
            return;
        }

        Database::pdo()->prepare('UPDATE clientes SET nombre=?, rfc=?, contacto=?, telefono=?, email=?, direccion=? WHERE id=?')
            ->execute([...$this->valores($d), $id]);

        Response::json(['ok' => true]);
    }

    public function apiDelete(string $id): void
    {
        Auth::requireRole('Admin');
        Database::pdo()->prepare('DELETE FROM clientes WHERE id = ?')->execute([$id]);
        Response::json(['ok' => true]);
    }

    private function valores(array $d): array
    {
        return array_map(fn($c) => Request::str($d[$c] ?? null), self::CAMPOS);
    }
}
