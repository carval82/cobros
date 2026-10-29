<?php

namespace App\Services;

use Carbon\Carbon;
use InvalidArgumentException;

class PlanPagos
{
    public const FRECUENCIAS = [
        'diario' => 'Diario',
        'semanal' => 'Semanal',
        'quincenal' => 'Quincenal',
        'mensual' => 'Mensual',
    ];

    /**
     * Interés fijo sobre el capital de todo el préstamo.
     * 100.000 al 20% en 5 cuotas: interés 20.000, total 120.000, cuota 24.000.
     *
     * @return array{
     *     capital: int,
     *     porcentaje: float,
     *     interes: int,
     *     total: int,
     *     cuotas: int,
     *     frecuencia: string,
     *     valor_cuota: int,
     *     valor_ultima: int,
     *     filas: list<array{numero: int, fecha: string, capital: int, interes: int, valor: int, saldo: int}>
     * }
     */
    public static function simular(int $capital, float $porcentaje, int $cuotas, string $frecuencia, string $fechaPrimerPago): array
    {
        if ($capital < 1) {
            throw new InvalidArgumentException('El capital debe ser mayor que cero.');
        }
        if ($porcentaje < 0) {
            throw new InvalidArgumentException('El porcentaje no puede ser negativo.');
        }
        if ($cuotas < 1) {
            throw new InvalidArgumentException('Debe haber al menos una cuota.');
        }
        if (! isset(self::FRECUENCIAS[$frecuencia])) {
            throw new InvalidArgumentException('La frecuencia no es válida.');
        }

        $interes = (int) round($capital * $porcentaje / 100);
        $total = $capital + $interes;
        $capitalBase = intdiv($capital, $cuotas);
        $capitalResiduo = $capital % $cuotas;
        $interesBase = intdiv($interes, $cuotas);
        $interesResiduo = $interes % $cuotas;

        $fecha = Carbon::parse($fechaPrimerPago)->startOfDay();
        $filas = [];
        $saldo = $total;

        for ($i = 1; $i <= $cuotas; $i++) {
            $esUltima = $i === $cuotas;
            $parteCapital = $capitalBase + ($esUltima ? $capitalResiduo : 0);
            $parteInteres = $interesBase + ($esUltima ? $interesResiduo : 0);
            $valor = $parteCapital + $parteInteres;
            $saldo -= $valor;

            $filas[] = [
                'numero' => $i,
                'fecha' => $fecha->toDateString(),
                'capital' => $parteCapital,
                'interes' => $parteInteres,
                'valor' => $valor,
                'saldo' => $saldo,
            ];

            $fecha = self::avanzar($fecha, $frecuencia);
        }

        return [
            'capital' => $capital,
            'porcentaje' => $porcentaje,
            'interes' => $interes,
            'total' => $total,
            'cuotas' => $cuotas,
            'frecuencia' => $frecuencia,
            'valor_cuota' => $filas[0]['valor'],
            'valor_ultima' => $filas[$cuotas - 1]['valor'],
            'filas' => $filas,
        ];
    }

    public static function avanzar(Carbon $fecha, string $frecuencia): Carbon
    {
        return match ($frecuencia) {
            'diario' => $fecha->copy()->addDay(),
            'semanal' => $fecha->copy()->addWeek(),
            'quincenal' => $fecha->copy()->addDays(15),
            'mensual' => $fecha->copy()->addMonthNoOverflow(),
            default => throw new InvalidArgumentException('La frecuencia no es válida.'),
        };
    }
}
