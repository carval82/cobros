<?php

namespace App\Models;

use App\Models\Concerns\FiltraEmpresa;
use App\Models\Concerns\UsaBaseTenant;
use App\Services\PlanPagos;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Prestamo extends Model
{
    use FiltraEmpresa, UsaBaseTenant;

    protected $fillable = [
        'empresa_id',
        'cliente_id',
        'capital',
        'porcentaje',
        'interes',
        'total',
        'numero_cuotas',
        'frecuencia',
        'fecha_primer_pago',
        'nota',
        'estado',
        'detenido_en',
        'motivo_detencion',
    ];

    protected function casts(): array
    {
        return [
            'capital' => 'integer',
            'porcentaje' => 'float',
            'interes' => 'integer',
            'total' => 'integer',
            'numero_cuotas' => 'integer',
            'fecha_primer_pago' => 'date',
            'detenido_en' => 'datetime',
        ];
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function cuotas(): HasMany
    {
        return $this->hasMany(Cuota::class);
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class);
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(Movimiento::class);
    }

    public function frecuenciaNombre(): string
    {
        return PlanPagos::FRECUENCIAS[$this->frecuencia] ?? $this->frecuencia;
    }

    public function saldo(): int
    {
        if ($this->relationLoaded('cuotas')) {
            return (int) $this->cuotas->sum('saldo');
        }

        return (int) $this->cuotas()->sum('saldo');
    }

    public function estaEnMora(): bool
    {
        if ($this->estado !== 'activo') {
            return false;
        }

        $cuotas = $this->relationLoaded('cuotas') ? $this->cuotas : $this->cuotas()->get();

        return $cuotas->contains(fn (Cuota $cuota) => $cuota->saldo > 0 && $cuota->fecha_vencimiento->lt(today()));
    }

    public function admitePagos(): bool
    {
        return in_array($this->estado, ['activo', 'detenido'], true) && $this->saldo() > 0;
    }
}
