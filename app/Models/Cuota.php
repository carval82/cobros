<?php

namespace App\Models;

use App\Models\Concerns\UsaBaseTenant;
use App\Support\EmpresaActual;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cuota extends Model
{
    use UsaBaseTenant;

    protected static function booted(): void
    {
        static::addGlobalScope('empresa', function (Builder $query) {
            if (! EmpresaActual::restringe()) {
                return;
            }

            $query->whereHas('prestamo', function (Builder $prestamo) {
                $prestamo->where('prestamos.empresa_id', EmpresaActual::id() ?? 0);
            });
        });
    }

    protected $fillable = [
        'prestamo_id',
        'numero',
        'fecha_vencimiento',
        'capital',
        'interes',
        'valor',
        'pagado',
        'saldo',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'numero' => 'integer',
            'fecha_vencimiento' => 'date',
            'capital' => 'integer',
            'interes' => 'integer',
            'valor' => 'integer',
            'pagado' => 'integer',
            'saldo' => 'integer',
        ];
    }

    public function prestamo(): BelongsTo
    {
        return $this->belongsTo(Prestamo::class);
    }

    public function aplicaciones(): HasMany
    {
        return $this->hasMany(PagoCuota::class);
    }

    public function estaAtrasada(): bool
    {
        if ($this->saldo <= 0 || $this->fecha_vencimiento->gte(today())) {
            return false;
        }

        $prestamo = $this->relationLoaded('prestamo') ? $this->prestamo : $this->prestamo()->first();

        return $prestamo?->estado === 'activo';
    }

    public function estadoVisible(): string
    {
        if ($this->saldo <= 0) {
            return 'pagada';
        }

        if ($this->prestamo?->estado === 'detenido') {
            return 'detenida';
        }

        if ($this->estaAtrasada()) {
            return 'vencida';
        }

        if ($this->pagado > 0) {
            return 'parcial';
        }

        return 'pendiente';
    }

    public function diasAtraso(): int
    {
        if (! $this->estaAtrasada()) {
            return 0;
        }

        return (int) round($this->fecha_vencimiento->diffInDays(today(), true));
    }
}
