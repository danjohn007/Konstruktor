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
        Response::view('clientes/index', ['user' => Auth::user(), 'titulo' => 'Clientes']);
    }

    public function apiIndex(): void
    {
        Auth::requireLogin();
        $db = Database::pdo();
        $q = trim($_GET['q'] ?? '');

        if ($q !== '') {
            $stmt = $db->prepare('SELECT * FROM clientes WHERE nombre LIKE ? OR rfc LIKE ? OR contacto LIKE ? ORDER BY nombre');
            $like = "%$q%";
            $stmt->execute([$like, $like, $like]);
        } else {
            $stmt = $db->query('SELECT * FROM clientes ORDER BY nombre');
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
