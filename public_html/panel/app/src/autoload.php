<?php

// Carpeta del sistema dentro del dominio: '' si está en la raíz, '/panel' en konstruktor.com.mx/panel.
// Las vistas la ponen en <base href> y las redirecciones de PHP la anteponen.
define('BASE', PHP_SAPI === 'cli' ? '' : rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/'));

/**
 * Ruta de un archivo de assets/ con su versión (fecha de modificación). El hosting le pide al
 * navegador guardarlos una semana; así, al subir una versión nueva se descarga la nueva y no la guardada.
 */
function recurso(string $ruta): string
{
    $archivo = dirname(__DIR__, 2) . '/' . $ruta;

    return $ruta . (is_file($archivo) ? '?v=' . filemtime($archivo) : '');
}

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $file = __DIR__ . '/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($file)) {
        require $file;
    }
});
