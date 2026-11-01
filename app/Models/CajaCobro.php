<?php

namespace App\Models;

use App\Models\Concerns\FiltraEmpresa;
use App\Models\Concerns\UsaBaseTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CajaCobro extends Model
{
    use FiltraEmpresa, UsaBaseTenant;

    protected $table = 'caja_cobros';

    protected $fillable = [
        'empresa_id',
        'user_id',
        'nombre',
        'tipo',
        'capital',
        'abierto',
    ];

    protected function casts(): array
    {
        return [
            'capital' => 'integer',
            'abierto' => 'boolean',
        ];
    }

    public function gastos(): HasMany
    {
        return $this->hasMany(Gasto::class, 'caja_cobro_id');
    }

    public function prestamos(): HasMany
    {
        return $this->hasMany(Prestamo::class, 'caja_cobro_id');
    }

    public function jornadas(): HasMany
    {
        return $this->hasMany(Jornada::class, 'caja_cobro_id');
    }

    public function diaAbierto(?string $fecha = null): bool
    {
        $fecha = $fecha ?? today()->toDateString();
        $jornada = $this->jornadas()->whereDate('fecha', $fecha)->first();

        return $jornada !== null && ! $jornada->cerrada;
    }

    public static function veTodos(User $usuario): bool
    {
        return $usuario->es_maestro
            || $usuario->puede('usuarios.gestionar')
            || $usuario->rol === 'consulta';
    }

    public static function visiblesPara(User $usuario)
    {
        return static::query()
            ->when(! static::veTodos($usuario), fn ($q) => $q->where('user_id', $usuario->id))
            ->orderByRaw("case when tipo = 'administrador' then 0 else 1 end")
            ->orderBy('nombre')
            ->get();
    }

    public static function conDiaAbierto(User $usuario)
    {
        return static::visiblesPara($usuario)
            ->filter(fn (self $caja) => $caja->diaAbierto())
            ->values();
    }
}
