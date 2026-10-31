<?php

declare(strict_types=1);

// Instalador para hosting sin Terminal/SSH: guarda los datos de la base, crea las tablas, carga
// el catálogo inicial y el administrador. Al terminar se borra solo; si el hosting no lo deja,
// BÓRRALO a mano.

require __DIR__ . '/app/src/autoload.php';

use App\Services\Instalador;

function pagina(string $html): never
{
    echo '<!doctype html><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">',
        '<title>Instalar Konstruktor</title>',
        '<div style="font-family:sans-serif;max-width:380px;margin:40px auto;padding:0 16px">', $html, '</div>';
    exit;
}

function e(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

function borrarme(): string
{
    return @unlink(__FILE__)
        ? '<p>Este instalador ya se borró del servidor.</p>'
        : '<p style="color:#b00"><b>Borra seed_web.php del servidor ahora mismo</b> (no se pudo borrar solo).</p>';
}

// Una vez instalado no vuelve a tocar nada: nadie puede cambiar la base ni crear otro administrador.
if (Instalador::instalado()) {
    pagina('<h3>Konstruktor ya está instalado</h3>' . borrarme() . '<p><a href="' . BASE . '/login">Iniciar sesión</a></p>');
}

$v = fn(string $k, string $def = '') => trim((string) ($_POST[$k] ?? $def));
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $adminEmail = $v('email');
    $adminPassword = (string) ($_POST['password'] ?? '');

    if ($v('db_name') === '' || $v('db_user') === '') {
        $error = 'Escribe el nombre de la base de datos y el usuario MySQL.';
    } elseif (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL) || strlen($adminPassword) < 8) {
        $error = 'Escribe un email válido y una contraseña de al menos 8 caracteres.';
    } else {
        $error = Instalador::guardarConexion($v('db_host', 'localhost'), $v('db_name'), $v('db_user'), (string) ($_POST['db_pass'] ?? '')) ?? '';
    }

    if ($error === '') {
        try {
            $log = Instalador::run($adminEmail, $adminPassword);
        } catch (Throwable $e) {
            error_log((string) $e);
            $log = null;
            $error = 'La instalación se detuvo: ' . $e->getMessage();
        }
        if ($log !== null) {
            pagina('<h3>Listo</h3><ul><li>' . implode('</li><li>', array_map('e', $log)) . '</li></ul>'
                . borrarme()
                . '<p><a href="' . BASE . '/login" style="font-size:1.1em">Iniciar sesión</a> con ' . e($adminEmail) . '</p>');
        }
    }
}

$campo = fn(string $etiqueta, string $nombre, string $tipo = 'text', string $extra = '', string $def = '') =>
    '<label>' . $etiqueta . '<br><input type="' . $tipo . '" name="' . $nombre . '" value="' . ($tipo === 'password' ? '' : e($v($nombre, $def))) . '" '
    . $extra . ' style="width:100%;padding:6px;box-sizing:border-box"></label><br><br>';

pagina(
    '<h3>Instalar Konstruktor</h3>'
    . ($error ? '<p style="color:#b00">' . e($error) . '</p>' : '')
    . '<form method="post">'
    . '<h4>1. Base de datos</h4><p style="color:#555;font-size:.9em">Créala antes en cPanel → Bases de datos MySQL, con un usuario con todos los privilegios. Los nombres llevan tu usuario de cPanel delante (p. ej. <i>usuario_konstruktor</i>).</p>'
    . $campo('Nombre de la base', 'db_name', 'text', 'required')
    . $campo('Usuario MySQL', 'db_user', 'text', 'required')
    . $campo('Contraseña MySQL', 'db_pass', 'password')
    . $campo('Servidor', 'db_host', 'text', 'required', 'localhost')
    . '<h4>2. Tu usuario administrador</h4>'
    . $campo('Email', 'email', 'email', 'required')
    . $campo('Contraseña (mínimo 8)', 'password', 'password', 'required minlength="8"')
    . '<button type="submit" style="padding:8px 16px">Instalar</button></form>'
);
