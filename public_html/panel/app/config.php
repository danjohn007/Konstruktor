<?php

// El instalador (seed_web.php) guarda aquí los datos de la base del hosting. Es un archivo
// aparte para que subir una versión nueva del sistema no los borre.
if (is_file(__DIR__ . '/config.local.php')) {
    return require __DIR__ . '/config.local.php';
}

return [
    'db' => [
        'host' => getenv('DB_HOST') ?: '127.0.0.1',
        'port' => getenv('DB_PORT') ?: '3306',
        'name' => getenv('DB_NAME') ?: 'konstruktor',
        'user' => getenv('DB_USER') ?: 'root',
        'pass' => getenv('DB_PASS') ?: '',
        'charset' => 'utf8mb4',
    ],
    'app' => [
        'name' => 'Konstruktor',
    ],
];
