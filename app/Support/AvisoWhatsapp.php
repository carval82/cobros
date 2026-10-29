<?php

namespace App\Support;

use App\Models\Cliente;

class AvisoWhatsapp
{
    public static function enlace(Cliente $cliente, int $monto, int $saldo, ?string $proximaFecha, ?int $proximaValor, string $empresa): ?string
    {
        $numero = self::numero($cliente->telefono);
        if ($numero === null) {
            return null;
        }

        $texto = 'Hola '.$cliente->nombre.', registramos tu abono de '.Dinero::formato($monto).'. '
            .'Saldo pendiente: '.Dinero::formato($saldo).'.';

        if ($proximaFecha) {
            $texto .= ' Próxima cuota: '.$proximaFecha;
            if ($proximaValor) {
                $texto .= ' por '.Dinero::formato($proximaValor);
            }
            $texto .= '.';
        } elseif ($saldo <= 0) {
            $texto .= ' El préstamo quedó saldado.';
        }

        $texto .= ' '.$empresa.'.';

        return 'https://wa.me/'.$numero.'?text='.rawurlencode($texto);
    }

    public static function numero(?string $telefono): ?string
    {
        $digitos = preg_replace('/\D+/', '', (string) $telefono) ?? '';

        if ($digitos === '') {
            return null;
        }

        if (str_starts_with($digitos, '57') && strlen($digitos) >= 12) {
            return $digitos;
        }

        if (strlen($digitos) === 10) {
            return '57'.$digitos;
        }

        if (strlen($digitos) >= 11 && strlen($digitos) <= 15) {
            return $digitos;
        }

        return null;
    }
}
