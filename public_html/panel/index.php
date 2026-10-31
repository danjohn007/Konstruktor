<?php

declare(strict_types=1);

require __DIR__ . '/app/src/autoload.php';

// La app móvil se autentica con token; solo el navegador usa la sesión PHP.
if (\App\Core\Auth::bearerToken() === null) {
    session_set_cookie_params(['path' => BASE . '/', 'httponly' => true, 'samesite' => 'Lax']);
    session_start();
}

use App\Core\Router;
use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\ClientesController;
use App\Controllers\InsumosController;
use App\Controllers\CuadrillasController;
use App\Controllers\CotizacionesController;
use App\Controllers\ConfiguracionController;
use App\Controllers\EstimacionesController;
use App\Controllers\ObraController;
use App\Controllers\UsuariosController;
use App\Controllers\AvanceController;
use App\Controllers\ArchivosController;

// Cualquier error no previsto: se registra en el log del servidor y al
// navegador solo llega un mensaje genérico (sin rutas ni SQL).
set_exception_handler(function (Throwable $e): void {
    error_log((string) $e);
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '';
    if (str_contains($path, '/api/')) {
        \App\Core\Response::json(['error' => 'Error interno del servidor'], 500);
    } else {
        http_response_code(500);
        echo 'Error interno del servidor';
    }
});

// Recién subido al hosting y sin instalar: llevar al instalador en vez de mostrar un error.
if (is_file(__DIR__ . '/seed_web.php') && !\App\Services\Instalador::instalado()) {
    header('Location: ' . BASE . '/seed_web.php');
    exit;
}

// Cambios pendientes a la base (app/database/migraciones): se aplican solos al subir una versión
// nueva. Si uno falla queda anotado en Configuración y la petición sigue.
try {
    \App\Services\Migraciones::alDia();
} catch (Throwable $e) {
    error_log('Migraciones: ' . $e->getMessage());
}

$router = new Router();

// Ingeniero (residente de obra): ve las obras aceptadas que tiene asignadas (obra_ingenieros) y registra avance y gastos con sus fotos.
// Lo que no está aquí le responde 403, así que una ruta nueva queda cerrada para él hasta agregarla.
const RUTAS_INGENIERO = [
    'GET /login', 'POST /logout', 'GET /', 'GET /api/ingeniero/resumen', 'GET /cotizaciones', 'GET /cotizaciones/{id}', 'GET /archivos/{tipo}/{id}',
    'POST /api/auth/login', 'POST /api/auth/token', 'DELETE /api/auth/token', 'GET /api/auth/yo', 'PUT /api/mi-password',
    'GET /api/obras', 'GET /api/cotizaciones', 'GET /api/cotizaciones/{id}',
    'GET /api/cotizaciones/{id}/avance', 'POST /api/cotizaciones/{id}/avances',
    'PUT /api/avances/{id}', 'DELETE /api/avances/{id}', 'POST /api/avances/{id}/foto',
    'GET /api/cotizaciones/{id}/control', 'POST /api/cotizaciones/{id}/movimientos', 'PUT /api/movimientos/{id}',
    'POST /api/movimientos/{id}/comprobante', 'DELETE /api/movimientos/{id}/comprobante',
];
$router->guard(function (string $method, string $pattern, array $params): bool {
    if (!\App\Core\Auth::esIngeniero()) {
        return true;
    }
    $permitida = in_array("$method $pattern", RUTAS_INGENIERO, true);
    // De las cotizaciones solo ve las obras (aceptadas) que tiene asignadas.
    if ($permitida && str_contains($pattern, 'cotizaciones/{id}')) {
        $permitida = CotizacionesController::estado((int) $params[0]) === 'aceptada'
            && CotizacionesController::asignada((int) $params[0], (int) \App\Core\Auth::user()['id']);
    }
    if ($permitida) {
        return true;
    }
    if (str_starts_with($pattern, '/api/')) {
        \App\Core\Response::json(['error' => 'Tu usuario (Ingeniero) no tiene acceso a esta acción'], 403);
    } else {
        header('Location: ' . BASE . '/cotizaciones');
    }
    return false;
});

