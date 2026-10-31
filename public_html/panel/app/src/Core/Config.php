<?php

namespace App\Core;

/**
 * Parámetros generales de la empresa (equivalen a la hoja DATOS del Excel).
 * Cada cotización copia los porcentajes al crearse, para que un cambio
 * posterior no altere cotizaciones ya enviadas.
 */
class Config
{
    public const DEFAULTS = [
        'empresa_nombre' => 'KONSTRUKTOR',
        'empresa_rfc' => '',
        'empresa_direccion' => '',
        'empresa_telefono' => '',
        'empresa_email' => '',
        'responsable' => '',
        'cargo' => '',
        'folio_prefijo' => 'KON',
        'pct_indirectos' => '0.12',
        'pct_utilidad' => '0.08',
        'pct_herramienta' => '0.03',
        'pct_iva' => '0.16',
        'pct_iva_insumos' => '0.16',
        'pct_anticipo' => '0.30',
        'pct_retencion' => '0',
        'num_estimaciones' => '2',
        'vigencia_dias' => '30',
        // Factor de salario de las cuadrillas (valores de la plantilla Excel)
        'pct_cuotas' => '1',
        'tasa_imss' => '0.30',
        'tasa_infonavit' => '0.05',
        'tasa_afore' => '0.02',
        'tasa_isn' => '0.02',
        'dias_gratificacion' => '16.5',
        'dias_prima_vacacional' => '12',
        'dias_semana' => '5',
        'alcances' => 'Incluye suministro de materiales, mano de obra, herramienta y equipo necesarios para ejecutar los conceptos cotizados.',
        'exclusiones' => 'No incluye trámites, permisos, licencias ni trabajos no indicados en este presupuesto.',
        'notas' => 'Precios en moneda nacional. Las cantidades son estimadas; se pagará lo realmente ejecutado.',
    ];

    public const NUMERICOS = [
        'pct_indirectos', 'pct_utilidad', 'pct_herramienta', 'pct_iva', 'pct_iva_insumos', 'pct_anticipo', 'pct_retencion',
        'num_estimaciones', 'vigencia_dias', 'pct_cuotas', 'tasa_imss', 'tasa_infonavit', 'tasa_afore',
        'tasa_isn', 'dias_gratificacion', 'dias_prima_vacacional', 'dias_semana',
    ];

    private static ?array $cache = null;

    public static function all(): array
    {
        if (self::$cache === null) {
            $rows = Database::pdo()->query('SELECT clave, valor FROM configuracion')->fetchAll();
            self::$cache = self::DEFAULTS;
            foreach ($rows as $r) {
                self::$cache[$r['clave']] = $r['valor'];
            }
        }

        return self::$cache;
    }

    public static function get(string $clave): string
    {
        return (string) (self::all()[$clave] ?? '');
    }

    public static function num(string $clave): float
    {
        return (float) self::get($clave);
    }

    public static function save(array $valores): void
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO configuracion (clave, valor) VALUES (?, ?) ON DUPLICATE KEY UPDATE valor = VALUES(valor)'
        );
        foreach ($valores as $clave => $valor) {
            if (array_key_exists($clave, self::DEFAULTS)) {
                $stmt->execute([$clave, (string) $valor]);
            }
        }
        self::$cache = null;
    }
}
