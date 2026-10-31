<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Config;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Services\Archivos;
use App\Services\Migraciones;
use App\Services\Nomina;

class ConfiguracionController
{
    public function index(): void
    {
        Auth::requireRole('Admin');
        Response::view('configuracion/index', ['user' => Auth::user(), 'titulo' => 'Configuración', 'subtitulo' => 'Datos de la empresa, porcentajes por defecto y textos del presupuesto']);
    }

    public function apiShow(): void
    {
        Auth::requireLogin();
        Response::json(Config::all());
    }

    public function apiUpdate(): void
    {
        Auth::requireRole('Admin');
        $d = Request::json();

        $valores = [];
        foreach (Config::DEFAULTS as $clave => $_) {
            if (!array_key_exists($clave, $d)) {
                continue;
            }
            if (in_array($clave, Config::NUMERICOS, true)) {
                $n = Request::num($d[$clave]);
                if ($n === null || $n < 0) {
                    Response::json(['error' => "Valor inválido en $clave"], 422);
                    return;
                }
                $valores[$clave] = $n;
            } else {
                $valores[$clave] = trim((string) $d[$clave]);
            }
        }

        Config::save($valores);
        // Los precios de cuadrillas e insumos sin IVA dependen de estos parámetros.
        Nomina::sincronizarTodas();
        InsumosController::recalcularMasIva();

        Response::json(['ok' => true]);
    }

    /** Migraciones de la base: cuáles se aplicaron y cuáles fallaron. */
    public function apiMigraciones(): void
    {
        Auth::requireRole('Admin');
        Response::json(Migraciones::estado());
    }

    /** Aplica ya las pendientes, sin esperar el reintento automático. */
    public function apiAplicarMigraciones(): void
    {
        Auth::requireRole('Admin');
        Request::json(); // solo application/json: frena CSRF como el resto de escrituras
        $log = Migraciones::aplicar();
        Response::json(['log' => $log, 'migraciones' => Migraciones::estado()]);
    }

    // Datos de prueba (migración 006): usuarios y clientes con correo @demo.konstruktor.mx y cotizaciones DEMO-xx.
    private const CORREO_DEMO = '%@demo.konstruktor.mx';
    private const FOLIO_DEMO = 'DEMO-%';

    public function apiDatosPrueba(): void
    {
        Auth::requireRole('Admin');
        $db = Database::pdo();
        $cuenta = function (string $sql, string $patron) use ($db): int {
            $stmt = $db->prepare($sql);
            $stmt->execute([$patron]);

            return (int) $stmt->fetchColumn();
        };
        Response::json([
            'usuarios' => $cuenta('SELECT COUNT(*) FROM usuarios WHERE email LIKE ?', self::CORREO_DEMO),
            'clientes' => $cuenta('SELECT COUNT(*) FROM clientes WHERE email LIKE ?', self::CORREO_DEMO),
            'cotizaciones' => $cuenta('SELECT COUNT(*) FROM cotizaciones WHERE folio LIKE ?', self::FOLIO_DEMO),
        ]);
    }

    /** Borra todo lo de prueba (con sus fotos y comprobantes), menos al usuario con el que se está trabajando. */
    public function apiBorrarDatosPrueba(): void
    {
        Auth::requireRole('Admin');
        $db = Database::pdo();
        $archivos = $db->prepare(
            'SELECT foto FROM avances a JOIN cotizaciones c ON c.id = a.cotizacion_id WHERE c.folio LIKE ? AND a.foto IS NOT NULL
             UNION ALL
             SELECT comprobante FROM movimientos m JOIN cotizaciones c ON c.id = m.cotizacion_id WHERE c.folio LIKE ? AND m.comprobante IS NOT NULL'
        );
        $archivos->execute([self::FOLIO_DEMO, self::FOLIO_DEMO]);
        $archivos = $archivos->fetchAll(\PDO::FETCH_COLUMN);

        $db->beginTransaction();
        $db->prepare('DELETE FROM cotizaciones WHERE folio LIKE ?')->execute([self::FOLIO_DEMO]);
        $db->prepare('DELETE FROM clientes WHERE email LIKE ?')->execute([self::CORREO_DEMO]);
        $db->prepare('DELETE FROM usuarios WHERE email LIKE ? AND id <> ?')->execute([self::CORREO_DEMO, Auth::user()['id']]);
        $db->commit();

        foreach ($archivos as $ruta) {
            Archivos::borrar($ruta);
        }
        $this->apiDatosPrueba();
    }
}
