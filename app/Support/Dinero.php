<?php

namespace App\Support;

class Dinero
{
    public static function formato(int|float|string|null $valor): string
    {
        return '$ '.number_format((float) $valor, 0, ',', '.');
    }

    public static function porcentaje(int|float|string|null $valor): string
    {
        $texto = rtrim(rtrim(number_format((float) $valor, 2, '.', ''), '0'), '.');

        return ($texto === '' ? '0' : $texto).'%';
    }
}