// --- Páginas web ---
$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/logout', [AuthController::class, 'logout']);
$router->get('/', [DashboardController::class, 'index']);
$router->get('/cotizaciones', [CotizacionesController::class, 'index']);
$router->get('/cotizaciones/{id}', [CotizacionesController::class, 'show']);
$router->get('/cotizaciones/{id}/presupuesto', [CotizacionesController::class, 'presupuesto']);
$router->get('/clientes', [ClientesController::class, 'index']);
$router->get('/insumos', [InsumosController::class, 'index']);
$router->get('/cuadrillas', [CuadrillasController::class, 'index']);
$router->get('/configuracion', [ConfiguracionController::class, 'index']);
$router->get('/usuarios', [UsuariosController::class, 'index']);
$router->get('/estimaciones/{id}', [EstimacionesController::class, 'show']);
$router->get('/estimaciones/{id}/imprimir', [EstimacionesController::class, 'imprimir']);
$router->get('/archivos/{tipo}/{id}', [ArchivosController::class, 'ver']);

// --- API ---
$router->post('/api/auth/login', [AuthController::class, 'apiLogin']);
$router->post('/api/auth/token', [AuthController::class, 'apiToken']);
$router->delete('/api/auth/token', [AuthController::class, 'apiRevocarToken']);
$router->get('/api/auth/yo', [AuthController::class, 'apiYo']);
$router->get('/api/dashboard/resumen', [DashboardController::class, 'apiResumen']);
$router->get('/api/ingeniero/resumen', [DashboardController::class, 'apiIngeniero']);

$router->get('/api/clientes', [ClientesController::class, 'apiIndex']);
$router->post('/api/clientes', [ClientesController::class, 'apiStore']);
$router->put('/api/clientes/{id}', [ClientesController::class, 'apiUpdate']);
$router->delete('/api/clientes/{id}', [ClientesController::class, 'apiDelete']);

$router->get('/api/insumos', [InsumosController::class, 'apiIndex']);
$router->get('/api/insumos/categorias', [InsumosController::class, 'apiCategorias']);
$router->post('/api/insumos', [InsumosController::class, 'apiStore']);
$router->put('/api/insumos/{id}', [InsumosController::class, 'apiUpdate']);
$router->delete('/api/insumos/{id}', [InsumosController::class, 'apiDelete']);

$router->get('/api/cuadrillas', [CuadrillasController::class, 'apiIndex']);
$router->post('/api/cuadrillas', [CuadrillasController::class, 'apiStore']);
$router->put('/api/cuadrillas/{id}', [CuadrillasController::class, 'apiUpdate']);
$router->delete('/api/cuadrillas/{id}', [CuadrillasController::class, 'apiDelete']);

$router->get('/api/configuracion', [ConfiguracionController::class, 'apiShow']);
$router->put('/api/configuracion', [ConfiguracionController::class, 'apiUpdate']);
$router->get('/api/configuracion/migraciones', [ConfiguracionController::class, 'apiMigraciones']);
$router->post('/api/configuracion/migraciones', [ConfiguracionController::class, 'apiAplicarMigraciones']);
$router->get('/api/configuracion/datos-prueba', [ConfiguracionController::class, 'apiDatosPrueba']);
$router->delete('/api/configuracion/datos-prueba', [ConfiguracionController::class, 'apiBorrarDatosPrueba']);

$router->get('/api/cotizaciones', [CotizacionesController::class, 'apiIndex']);
$router->post('/api/cotizaciones', [CotizacionesController::class, 'apiStore']);
$router->get('/api/cotizaciones/{id}', [CotizacionesController::class, 'apiShow']);
$router->put('/api/cotizaciones/{id}', [CotizacionesController::class, 'apiUpdate']);
$router->delete('/api/cotizaciones/{id}', [CotizacionesController::class, 'apiDelete']);
$router->post('/api/cotizaciones/{id}/actualizar-precios', [CotizacionesController::class, 'apiActualizarPrecios']);
$router->get('/api/cotizaciones/{id}/ingenieros', [CotizacionesController::class, 'apiIngenieros']);
$router->put('/api/cotizaciones/{id}/ingenieros', [CotizacionesController::class, 'apiAsignarIngenieros']);
$router->post('/api/cotizaciones/{id}/partidas', [CotizacionesController::class, 'apiStorePartida']);

