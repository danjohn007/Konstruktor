<?php

namespace App\Services;

use App\Core\Config;
use App\Core\Database;

/**
 * Costo por jornada de una cuadrilla, igual que en la hoja BASICOS del Excel:
 * nómina + aguinaldo + prima vacacional + ISN + cuotas patronales.
 */
class Nomina
{
    public static function costoJornada(array $integrantes): array
    {
        $c = Config::all();
        $diasSemana = max(1.0, (float) $c['dias_semana']);

        $nomina = 0.0;
        foreach ($integrantes as $i) {
            $nomina += (float) $i['cantidad'] * (float) $i['salario_semanal'] / $diasSemana;
        }

        $gratificacion = $nomina * (float) $c['dias_gratificacion'] / 360;
        $prima = $nomina * (float) $c['dias_prima_vacacional'] / 360;
        $isn = $nomina * (float) $c['tasa_isn'];
        $baseCuotas = ($nomina + $gratificacion + $prima) * (float) $c['pct_cuotas'];
        $imss = $baseCuotas * (float) $c['tasa_imss'];
        $infonavit = $baseCuotas * (float) $c['tasa_infonavit'];
        $afore = $baseCuotas * (float) $c['tasa_afore'];

        $total = $nomina + $gratificacion + $prima + $isn + $imss + $infonavit + $afore;

        return [
            'nomina' => round($nomina, 2),
            'gratificacion' => round($gratificacion, 2),
            'prima_vacacional' => round($prima, 2),
            'isn' => round($isn, 2),
            'imss' => round($imss, 2),
            'infonavit' => round($infonavit, 2),
            'afore' => round($afore, 2),
            'total' => round($total, 2),
            'factor' => $nomina > 0 ? round($total / $nomina, 4) : 0,
        ];
    }

    /** Recalcula el costo de la cuadrilla y actualiza su insumo de mano de obra. */
    public static function sincronizar(int $cuadrillaId): void
    {
        $db = Database::pdo();
        $cuad = $db->prepare('SELECT * FROM cuadrillas WHERE id = ?');
        $cuad->execute([$cuadrillaId]);
        $cuad = $cuad->fetch();
        if (!$cuad) {
            return;
        }

        $int = $db->prepare('SELECT * FROM cuadrilla_integrantes WHERE cuadrilla_id = ?');
        $int->execute([$cuadrillaId]);
        $costo = self::costoJornada($int->fetchAll())['total'];

        $existe = $db->prepare('SELECT id FROM insumos WHERE cuadrilla_id = ?');
        $existe->execute([$cuadrillaId]);
        $insumoId = $existe->fetchColumn();

        if ($insumoId) {
            $db->prepare('UPDATE insumos SET clave=?, descripcion=?, precio_captura=?, precio=? WHERE id=?')
                ->execute([$cuad['clave'], $cuad['nombre'], $costo, $costo, $insumoId]);
        } else {
            $db->prepare(
                "INSERT INTO insumos (clave, descripcion, unidad, tipo, categoria, precio_captura, precio, cuadrilla_id)
                 VALUES (?, ?, 'JOR', 'MO', 'MANO DE OBRA', ?, ?, ?)"
            )->execute([$cuad['clave'], $cuad['nombre'], $costo, $costo, $cuadrillaId]);
        }
    }

    public static function sincronizarTodas(): void
    {
        foreach (Database::pdo()->query('SELECT id FROM cuadrillas')->fetchAll() as $c) {
            self::sincronizar((int) $c['id']);
        }
    }
}
