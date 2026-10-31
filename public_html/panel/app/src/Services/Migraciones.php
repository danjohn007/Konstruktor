<?php

namespace App\Services;

use App\Core\Database;

/**
 * Cambios a la base de datos que se aplican solos al subir una versión nueva, como
 * `php artisan migrate`: cada archivo de app/database/migraciones se corre una vez, en
 * orden de nombre, y queda registrado en la tabla `migraciones`.
 *
 * Reglas para escribir una migración:
 * - Nombre con número de orden: 005_lo_que_hace.sql.
 * - Que se pueda correr más de una vez (IF NOT EXISTS, INSERT IGNORE...): si falla a medias,
 *   se vuelve a intentar desde el principio.
 * - Si el cambio también va en schema.sql, agregar el archivo al INSERT de `migraciones` de ese
 *   esquema, para que las instalaciones nuevas no lo vuelvan a aplicar.
 */
class Migraciones
{
    private const DIR = __DIR__ . '/../../database/migraciones';

    /** Tras un error se espera esto antes de reintentar solo (desde Configuración se puede forzar). */
    private const ESPERA_REINTENTO = 600;

    /** Se llama en cada petición; si no hay nada pendiente cuesta una consulta. */
    public static function alDia(): void
    {
        $registro = self::registro();
        $pendientes = array_filter(self::archivos(), function (string $a) use ($registro): bool {
            $r = $registro[$a] ?? null;
            if ($r === null) {
                return true;
            }
            return $r['aplicada'] === null && (int) $r['segundos'] >= self::ESPERA_REINTENTO;
        });
        // Una base recién creada se completa con el instalador (seed), que aplica las migraciones al final.
        if ($pendientes && self::instalada()) {
            self::aplicar();
        }
    }

    /**
     * Aplica las migraciones pendientes en orden. Si una falla, guarda el error y se detiene
     * (las siguientes pueden depender de ella). Devuelve lo que hizo, para mostrarlo.
     */
    public static function aplicar(): array
    {
        $db = Database::pdo();
        // Evita que dos visitas simultáneas apliquen la misma migración.
        if ((int) $db->query("SELECT GET_LOCK('konstruktor_migraciones', 30)")->fetchColumn() !== 1) {
            return ['Otra petición está aplicando migraciones; intenta de nuevo en un momento.'];
        }
        $log = [];
        try {
            $registro = self::registro();
            foreach (self::archivos() as $archivo) {
                if (($registro[$archivo]['aplicada'] ?? null) !== null) {
                    continue;
                }
                try {
                    foreach (self::sentencias((string) file_get_contents(self::DIR . '/' . $archivo)) as $sql) {
                        $db->exec($sql);
                    }
                } catch (\PDOException $e) {
                    error_log("Migración $archivo: " . $e->getMessage());
                    self::anotar($archivo, $e->getMessage());
                    $log[] = "Error en $archivo: " . $e->getMessage();
                    break;
                }
                self::anotar($archivo, null);
                $log[] = "Migración aplicada: $archivo";
            }
        } finally {
            $db->query("SELECT RELEASE_LOCK('konstruktor_migraciones')");
        }

        return $log;
    }

    /** Estado de cada archivo, para la pantalla de Configuración. */
    public static function estado(): array
    {
        $registro = self::registro();

        return array_map(fn(string $a) => [
            'archivo' => $a,
            'aplicada' => $registro[$a]['aplicada'] ?? null,
            'error' => ($registro[$a]['aplicada'] ?? null) === null ? ($registro[$a]['error'] ?? null) : null,
        ], self::archivos());
    }

    /** Separa un .sql en sentencias (sin comentarios de línea completa). Sin DELIMITER ni procedimientos. */
    public static function sentencias(string $sql): array
    {
        $sql = preg_replace('/^\s*--.*$/m', '', $sql);

        return array_values(array_filter(array_map('trim', preg_split('/;\s*(?:\n|$)/', $sql)), fn($s) => $s !== ''));
    }

    private static function archivos(): array
    {
        $archivos = array_map('basename', glob(self::DIR . '/*.sql') ?: []);
        sort($archivos, SORT_STRING);

        return $archivos;
    }

    /** archivo => fila de la tabla `migraciones`; la crea si la base es anterior a este sistema. */
    private static function registro(): array
    {
        $db = Database::pdo();
        try {
            // La espera se mide en MySQL: PHP puede tener otra zona horaria que NOW().
            $filas = $db->query('SELECT archivo, aplicada, error, TIMESTAMPDIFF(SECOND, intento, NOW()) AS segundos FROM migraciones')->fetchAll();
        } catch (\PDOException $e) {
            if ((string) $e->getCode() !== '42S02') { // 42S02 = la tabla no existe
                throw $e;
            }
            $db->exec(
                'CREATE TABLE IF NOT EXISTS migraciones (
                    archivo VARCHAR(150) PRIMARY KEY,
                    aplicada DATETIME DEFAULT NULL,
                    intento DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    error TEXT DEFAULT NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
            );
            $filas = [];
        }

        return array_column($filas, null, 'archivo');
    }

    private static function anotar(string $archivo, ?string $error): void
    {
        Database::pdo()->prepare(
            'INSERT INTO migraciones (archivo, aplicada, intento, error) VALUES (?, IF(? IS NULL, NOW(), NULL), NOW(), ?)
             ON DUPLICATE KEY UPDATE aplicada = VALUES(aplicada), intento = VALUES(intento), error = VALUES(error)'
        )->execute([$archivo, $error, $error]);
    }

    /** Ya corrió el instalador (hay al menos un usuario). */
    private static function instalada(): bool
    {
        return (bool) Database::pdo()->query('SELECT EXISTS(SELECT 1 FROM usuarios)')->fetchColumn();
    }
}
