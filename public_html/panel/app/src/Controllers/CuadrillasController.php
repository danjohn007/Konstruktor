<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Services\Nomina;

class CuadrillasController
{
    public function index(): void
    {
        Auth::requireLogin();
        Response::view('cuadrillas/index', ['user' => Auth::user(), 'titulo' => 'Cuadrillas']);
    }

    public function apiIndex(): void
    {
        Auth::requireLogin();
        $db = Database::pdo();
        $cuadrillas = $db->query('SELECT * FROM cuadrillas ORDER BY clave')->fetchAll();
        $int = $db->prepare('SELECT * FROM cuadrilla_integrantes WHERE cuadrilla_id = ? ORDER BY id');

        foreach ($cuadrillas as &$c) {
            $int->execute([$c['id']]);
            $c['integrantes'] = $int->fetchAll();
            $c['costo'] = Nomina::costoJornada($c['integrantes']);
        }

        Response::json($cuadrillas);
    }

    public function apiStore(): void
    {
        Auth::requireLogin();
        $this->guardar(null, Request::json());
    }

    public function apiUpdate(string $id): void
    {
        Auth::requireLogin();
        $this->guardar((int) $id, Request::json());
    }

    public function apiDelete(string $id): void
    {
        Auth::requireRole('Admin');
        $db = Database::pdo();
        $usada = $db->prepare(
            'SELECT COUNT(*) FROM concepto_insumos ci JOIN insumos i ON i.id = ci.insumo_id WHERE i.cuadrilla_id = ?'
        );
        $usada->execute([$id]);
        if ((int) $usada->fetchColumn() > 0) {
            Response::json(['error' => 'La cuadrilla se usa en análisis de cotizaciones; no se puede eliminar'], 409);
            return;
        }

        $db->prepare('DELETE FROM insumos WHERE cuadrilla_id = ?')->execute([$id]);
        $db->prepare('DELETE FROM cuadrillas WHERE id = ?')->execute([$id]);
        Response::json(['ok' => true]);
    }

    private function guardar(?int $id, array $d): void
    {
        $clave = strtoupper((string) Request::str($d['clave'] ?? null));
        $nombre = Request::str($d['nombre'] ?? null);
        $integrantes = array_values(array_filter($d['integrantes'] ?? [], fn($i) => Request::str($i['puesto'] ?? null) !== null));

        if ($clave === '' || $nombre === null) {
            Response::json(['error' => 'Clave y nombre son requeridos'], 422);
            return;
        }
        if (!$integrantes) {
            Response::json(['error' => 'Agrega al menos un integrante'], 422);
            return;
        }

        $db = Database::pdo();
        $choque = $db->prepare('SELECT COUNT(*) FROM insumos WHERE clave = ? AND (cuadrilla_id IS NULL OR cuadrilla_id <> ?)');
        $choque->execute([$clave, $id ?? 0]);
        if ((int) $choque->fetchColumn() > 0) {
            Response::json(['error' => "La clave $clave ya la usa otro insumo"], 409);
            return;
        }

        $db->beginTransaction();
        try {
            if ($id === null) {
                $db->prepare('INSERT INTO cuadrillas (clave, nombre) VALUES (?, ?)')->execute([$clave, $nombre]);
                $id = (int) $db->lastInsertId();
            } else {
                $db->prepare('UPDATE cuadrillas SET clave = ?, nombre = ? WHERE id = ?')->execute([$clave, $nombre, $id]);
                $db->prepare('DELETE FROM cuadrilla_integrantes WHERE cuadrilla_id = ?')->execute([$id]);
            }

            $ins = $db->prepare('INSERT INTO cuadrilla_integrantes (cuadrilla_id, puesto, cantidad, salario_semanal) VALUES (?,?,?,?)');
            foreach ($integrantes as $i) {
                $ins->execute([$id, Request::str($i['puesto']), Request::num($i['cantidad'] ?? 1) ?? 1, Request::num($i['salario_semanal'] ?? 0) ?? 0]);
            }

            Nomina::sincronizar($id);
            $db->commit();
        } catch (\PDOException $e) {
            $db->rollBack();
            Response::json(['error' => 'No se pudo guardar la cuadrilla (¿clave repetida?)'], 409);
            return;
        }

        Response::json(['id' => $id]);
    }
}
