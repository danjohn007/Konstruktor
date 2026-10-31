<?php

namespace App\Services;

use App\Core\Database;
use PDO;

/** Instalación: tablas, roles (Admin, Cotizador, Ingeniero), catálogo de insumos del Excel, cuadrillas, usuario administrador y migraciones pendientes. */
class Instalador
{
    private const CONFIG_LOCAL = __DIR__ . '/../../config.local.php';

    /** Ya hay base con tablas y al menos un usuario. */
    public static function instalado(): bool
    {
        try {
            return (bool) Database::pdo()->query('SELECT EXISTS(SELECT 1 FROM usuarios)')->fetchColumn();
        } catch (\PDOException) {
            return false;
        }
    }

    /**
     * Prueba la conexión y la guarda en app/config.local.php.
     * Devuelve el error para mostrarlo, o null si quedó guardada.
     */
    public static function guardarConexion(string $host, string $nombre, string $usuario, string $clave): ?string
    {
        try {
            new PDO("mysql:host=$host;dbname=$nombre;charset=utf8mb4", $usuario, $clave, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        } catch (\PDOException $e) {
            return 'No se pudo conectar a la base de datos: ' . $e->getMessage();
        }

        $config = [
            'db' => ['host' => $host, 'port' => '3306', 'name' => $nombre, 'user' => $usuario, 'pass' => $clave, 'charset' => 'utf8mb4'],
            'app' => ['name' => 'Konstruktor'],
        ];
        $php = "<?php\n\n// Escrito por el instalador (seed_web.php).\n\nreturn " . var_export($config, true) . ";\n";
        if (@file_put_contents(self::CONFIG_LOCAL, $php) === false) {
            return 'No se pudo escribir app/config.local.php: revisa que la carpeta app/ tenga permisos 755.';
        }
        if (function_exists('opcache_invalidate')) {
            opcache_invalidate(self::CONFIG_LOCAL, true);
        }
        Database::reiniciar();

        return null;
    }

    public static function run(string $adminEmail, string $adminPassword): array
    {
        $log = [];

        // Base nueva: crea las tablas, igual que importar schema.sql en phpMyAdmin
        // (todo es IF NOT EXISTS / INSERT IGNORE, así que repetirlo no daña).
        if (!self::instalado()) {
            foreach (Migraciones::sentencias((string) file_get_contents(__DIR__ . '/../../database/schema.sql')) as $stmt) {
                Database::pdo()->exec($stmt);
            }
            $log[] = 'Tablas creadas.';
        }

        $db = Database::pdo();

        foreach (['Admin', 'Cotizador', 'Ingeniero'] as $rol) {
            $db->prepare('INSERT IGNORE INTO roles (nombre) VALUES (?)')->execute([$rol]);
        }

        if ((int) $db->query('SELECT COUNT(*) FROM insumos')->fetchColumn() === 0) {
            $sql = file_get_contents(__DIR__ . '/../../database/insumos_iniciales.sql');
            foreach (Migraciones::sentencias($sql) as $stmt) {
                $db->exec($stmt);
            }
            Nomina::sincronizarTodas();
            $n = (int) $db->query('SELECT COUNT(*) FROM insumos')->fetchColumn();
            $log[] = "Catálogo inicial cargado: $n insumos (incluye cuadrillas).";
        } else {
            $log[] = 'Ya existen insumos; no se cargó el catálogo inicial.';
        }

        if ((int) $db->query('SELECT COUNT(*) FROM usuarios')->fetchColumn() === 0) {
            $rolId = $db->query("SELECT id FROM roles WHERE nombre = 'Admin'")->fetchColumn();
            $db->prepare('INSERT INTO usuarios (nombre, email, password_hash, rol_id) VALUES (?,?,?,?)')
                ->execute(['Administrador', $adminEmail, password_hash($adminPassword, PASSWORD_BCRYPT), $rolId]);
            $log[] = "Usuario administrador creado: $adminEmail";
        } else {
            $log[] = 'Ya existen usuarios; no se creó un administrador nuevo.';
        }

        // Cambios a la base que no venían en el schema.sql importado.
        return array_merge($log, Migraciones::aplicar());
    }
}
