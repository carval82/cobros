<?php

namespace App\Support;

use App\Models\Empresa;
use App\Models\User;

class EmpresaActual
{
    public static function restringe(): bool
    {
        $usuario = auth()->user();

        if (! $usuario instanceof User) {
            return false;
        }

        return $usuario->es_maestro || $usuario->empresa_id !== null;
    }

    public static function id(): ?int
    {
        $usuario = auth()->user();

        if (! $usuario instanceof User) {
            return null;
        }

        if ($usuario->es_maestro) {
            $id = session('empresa_id');

            return $id ? (int) $id : null;
        }

        return $usuario->empresa_id ? (int) $usuario->empresa_id : null;
    }

    public static function empresa(): ?Empresa
    {
        $id = self::id();

        return $id ? Empresa::query()->find($id) : null;
    }
}
