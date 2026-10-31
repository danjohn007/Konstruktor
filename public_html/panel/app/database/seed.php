<?php

// Ejecutar UNA vez, después de importar schema.sql. Desde la carpeta app/:
//   php database/seed.php admin@konstruktor.mx "MiClaveSegura123!"
// Si no se pasan argumentos usa valores por defecto (cámbialos después del primer login).

declare(strict_types=1);

require __DIR__ . '/../src/autoload.php';

use App\Services\Instalador;

$adminEmail = $argv[1] ?? 'admin@konstruktor.mx';
$adminPassword = $argv[2] ?? 'CambiaEstaClave123!';

foreach (Instalador::run($adminEmail, $adminPassword) as $linea) {
    echo $linea, "\n";
}

if (!isset($argv[2])) {
    echo "Contraseña por defecto: $adminPassword  (cámbiala en cuanto entres)\n";
}
echo "Seed completado.\n";