$router->put('/api/partidas/{id}', [CotizacionesController::class, 'apiUpdatePartida']);
$router->delete('/api/partidas/{id}', [CotizacionesController::class, 'apiDeletePartida']);
$router->post('/api/partidas/{id}/conceptos', [CotizacionesController::class, 'apiStoreConcepto']);

$router->put('/api/conceptos/{id}', [CotizacionesController::class, 'apiUpdateConcepto']);
$router->delete('/api/conceptos/{id}', [CotizacionesController::class, 'apiDeleteConcepto']);
$router->post('/api/conceptos/{id}/insumos', [CotizacionesController::class, 'apiStoreInsumo']);
$router->post('/api/conceptos/{id}/volumenes', [CotizacionesController::class, 'apiStoreVolumen']);

$router->put('/api/concepto-insumos/{id}', [CotizacionesController::class, 'apiUpdateInsumo']);
$router->delete('/api/concepto-insumos/{id}', [CotizacionesController::class, 'apiDeleteInsumo']);
$router->put('/api/volumenes/{id}', [CotizacionesController::class, 'apiUpdateVolumen']);
$router->delete('/api/volumenes/{id}', [CotizacionesController::class, 'apiDeleteVolumen']);

// --- Fase 2: estimaciones, control de obra y usuarios ---
$router->get('/api/cotizaciones/{id}/estimaciones', [EstimacionesController::class, 'apiIndex']);
$router->post('/api/cotizaciones/{id}/estimaciones', [EstimacionesController::class, 'apiStore']);
$router->get('/api/estimaciones/{id}', [EstimacionesController::class, 'apiShow']);
$router->put('/api/estimaciones/{id}', [EstimacionesController::class, 'apiUpdate']);
$router->delete('/api/estimaciones/{id}', [EstimacionesController::class, 'apiDelete']);
$router->put('/api/estimaciones/{id}/cantidad', [EstimacionesController::class, 'apiCantidad']);
$router->post('/api/estimaciones/{id}/cobro', [EstimacionesController::class, 'apiCobro']);

$router->get('/api/cotizaciones/{id}/control', [ObraController::class, 'apiControl']);
$router->post('/api/cotizaciones/{id}/movimientos', [ObraController::class, 'apiStore']);
$router->put('/api/movimientos/{id}', [ObraController::class, 'apiUpdate']);
$router->delete('/api/movimientos/{id}', [ObraController::class, 'apiDelete']);

// --- Fase 3: app móvil, avance diario, compras y flujo de efectivo ---
$router->get('/api/obras', [ObraController::class, 'apiObras']);
$router->post('/api/movimientos/{id}/comprobante', [ArchivosController::class, 'apiSubirComprobante']);
$router->delete('/api/movimientos/{id}/comprobante', [ArchivosController::class, 'apiBorrarComprobante']);

$router->get('/api/cotizaciones/{id}/avance', [AvanceController::class, 'apiAvance']);
$router->post('/api/cotizaciones/{id}/avances', [AvanceController::class, 'apiStore']);
$router->put('/api/avances/{id}', [AvanceController::class, 'apiUpdate']);
$router->delete('/api/avances/{id}', [AvanceController::class, 'apiDelete']);
$router->post('/api/avances/{id}/foto', [ArchivosController::class, 'apiSubirFoto']);
$router->get('/api/cotizaciones/{id}/flujo', [AvanceController::class, 'apiFlujo']);

$router->get('/api/usuarios', [UsuariosController::class, 'apiIndex']);
$router->post('/api/usuarios', [UsuariosController::class, 'apiStore']);
$router->put('/api/usuarios/{id}', [UsuariosController::class, 'apiUpdate']);
$router->put('/api/mi-password', [UsuariosController::class, 'apiMiPassword']);

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$router->dispatch($_SERVER['REQUEST_METHOD'], substr($uri, strlen(BASE)) ?: '/');
