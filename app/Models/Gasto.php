<?php

namespace App\Models;

use App\Models\Concerns\FiltraEmpresa;
use App\Models\Concerns\UsaBaseTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Gasto extends Model
{
    use FiltraEmpresa, UsaBaseTenant;

    protected $fillable = [
        'empresa_id',
        'caja_cobro_id',
        'user_id',
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

    public function caja(): BelongsTo
    {
        return $this->belongsTo(CajaCobro::class, 'caja_cobro_id');
    }
}
