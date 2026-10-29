<?php

namespace App\Models;

use App\Models\Concerns\FiltraEmpresa;
use App\Models\Concerns\UsaBaseTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Cliente extends Model
{
    use FiltraEmpresa, UsaBaseTenant;

    protected $fillable = [
        'empresa_id',
        'nombre',
        'documento',
        'telefono',
        'direccion',
        'notas',
        'latitud',
        'longitud',
    ];

    protected function casts(): array
    {
        return [
            'latitud' => 'float',
            'longitud' => 'float',
        ];
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

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
