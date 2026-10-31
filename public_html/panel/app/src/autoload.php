<?php

// Carpeta del sistema dentro del dominio: '' si está en la raíz, '/panel' en konstruktor.com.mx/panel.
// Las vistas la ponen en <base href> y las redirecciones de PHP la anteponen.
define('BASE', PHP_SAPI === 'cli' ? '' : rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/'));

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
