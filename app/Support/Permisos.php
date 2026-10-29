<?php

namespace App\Support;

class Permisos
{
    public const ROLES = [
        'administrador' => 'Administrador',
        'cobrador' => 'Cobrador',
        'consulta' => 'Consulta',
    ];

    public static function de(string $rol): array
    {
        return match ($rol) {
            'cobrador' => [
                'clientes.escribir',
                'prestamos.crear',
                'cobros.registrar',
            ],
            'consulta' => [],
            default => ['*'],
        };
    }
}
