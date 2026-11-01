<?php

namespace App\Models;

use App\Models\Concerns\FiltraEmpresa;
use App\Models\Concerns\UsaBaseTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Jornada extends Model
{
    use FiltraEmpresa, UsaBaseTenant;

    protected $fillable = [
        'empresa_id',
        'caja_cobro_id',
        'fecha',
        'valor_inicial',
        'refuerzo',
        'prestado',
        'recaudado',
        'gastos',
        'interes',
        'ganancia',
        'cierre',
        'decision',
        'retiro',
        'valor_siguiente',
        'cerrada',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'valor_inicial' => 'integer',
            'refuerzo' => 'integer',
            'prestado' => 'integer',
            'recaudado' => 'integer',
            'gastos' => 'integer',
            'interes' => 'integer',
            'ganancia' => 'integer',
            'cierre' => 'integer',
            'retiro' => 'integer',
            'valor_siguiente' => 'integer',
            'cerrada' => 'boolean',
        ];
    }

    public function caja(): BelongsTo
    {
        return $this->belongsTo(CajaCobro::class, 'caja_cobro_id');
    }
}
