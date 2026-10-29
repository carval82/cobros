<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Empresa extends Model
{
    protected $fillable = [
        'nombre',
        'uuid',
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

    protected static function booted(): void
    {
        static::creating(function (Empresa $empresa) {
            if (! $empresa->uuid) {
                $empresa->uuid = (string) Str::uuid();
            }
        });
    }

    public function direccion(): ?string
    {
        if (! $this->base_datos || ! $this->uuid) {
            return null;
        }

        return route('tenant.login', ['tenant' => $this->uuid]);
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
