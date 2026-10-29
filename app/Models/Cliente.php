<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Cliente extends Model
{
    protected $fillable = [
        'nombre',
        'documento',
        'telefono',
        'direccion',
        'notas',
    ];

    public function prestamos(): HasMany
    {
        return $this->hasMany(Prestamo::class);
    }

    public function cuotas(): HasManyThrough
    {
        return $this->hasManyThrough(Cuota::class, Prestamo::class);
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class);
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(Movimiento::class);
    }
}
