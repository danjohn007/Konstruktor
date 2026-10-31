<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;

class UsuariosController
{
    private const MIN_PASSWORD = 8;

    public function index(): void
    {
        Auth::requireRole('Admin');
        Response::view('usuarios/index', ['user' => Auth::user(), 'titulo' => 'Usuarios']);
    }

    public function apiIndex(): void
    {
        Auth::requireRole('Admin');
        Response::json([
            'usuarios' => Database::pdo()->query(
                'SELECT u.id, u.nombre, u.email, u.rol_id, u.activo, u.created_at, r.nombre AS rol_nombre
                 FROM usuarios u JOIN roles r ON r.id = u.rol_id ORDER BY u.nombre'
            )->fetchAll(),
            'roles' => Database::pdo()->query('SELECT * FROM roles ORDER BY id')->fetchAll(),
        ]);
    }

    public function apiStore(): void
    {
        Auth::requireRole('Admin');
        $d = Request::json();
        $nombre = Request::str($d['nombre'] ?? null);
        $email = strtolower((string) Request::str($d['email'] ?? null));
        $password = (string) ($d['password'] ?? '');

        if ($nombre === null || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Response::json(['error' => 'Nombre y un correo válido son requeridos'], 422);
            return;
        }
        if (strlen($password) < self::MIN_PASSWORD) {
            Response::json(['error' => 'La contraseña debe tener al menos ' . self::MIN_PASSWORD . ' caracteres'], 422);
            return;
        }

        $db = Database::pdo();
        try {
            $db->prepare('INSERT INTO usuarios (nombre, email, password_hash, rol_id) VALUES (?,?,?,?)')
                ->execute([$nombre, $email, password_hash($password, PASSWORD_BCRYPT), (int) ($d['rol_id'] ?? 0)]);
        } catch (\PDOException $e) {
            Response::json(['error' => 'Ya existe un usuario con ese correo o el rol no es válido'], 409);
            return;
        }

        Response::json(['id' => (int) $db->lastInsertId()], 201);
    }

    public function apiUpdate(string $id): void
    {
        Auth::requireRole('Admin');
        $d = Request::json();
        $nombre = Request::str($d['nombre'] ?? null);
        $activo = empty($d['activo']) ? 0 : 1;
        $rolId = (int) ($d['rol_id'] ?? 0);

        if ($nombre === null) {
            Response::json(['error' => 'El nombre es requerido'], 422);
            return;
        }
        if ((int) $id === (int) Auth::user()['id'] && (!$activo || $rolId !== (int) Auth::user()['rol_id'])) {
            Response::json(['error' => 'No puedes desactivarte ni quitarte el rol de administrador a ti mismo'], 409);
            return;
        }

        $db = Database::pdo();
        try {
            $db->prepare('UPDATE usuarios SET nombre=?, rol_id=?, activo=? WHERE id=?')->execute([$nombre, $rolId, $activo, $id]);
        } catch (\PDOException $e) {
            Response::json(['error' => 'Rol no válido'], 422);
            return;
        }

        $password = (string) ($d['password'] ?? '');
        if ($password !== '') {
            if (strlen($password) < self::MIN_PASSWORD) {
                Response::json(['error' => 'La contraseña debe tener al menos ' . self::MIN_PASSWORD . ' caracteres'], 422);
                return;
            }
            $db->prepare('UPDATE usuarios SET password_hash=? WHERE id=?')->execute([password_hash($password, PASSWORD_BCRYPT), $id]);
        }

        Response::json(['ok' => true]);
    }

    /** Cualquier usuario cambia su propia contraseña confirmando la actual. */
    public function apiMiPassword(): void
    {
        Auth::requireLogin();
        $d = Request::json();
        $actual = (string) ($d['actual'] ?? '');
        $nueva = (string) ($d['nueva'] ?? '');

        $stmt = Database::pdo()->prepare('SELECT password_hash FROM usuarios WHERE id = ?');
        $stmt->execute([Auth::user()['id']]);
        if (!password_verify($actual, (string) $stmt->fetchColumn())) {
            Response::json(['error' => 'La contraseña actual no es correcta'], 422);
            return;
        }
        if (strlen($nueva) < self::MIN_PASSWORD) {
            Response::json(['error' => 'La nueva contraseña debe tener al menos ' . self::MIN_PASSWORD . ' caracteres'], 422);
            return;
        }

        Database::pdo()->prepare('UPDATE usuarios SET password_hash=? WHERE id=?')
            ->execute([password_hash($nueva, PASSWORD_BCRYPT), Auth::user()['id']]);
        Response::json(['ok' => true]);
    }
}
