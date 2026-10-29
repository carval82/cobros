<?php

namespace App\Models;

use App\Models\Concerns\FiltraEmpresa;
use App\Models\Concerns\UsaBaseTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pago extends Model
{
    use FiltraEmpresa, UsaBaseTenant;

    protected $fillable = [
        'empresa_id',
        'prestamo_id',
        'cliente_id',
        'monto',
        'fecha',
        'nota',
        'latitud',
        'longitud',
    ];

    protected function casts(): array
    {
        return [
            'monto' => 'integer',
            'fecha' => 'date',
            'latitud' => 'float',
            'longitud' => 'float',
        ];
    }

    public function prestamo(): BelongsTo
    {
        return $this->belongsTo(Prestamo::class);
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function aplicaciones(): HasMany
    {
        return $this->hasMany(PagoCuota::class);
    }
}
