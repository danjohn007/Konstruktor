<?php

namespace App\Core;

class Auth
{
    /** Días que dura la sesión de la app móvil sin volver a pedir contraseña. */
    private const DIAS_TOKEN = 180;

    /** Usuario autenticado por token (app móvil). Si la petición trae token, la sesión PHP no se usa. */
    private static ?array $tokenUser = null;

    /** Comprueba correo y contraseña sin abrir sesión. */
    public static function verificar(string $email, string $password): ?array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT u.*, r.nombre AS rol_nombre FROM usuarios u
             JOIN roles r ON r.id = u.rol_id
             WHERE u.email = ? AND u.activo = 1'
        );
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            unset($user['password_hash']);
            return $user;
        }

        return null;
    }

    public static function login(string $email, string $password): ?array
    {
        $user = self::verificar($email, $password);
        if ($user) {
            session_regenerate_id(true);
            $_SESSION['user'] = $user;
        }

        return $user;
    }

    /** Token que manda la app en "Authorization: Bearer ..." (o X-Auth-Token si el hosting borra Authorization). */
    public static function bearerToken(): ?string
    {
        $h = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
        if (preg_match('/^Bearer\s+([a-f0-9]{64})$/i', trim($h), $m)) {
            return strtolower($m[1]);
        }
        $x = trim($_SERVER['HTTP_X_AUTH_TOKEN'] ?? '');

        return preg_match('/^[a-f0-9]{64}$/i', $x) ? strtolower($x) : null;
    }

    /** Crea un token para la app; solo se guarda su hash, el token en claro se devuelve una vez. */
    public static function crearToken(int $usuarioId, ?string $dispositivo): string
    {
        $token = bin2hex(random_bytes(32));
        Database::pdo()->prepare(
            'INSERT INTO api_tokens (usuario_id, token_hash, dispositivo, expira) VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL ? DAY))'
        )->execute([$usuarioId, hash('sha256', $token), $dispositivo ? mb_substr($dispositivo, 0, 150) : null, self::DIAS_TOKEN]);

        return $token;
    }

    public static function revocarToken(string $token): void
    {
        Database::pdo()->prepare('DELETE FROM api_tokens WHERE token_hash = ?')->execute([hash('sha256', $token)]);
    }

    public static function logout(): void
    {
        $_SESSION = [];
        session_destroy();
    }

    public static function user(): ?array
    {
        return self::bearerToken() !== null ? self::$tokenUser : ($_SESSION['user'] ?? null);
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function hasRole(string ...$roles): bool
    {
        $user = self::user();
        return $user !== null && in_array($user['rol_nombre'], $roles, true);
    }

    /**
     * Vuelve a leer al usuario en cada petición: si lo desactivaron se cierra su
     * sesión, y si le cambiaron el rol el cambio aplica de inmediato.
     */
    private static function refrescar(): void
    {
        $token = self::bearerToken();
        if ($token !== null) {
            self::refrescarToken($token);
            return;
        }
        if (!self::check()) {
            return;
        }
        $stmt = Database::pdo()->prepare(
            'SELECT u.id, u.nombre, u.email, u.rol_id, u.activo, r.nombre AS rol_nombre
             FROM usuarios u JOIN roles r ON r.id = u.rol_id WHERE u.id = ?'
        );
        $stmt->execute([$_SESSION['user']['id']]);
        $fresco = $stmt->fetch();
        if (!$fresco || !(int) $fresco['activo']) {
            self::logout();
            return;
        }
        $_SESSION['user'] = array_merge($_SESSION['user'], $fresco);
    }

    /** Igual que refrescar(), pero para la app: token vigente y usuario activo. */
    private static function refrescarToken(string $token): void
    {
        $db = Database::pdo();
        $stmt = $db->prepare(
            'SELECT u.id, u.nombre, u.email, u.rol_id, u.activo, r.nombre AS rol_nombre, t.id AS token_id
             FROM api_tokens t JOIN usuarios u ON u.id = t.usuario_id JOIN roles r ON r.id = u.rol_id
             WHERE t.token_hash = ? AND t.expira > NOW() AND u.activo = 1'
        );
        $stmt->execute([hash('sha256', $token)]);
        $user = $stmt->fetch() ?: null;
        if ($user) {
            $db->prepare('UPDATE api_tokens SET ultimo_uso = NOW() WHERE id = ?')->execute([$user['token_id']]);
            unset($user['token_id']);
        }
        self::$tokenUser = $user;
    }

    public static function requireLogin(): void
    {
        self::refrescar();
        if (!self::check()) {
            if (self::isApi()) {
                Response::json(['error' => 'No autenticado'], 401);
            } else {
                header('Location: ' . BASE . '/login');
            }
            exit;
        }
    }

    public static function requireRole(string ...$roles): void
    {
        self::requireLogin();
        if (!self::hasRole(...$roles)) {
            if (self::isApi()) {
                Response::json(['error' => 'No autorizado para esta acción'], 403);
            } else {
                http_response_code(403);
                echo 'No autorizado';
            }
            exit;
        }
    }

    private static function isApi(): bool
    {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '';
        return str_contains($path, '/api/');
    }
}
