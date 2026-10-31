<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Config;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;

class InsumosController
{
    public function index(): void
    {
        Auth::requireLogin();
        Response::view('insumos/index', [
            'user' => Auth::user(),
            'titulo' => 'Insumos',
            'subtitulo' => 'Materiales, equipo y mano de obra con los que se arman los análisis de precio unitario',
        ]);
    }

    public function apiIndex(): void
    {
        Auth::requireLogin();
        $where = ['activo = 1'];
        $params = [];

        $q = trim($_GET['q'] ?? '');
        if ($q !== '') {
            $where[] = '(clave LIKE ? OR descripcion LIKE ?)';
            $params[] = "%$q%";
            $params[] = "%$q%";
        }
        if (in_array($_GET['tipo'] ?? '', ['MAT', 'MO', 'EQ'], true)) {
            $where[] = 'tipo = ?';
            $params[] = $_GET['tipo'];
        }
        if (($_GET['categoria'] ?? '') !== '') {
            $where[] = 'categoria = ?';
            $params[] = $_GET['categoria'];
        }

        $limit = min(2000, max(1, (int) ($_GET['limit'] ?? 500)));
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM insumos WHERE ' . implode(' AND ', $where) . " ORDER BY tipo, categoria, clave LIMIT $limit"
        );
        $stmt->execute($params);
        Response::json($stmt->fetchAll());
    }

    public function apiCategorias(): void
    {
        Auth::requireLogin();
        Response::json(Database::pdo()->query(
            'SELECT DISTINCT categoria FROM insumos WHERE activo = 1 AND categoria IS NOT NULL ORDER BY categoria'
        )->fetchAll(\PDO::FETCH_COLUMN));
    }

    public function apiStore(): void
    {
        Auth::requireLogin();
        $d = $this->validar(Request::json());
        if ($d === null) {
            return;
        }

        $db = Database::pdo();
        try {
            $db->prepare(
                'INSERT INTO insumos (clave, descripcion, unidad, tipo, categoria, precio_captura, mas_iva, precio)
                 VALUES (?,?,?,?,?,?,?,?)'
            )->execute([$d['clave'], $d['descripcion'], $d['unidad'], $d['tipo'], $d['categoria'],
                $d['precio_captura'], $d['mas_iva'], self::precioEfectivo($d['precio_captura'], $d['mas_iva'])]);
        } catch (\PDOException $e) {
            Response::json(['error' => 'Ya existe un insumo con la clave ' . $d['clave']], 409);
            return;
        }

        Response::json(['id' => (int) $db->lastInsertId()], 201);
    }

    public function apiUpdate(string $id): void
    {
        Auth::requireLogin();
        $actual = Database::pdo()->prepare('SELECT cuadrilla_id FROM insumos WHERE id = ?');
        $actual->execute([$id]);
        if ($actual->fetchColumn()) {
            Response::json(['error' => 'Este insumo es una cuadrilla: edítalo en la sección Cuadrillas'], 422);
            return;
        }

        $d = $this->validar(Request::json());
        if ($d === null) {
            return;
        }

        try {
            Database::pdo()->prepare(
                'UPDATE insumos SET clave=?, descripcion=?, unidad=?, tipo=?, categoria=?, precio_captura=?, mas_iva=?, precio=? WHERE id=?'
            )->execute([$d['clave'], $d['descripcion'], $d['unidad'], $d['tipo'], $d['categoria'],
                $d['precio_captura'], $d['mas_iva'], self::precioEfectivo($d['precio_captura'], $d['mas_iva']), $id]);
        } catch (\PDOException $e) {
            Response::json(['error' => 'Ya existe un insumo con la clave ' . $d['clave']], 409);
            return;
        }

        Response::json(['ok' => true]);
    }

    public function apiDelete(string $id): void
    {
        Auth::requireRole('Admin');
        // Baja lógica: los análisis de cotizaciones existentes lo siguen usando.
        Database::pdo()->prepare('UPDATE insumos SET activo = 0 WHERE id = ? AND cuadrilla_id IS NULL')->execute([$id]);
        Response::json(['ok' => true]);
    }

    public static function precioEfectivo(float $precioCaptura, int $masIva): float
    {
        return $masIva ? round($precioCaptura * (1 + Config::num('pct_iva_insumos')), 2) : $precioCaptura;
    }

    /** Recalcula los precios de los insumos cotizados sin IVA (cuando cambia el % en configuración). */
    public static function recalcularMasIva(): void
    {
        Database::pdo()->prepare('UPDATE insumos SET precio = ROUND(precio_captura * (1 + ?), 2) WHERE mas_iva = 1')
            ->execute([Config::num('pct_iva_insumos')]);
    }

    private function validar(array $d): ?array
    {
        $out = [
            'clave' => strtoupper((string) Request::str($d['clave'] ?? null)),
            'descripcion' => Request::str($d['descripcion'] ?? null),
            'unidad' => strtoupper((string) Request::str($d['unidad'] ?? null)),
            'tipo' => in_array($d['tipo'] ?? '', ['MAT', 'EQ'], true) ? $d['tipo'] : 'MAT',
            'categoria' => Request::str($d['categoria'] ?? null),
            'precio_captura' => Request::num($d['precio_captura'] ?? null),
            'mas_iva' => empty($d['mas_iva']) ? 0 : 1,
        ];

        if ($out['clave'] === '' || $out['descripcion'] === null || $out['unidad'] === '' || $out['precio_captura'] === null) {
            Response::json(['error' => 'Clave, descripción, unidad y precio son requeridos'], 422);
            return null;
        }
        if ($out['precio_captura'] < 0) {
            Response::json(['error' => 'El precio no puede ser negativo'], 422);
            return null;
        }

        return $out;
    }
}
