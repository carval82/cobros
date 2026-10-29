<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Empresa extends Model
{
    protected $fillable = [
        'nombre',
        'slug',
        'base_datos',
        'email',
        'activo',
        'plan',
        'codigo_solicitud',
        'fecha_vencimiento',
        'licencia_activada_at',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
            'fecha_vencimiento' => 'datetime',
            'licencia_activada_at' => 'datetime',
        ];
    }

    public function getConnectionName(): ?string
    {
        return config('tenencia.central') ?: parent::getConnectionName();
    }

    public function puedeOperar(): bool
    {
        if (! $this->activo) {
            return false;
        }

        return ! $this->fecha_vencimiento || ! $this->fecha_vencimiento->isPast();
    }

    public function usuarios(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function clientes(): HasMany
    {
        return $this->hasMany(Cliente::class);
    }
}
