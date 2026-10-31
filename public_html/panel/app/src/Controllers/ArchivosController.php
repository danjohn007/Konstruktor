<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Response;
use App\Services\Archivos;

/** Comprobantes de movimientos y fotos de avance. */
class ArchivosController
{
    /** tipo de la URL => [tabla, columna] */
    private const TIPOS = [
        'movimientos' => ['movimientos', 'comprobante'],
        'avances' => ['avances', 'foto'],
    ];

    public function ver(string $tipo, string $id): void
    {
        Auth::requireLogin();
        if (!isset(self::TIPOS[$tipo])) {
            http_response_code(404);
            echo 'Archivo no encontrado';
            return;
        }
        [$tabla, $columna] = self::TIPOS[$tipo];
        $stmt = Database::pdo()->prepare("SELECT $columna FROM $tabla WHERE id = ?");
        $stmt->execute([$id]);
        $ruta = Archivos::ruta($stmt->fetchColumn() ?: null);
        if (!$ruta) {
            http_response_code(404);
            echo 'Archivo no encontrado';
            return;
        }
        Archivos::enviar($ruta);
    }

    public function apiSubirComprobante(string $id): void
    {
        $this->subir('movimientos', (int) $id);
    }

    public function apiSubirFoto(string $id): void
    {
        $this->subir('avances', (int) $id);
    }

    public function apiBorrarComprobante(string $id): void
    {
        Auth::requireLogin();
        $db = Database::pdo();
        $stmt = $db->prepare('SELECT comprobante, usuario_id FROM movimientos WHERE id = ?');
        $stmt->execute([$id]);
        $mov = $stmt->fetch();
        if ($mov && !$this->puedeCambiar('movimientos', $mov)) {
            return;
        }
        Archivos::borrar($mov['comprobante'] ?? null);
        $db->prepare('UPDATE movimientos SET comprobante = NULL WHERE id = ?')->execute([$id]);
        Response::json(['ok' => true]);
    }

    private function subir(string $tipo, int $id): void
    {
        Auth::requireLogin();
        // Un multipart no exige preflight CORS: este encabezado (o el token de la app) frena envíos desde otros sitios.
        if (Auth::bearerToken() === null && ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') !== 'konstruktor') {
            Response::json(['error' => 'Petición no permitida'], 403);
            return;
        }
        [$tabla, $columna] = self::TIPOS[$tipo];
        $db = Database::pdo();
        $stmt = $db->prepare("SELECT $columna, usuario_id FROM $tabla WHERE id = ?");
        $stmt->execute([$id]);
        $anterior = $stmt->fetch();
        if (!$anterior) {
            Response::json(['error' => 'Registro no encontrado'], 404);
            return;
        }
        if (!$this->puedeCambiar($tipo, $anterior)) {
            return;
        }

        [$ruta, $error] = Archivos::guardar($tipo);
        if ($error) {
            Response::json(['error' => $error], 422);
            return;
        }
        $db->prepare("UPDATE $tabla SET $columna = ? WHERE id = ?")->execute([$ruta, $id]);
        Archivos::borrar($anterior[$columna]);
        Response::json(['ok' => true, 'url' => BASE . "/archivos/$tipo/$id"]);
    }

    /**
     * Mismas reglas que el registro: la foto de un avance la cambia quien lo registró o un Admin;
     * el comprobante de un movimiento, cualquiera de la oficina o el ingeniero que lo registró.
     */
    private function puedeCambiar(string $tipo, array $fila): bool
    {
        $propio = (int) $fila['usuario_id'] === (int) Auth::user()['id'];
        $permitido = $tipo === 'avances' ? $propio || Auth::hasRole('Admin') : $propio || !Auth::esIngeniero();
        if (!$permitido) {
            Response::json(['error' => 'Solo quien lo registró puede cambiar este archivo'], 403);
        }

        return $permitido;
    }
}
