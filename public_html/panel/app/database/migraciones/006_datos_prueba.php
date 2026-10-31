<?php

/*
 * Konstruktor — Datos de prueba para revisar el sistema con cada rol (Admin, Cotizador, Ingeniero).
 *
 * - Usuarios: admin@demo.konstruktor.mx, cotizador@demo.konstruktor.mx e ingeniero@demo.konstruktor.mx,
 *   todos con la contraseña Demo2026!
 * - 3 clientes y 5 cotizaciones (DEMO-01 a DEMO-05) en todos los estados. DEMO-01 y DEMO-05 son obras en
 *   ejecución con programa, avance registrado por el ingeniero, gastos, cobros y estimaciones.
 * - Las fechas se calculan desde el día en que se aplica, para que el inicio y las gráficas se vean al día.
 *
 * Todo queda marcado (correo @demo.konstruktor.mx, folio DEMO-) y se borra con un clic desde
 * Configuración → Base de datos → Datos de prueba.
 */

use App\Services\Calculadora;
use App\Services\Estimaciones;

return function (PDO $db): void {
    if ($db->query("SELECT COUNT(*) FROM cotizaciones WHERE folio = 'DEMO-01'")->fetchColumn()) {
        return; // ya están cargados
    }

    $valor = function (string $sql, array $params = []) use ($db) {
        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchColumn();
    };
    $insertar = function (string $tabla, array $fila) use ($db): int {
        $columnas = implode(', ', array_keys($fila));
        $marcas = implode(', ', array_fill(0, count($fila), '?'));
        $db->prepare("INSERT INTO $tabla ($columnas) VALUES ($marcas)")->execute(array_values($fila));

        return (int) $db->lastInsertId();
    };
    $lunes = strtotime('monday this week');
    $dia = fn(int $base, int $dias): string => date('Y-m-d', $base + $dias * 86400);
    $hoy = fn(int $dias): string => date('Y-m-d', strtotime("$dias days"));

    // ------------------------------------------------------------------ usuarios (uno por rol)
    $hash = password_hash('Demo2026!', PASSWORD_BCRYPT);
    $usuario = [];
    foreach ([
        ['Admin', 'Ana Admin (demo)', 'admin@demo.konstruktor.mx'],
        ['Cotizador', 'Carlos Cotizador (demo)', 'cotizador@demo.konstruktor.mx'],
        ['Ingeniero', 'Iván Ingeniero (demo)', 'ingeniero@demo.konstruktor.mx'],
    ] as [$rol, $nombre, $email]) {
        $rolId = $valor('SELECT id FROM roles WHERE nombre = ?', [$rol]);
        if (!$rolId) {
            throw new RuntimeException("Falta el rol $rol");
        }
        $db->prepare('INSERT IGNORE INTO usuarios (nombre, email, password_hash, rol_id) VALUES (?, ?, ?, ?)')
            ->execute([$nombre, $email, $hash, $rolId]);
        $usuario[$rol] = (int) $valor('SELECT id FROM usuarios WHERE email = ?', [$email]);
    }

    // ------------------------------------------------------------------ clientes
    $cliente = [
        'hernandez' => $insertar('clientes', ['nombre' => 'Familia Hernández Ruiz (demo)', 'contacto' => 'Laura Hernández',
            'telefono' => '442 000 0001', 'email' => 'familia.hernandez@demo.konstruktor.mx', 'direccion' => 'Calle Encino 214, Juriquilla']),
        'arcos' => $insertar('clientes', ['nombre' => 'Inmobiliaria Los Arcos (demo)', 'rfc' => 'ILA200101AA1', 'contacto' => 'Ing. Jorge Salas',
            'telefono' => '442 000 0002', 'email' => 'compras@demo.konstruktor.mx', 'direccion' => 'Av. Constituyentes 900, Querétaro']),
        'sonrisa' => $insertar('clientes', ['nombre' => 'Consultorio Dental Sonrisa (demo)', 'contacto' => 'Dra. Paola Méndez',
            'telefono' => '442 000 0003', 'email' => 'consultorio@demo.konstruktor.mx', 'direccion' => 'Blvd. Bernardo Quintana 45, Querétaro']),
    ];

    // ------------------------------------------------------------------ catálogo y análisis
    $insumos = [];
    $insumo = function (string $clave) use (&$insumos, $valor): array {
        if (!isset($insumos[$clave])) {
            $id = $valor('SELECT id FROM insumos WHERE clave = ?', [$clave]);
            if (!$id) {
                throw new RuntimeException("Falta el insumo $clave en el catálogo");
            }
            $insumos[$clave] = ['id' => (int) $id, 'precio' => (float) $valor('SELECT precio FROM insumos WHERE id = ?', [$id])];
        }

        return $insumos[$clave];
    };

    // Análisis de P.U. por unidad de concepto. 'R' = la cuadrilla se calcula por rendimiento (1 / rend).
    $analisis = [
        'plantilla' => ['CEM-50' => 0.15, 'ARENA' => 0.025, 'GRAVA' => 0.03, 'AGUA' => 0.01, 'CUAD-1' => 'R'],
        'zapata' => ['CEM-50' => 0.9, 'ARENA' => 0.1, 'GRAVA' => 0.12, 'VAR-3-8' => 1.2, 'ALA-REC' => 0.3, 'AGUA' => 0.05, 'TAB-30-250' => 0.3, 'CUAD-1' => 'R'],
        'castillo' => ['VAR-3-8' => 0.36667, 'ALA-25' => 0.9984, 'ALA-REC' => 0.2, 'CEM-50' => 0.1575, 'GRAVA' => 0.01197,
            'ARENA' => 0.01496, 'AGUA' => 0.00513, 'TAB-30-250' => 0.20833, 'BAR-250' => 0.20833, 'CUAD-1' => 'R'],
        'muro' => ['TAB-10' => 30, 'MOR-50' => 0.23, 'ARENA' => 0.03, 'AGUA' => 0.01, 'CUAD-1' => 'R'],
        'cadena' => ['VAR-3-8' => 0.4, 'ALA-25' => 1, 'ALA-REC' => 0.2, 'CEM-50' => 0.18, 'GRAVA' => 0.014, 'ARENA' => 0.017, 'TAB-30-250' => 0.25, 'CUAD-1' => 'R'],
        'losa' => ['VIG-320' => 0.5, 'BOV-75' => 5, 'CON-200-B' => 0.07, 'MALL-10-10' => 0.022, 'POL-250' => 0.35, 'EQU-006' => 0.00526, 'CUAD-2' => 'R'],
        'laminado' => ['PI-LAM' => 1.05, 'PEG-CONT' => 0.05, 'CUAD-1' => 'R'],
    ];
    $descripciones = [
        'plantilla' => ["Plantilla de concreto f'c=100 kg/cm² de 5 cm de espesor", 'M2'],
        'zapata' => ['Zapata corrida de concreto armado de 60 cm de ancho', 'ML'],
        'castillo' => ['Castillo de concreto 15x15 cm armado con 4 varillas de 3/8"', 'ML'],
        'muro' => ['Muro de tabicón de 14 cm junteado con mortero', 'M2'],
        'cadena' => ['Cadena de cerramiento de concreto 15x20 cm', 'ML'],
        'losa' => ['Losa de vigueta y bovedilla con capa de compresión de 5 cm', 'M2'],
        'laminado' => ['Piso laminado de 8 mm sobre bajo alfombra', 'M2'],
    ];

    $cotizacion = function (array $datos) use ($insertar, $usuario): int {
        return $insertar('cotizaciones', $datos + [
            'vigencia_dias' => 30, 'pct_indirectos' => 0.08, 'pct_utilidad' => 0.12, 'pct_herramienta' => 0.03, 'pct_iva' => 0.16,
            'pct_anticipo' => 0.30, 'pct_retencion' => 0, 'num_estimaciones' => 3, 'usuario_id' => $usuario['Cotizador'],
            'alcances' => 'Incluye suministro de materiales, mano de obra, herramienta y equipo necesarios para ejecutar los conceptos cotizados.',
            'exclusiones' => 'No incluye trámites, permisos, licencias ni trabajos no indicados en este presupuesto.',
            'notas' => 'Datos de prueba: se pueden borrar desde Configuración → Base de datos.',
        ]);
    };

    /** Crea las partidas y conceptos con su análisis. $partidas = [clave => [nombre, [[clave, tipo, cantidad, rend, inicio], ...]]] */
    $catalogo = function (int $cotId, array $partidas) use ($insertar, $insumo, $analisis, $descripciones): array {
        $ids = [];
        $orden = 0;
        foreach ($partidas as $clavePartida => [$nombre, $conceptos]) {
            $partidaId = $insertar('partidas', ['cotizacion_id' => $cotId, 'orden' => ++$orden, 'clave' => $clavePartida, 'nombre' => $nombre]);
            foreach ($conceptos as $i => [$clave, $tipo, $cantidad, $rend, $inicio]) {
                [$descripcion, $unidad] = $descripciones[$tipo];
                $conceptoId = $insertar('conceptos', ['cotizacion_id' => $cotId, 'partida_id' => $partidaId, 'orden' => $i + 1, 'clave' => $clave,
                    'descripcion' => $descripcion, 'unidad' => $unidad, 'cantidad' => $cantidad, 'rendimiento' => $rend, 'fecha_inicio' => $inicio]);
                foreach ($analisis[$tipo] as $claveInsumo => $consumo) {
                    $ins = $insumo($claveInsumo);
                    $insertar('concepto_insumos', ['concepto_id' => $conceptoId, 'insumo_id' => $ins['id'],
                        'cantidad' => $consumo === 'R' ? 0 : $consumo, 'por_rendimiento' => (int) ($consumo === 'R'), 'precio' => $ins['precio']]);
                }
                $ids[$clave] = ['id' => $conceptoId, 'partida' => $partidaId];
            }
        }
        Calculadora::refrescarTotales($cotId);

        return $ids;
    };

    $avance = fn(int $cotId, array $c, string $fecha, float $cantidad, string $notas) => $insertar('avances', [
        'cotizacion_id' => $cotId, 'concepto_id' => $c['id'], 'fecha' => $fecha, 'cantidad' => $cantidad, 'notas' => $notas, 'usuario_id' => $usuario['Ingeniero'],
    ]);
    $gasto = fn(int $cotId, string $fecha, string $categoria, string $descripcion, float $monto, ?array $c = null, ?string $proveedor = null, string $forma = 'Transferencia', ?int $quien = null) =>
        $insertar('movimientos', [
            'cotizacion_id' => $cotId, 'fecha' => $fecha, 'tipo' => 'egreso', 'categoria' => $categoria, 'partida_id' => $c['partida'] ?? null,
            'concepto_id' => $c['id'] ?? null, 'descripcion' => $descripcion, 'proveedor' => $proveedor, 'forma_pago' => $forma, 'monto' => $monto,
            'usuario_id' => $quien ?? $usuario['Ingeniero'],
        ]);
    $ingreso = fn(int $cotId, string $fecha, string $categoria, string $descripcion, float $monto, ?int $estimacionId = null) => $insertar('movimientos', [
        'cotizacion_id' => $cotId, 'fecha' => $fecha, 'tipo' => 'ingreso', 'categoria' => $categoria, 'estimacion_id' => $estimacionId,
        'descripcion' => $descripcion, 'forma_pago' => 'Transferencia', 'monto' => $monto, 'usuario_id' => $usuario['Admin'],
    ]);
    $puDe = function (int $cotId): array {
        $pu = [];
        foreach (Calculadora::cotizacion($cotId)['partidas'] as $p) {
            foreach ($p['conceptos'] as $c) {
                $pu[$c['id']] = $c['analisis']['pu'];
            }
        }

        return $pu;
    };
    $subtotal = fn(int $cotId) => (float) $valor('SELECT subtotal FROM cotizaciones WHERE id = ?', [$cotId]);

    // ------------------------------------------------------------------ DEMO-01: casa en obra, un poco atrasada
    $s = $lunes - 42 * 86400; // arrancó hace 6 semanas
    $c1 = $cotizacion(['folio' => 'DEMO-01', 'cliente_id' => $cliente['hernandez'], 'obra' => 'Casa habitación 2 niveles (demo)',
        'direccion' => 'Calle Encino 214, Juriquilla', 'municipio' => 'Querétaro, Qro.', 'fecha_cotizacion' => $dia($s, -30),
        'fecha_inicio' => $dia($s, 0), 'estado' => 'aceptada', 'num_estimaciones' => 4, 'usuario_id' => $usuario['Admin']]);
    $k = $catalogo($c1, [
        '1' => ['CIMENTACIÓN', [['1.01', 'plantilla', 85, 25, $dia($s, 0)], ['1.02', 'zapata', 42, 4, $dia($s, 4)]]],
        '2' => ['ESTRUCTURA Y MUROS', [['2.01', 'castillo', 95, 15, $dia($s, 15)], ['2.02', 'muro', 225, 9, $dia($s, 21)], ['2.03', 'cadena', 75, 12, $dia($s, 40)]]],
        '3' => ['LOSAS', [['3.01', 'losa', 78, 6, $dia($s, 47)]]],
    ]);
    foreach ([
        ['1.01', 1, 40, 'Eje A a C'], ['1.01', 3, 45, 'Eje C a F, terminada'],
        ['1.02', 6, 15, 'Ejes 1 y 2'], ['1.02', 10, 15, 'Ejes 3 y 4'], ['1.02', 14, 12, 'Eje 5, terminada'],
        ['2.01', 16, 30, 'Castillos planta baja eje 1 a 3'], ['2.01', 19, 35, 'Eje 4 a 6'], ['2.01', 22, 30, 'Terminados'],
        ['2.02', 24, 30, 'Muro eje 1 de A a C'], ['2.02', 28, 35, 'Muros ejes 2 y 3'], ['2.02', 31, 30, 'Muros ejes 4 y 5'],
        ['2.02', 35, 30, 'Fachada principal'], ['2.02', 38, 25, 'Muros interiores, falta baño y cocina'],
    ] as [$clave, $d, $cantidad, $notas]) {
        $avance($c1, $k[$clave], $dia($s, $d), $cantidad, $notas);
    }
    $ingreso($c1, $dia($s, 0), 'ANTICIPO', 'Anticipo 30% a la firma del contrato', round($subtotal($c1) * 0.30 * 1.16, 2));
    foreach ([
        [0, 'MAT', 'Cemento gris 50 kg (40 bultos)', 9600, '1.02', 'Materiales El Cimiento (demo)'],
        [1, 'MAT', 'Arena y grava, 2 viajes', 6200, '1.01', 'Pétreos del Bajío (demo)'],
        [2, 'EQ', 'Renta de revolvedora de 1 saco, 3 días', 4200, null, 'Rentas La Torre (demo)'],
        [3, 'INDIRECTO', 'Flete de material', 1500, null, null],
        [5, 'MAT', 'Varilla 3/8" (60 piezas)', 9600, '1.02', 'Aceros del Centro (demo)'],
        [15, 'MAT', 'Varilla, alambrón y alambre para castillos', 8450, '2.01', 'Aceros del Centro (demo)'],
        [20, 'MAT', 'Tabicón 9x14x28 (3,500 piezas)', 24360, '2.02', 'Block y Tabicón Querétaro (demo)'],
        [26, 'INDIRECTO', 'Limpieza y retiro de escombro', 2800, null, null],
        [29, 'MAT', 'Tabicón 9x14x28 (3,250 piezas)', 22620, '2.02', 'Block y Tabicón Querétaro (demo)'],
        [30, 'MAT', 'Mortero 50 kg (52 bultos)', 10400, '2.02', 'Materiales El Cimiento (demo)'],
        [39, 'MAT', 'Varilla y armex para cadenas', 7900, '2.03', 'Aceros del Centro (demo)'],
    ] as [$d, $categoria, $descripcion, $monto, $clave, $proveedor]) {
        $gasto($c1, $dia($s, $d), $categoria, $descripcion, $monto, $clave ? $k[$clave] : null, $proveedor, $categoria === 'INDIRECTO' ? 'Efectivo' : 'Transferencia');
    }
    foreach ([15800, 17200, 16400, 18900, 18100, 17600] as $semana => $raya) {
        $gasto($c1, $dia($s, $semana * 7 + 5), 'MO', 'Raya semana ' . ($semana + 1), $raya, null, null, 'Efectivo', $usuario['Admin']);
    }
    $pu = $puDe($c1);
    $estimacion = function (int $cotId, int $numero, string $fecha, string $desde, string $hasta, string $estado, array $lineas) use ($insertar, $k, $pu): int {
        $estId = $insertar('estimaciones', ['cotizacion_id' => $cotId, 'numero' => $numero, 'fecha' => $fecha,
            'periodo_inicio' => $desde, 'periodo_fin' => $hasta, 'estado' => $estado]);
        foreach ($lineas as $clave => $cantidad) {
            $insertar('estimacion_conceptos', ['estimacion_id' => $estId, 'concepto_id' => $k[$clave]['id'], 'cantidad' => $cantidad, 'pu' => $pu[$k[$clave]['id']]]);
        }

        return $estId;
    };
    $e1 = $estimacion($c1, 1, $dia($s, 21), $dia($s, 0), $dia($s, 20), 'pagada', ['1.01' => 85, '1.02' => 42, '2.01' => 60]);
    $estimacion($c1, 2, $dia($s, 35), $dia($s, 21), $dia($s, 34), 'autorizada', ['2.01' => 35, '2.02' => 110]);
    Estimaciones::recalcular($c1);
    $ingreso($c1, $dia($s, 24), 'ESTIMACION', 'Cobro de la estimación 1', (float) $valor('SELECT neto FROM estimaciones WHERE id = ?', [$e1]), $e1);

    // ------------------------------------------------------------------ DEMO-05: cochera, al corriente
    $s5 = $lunes - 14 * 86400;
    $c5 = $cotizacion(['folio' => 'DEMO-05', 'cliente_id' => $cliente['sonrisa'], 'obra' => 'Cochera techada para 2 autos (demo)',
        'direccion' => 'Blvd. Bernardo Quintana 45', 'municipio' => 'Querétaro, Qro.', 'fecha_cotizacion' => $dia($s5, -20),
        'fecha_inicio' => $dia($s5, 0), 'estado' => 'aceptada', 'num_estimaciones' => 2]);
    $k5 = $catalogo($c5, [
        '1' => ['COCHERA', [['1.01', 'plantilla', 36, 25, $dia($s5, 0)], ['1.02', 'castillo', 24, 15, $dia($s5, 2)], ['1.03', 'losa', 36, 4, $dia($s5, 6)]]],
    ]);
    foreach ([['1.01', 1, 36, 'Plantilla completa'], ['1.02', 3, 24, 'Seis castillos'], ['1.03', 8, 12, 'Cimbra y armado'], ['1.03', 11, 14, 'Colado primera mitad'], ['1.03', 13, 10, 'Colado terminado']] as [$clave, $d, $cantidad, $notas]) {
        $avance($c5, $k5[$clave], $dia($s5, $d), $cantidad, $notas);
    }
    $ingreso($c5, $dia($s5, 0), 'ANTICIPO', 'Anticipo 30%', round($subtotal($c5) * 0.30 * 1.16, 2));
    $gasto($c5, $dia($s5, 0), 'MAT', 'Cemento, arena y grava', 6900, $k5['1.01'], 'Materiales El Cimiento (demo)');
    $gasto($c5, $dia($s5, 6), 'MAT', 'Vigueta, bovedilla y malla', 21400, $k5['1.03'], 'Prefabricados del Bajío (demo)');
    $gasto($c5, $dia($s5, 5), 'MO', 'Raya semana 1', 9800, null, null, 'Efectivo', $usuario['Admin']);
    $gasto($c5, $dia($s5, 12), 'MO', 'Raya semana 2', 10400, null, null, 'Efectivo', $usuario['Admin']);

    // ------------------------------------------------------------------ cotizaciones sin obra (Cotizador)
    $c2 = $cotizacion(['folio' => 'DEMO-02', 'cliente_id' => $cliente['arcos'], 'obra' => 'Ampliación de local comercial (demo)',
        'direccion' => 'Av. Constituyentes 900', 'municipio' => 'Querétaro, Qro.', 'fecha_cotizacion' => $hoy(-12), 'fecha_inicio' => $hoy(20), 'estado' => 'enviada']);
    $catalogo($c2, ['1' => ['ALBAÑILERÍA', [['1.01', 'castillo', 40, 15, null], ['1.02', 'muro', 120, 9, null], ['1.03', 'losa', 60, 6, null]]]]);

    $c3 = $cotizacion(['folio' => 'DEMO-03', 'cliente_id' => $cliente['sonrisa'], 'obra' => 'Remodelación de consultorio (demo)',
        'direccion' => 'Blvd. Bernardo Quintana 45', 'municipio' => 'Querétaro, Qro.', 'fecha_cotizacion' => $hoy(-3), 'estado' => 'borrador']);
    $catalogo($c3, ['1' => ['ACABADOS', [['1.01', 'laminado', 45, 20, null]]]]);

    $c4 = $cotizacion(['folio' => 'DEMO-04', 'cliente_id' => $cliente['arcos'], 'obra' => 'Barda perimetral de 40 m (demo)',
        'direccion' => 'Parque Industrial Querétaro', 'municipio' => 'Colón, Qro.', 'fecha_cotizacion' => $hoy(-55), 'estado' => 'rechazada']);
    $catalogo($c4, ['1' => ['BARDA', [['1.01', 'castillo', 30, 15, null], ['1.02', 'muro', 100, 9, null], ['1.03', 'cadena', 40, 12, null]]]]);
};
