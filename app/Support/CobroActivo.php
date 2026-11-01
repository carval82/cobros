<?php

namespace App\Support;

use App\Models\CajaCobro;
use App\Models\User;

class CobroActivo
{
    public const LLAVE = 'caja_cobro_id';

    public static function id(): ?int
    {
        $id = session(self::LLAVE);

        return $id ? (int) $id : null;
    }

    public static function caja(): ?CajaCobro
    {
        $usuario = auth()->user();
        $id = self::id();
        if (! $usuario instanceof User || ! $id) {
            return null;
        }

        $caja = CajaCobro::visiblesPara($usuario)->firstWhere('id', $id);
        if (! $caja) {
            session()->forget(self::LLAVE);

            return null;
        }

        return $caja;
    }

    public static function entrar(CajaCobro $caja): void
    {
        session([self::LLAVE => $caja->id]);
    }

    public static function salir(): void
    {
        session()->forget(self::LLAVE);
    }
}
