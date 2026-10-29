<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pago extends Model
{
    protected $fillable = [
        'prestamo_id',
        'cliente_id',
        'monto',
        'fecha',
        'nota',
    ];

    protected function casts(): array
    {
        return [
            'monto' => 'integer',
            'fecha' => 'date',
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
