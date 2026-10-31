<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Config;
use App\Core\Request;
use App\Core\Response;
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
}
