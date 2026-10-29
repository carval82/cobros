<?php

namespace App\Models;

use App\Models\Concerns\FiltraEmpresa;
use App\Models\Concerns\UsaBaseTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Movimiento extends Model
{
    use FiltraEmpresa, UsaBaseTenant;

    protected $fillable = [
        'empresa_id',
        'cliente_id',
        'prestamo_id',
        'tipo',
        'descripcion',
        'monto',
        'fecha',
    ];

    protected function casts(): array
    {
        return [
            'monto' => 'integer',
            'fecha' => 'date',
        ];
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function prestamo(): BelongsTo
    {
        return $this->belongsTo(Prestamo::class);
    }
}
