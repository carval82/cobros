<?php

namespace App\Models;

use App\Models\Concerns\FiltraEmpresa;
use App\Models\Concerns\UsaBaseTenant;
use App\Support\EmpresaActual;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class CajaCobro extends Model
{
    use FiltraEmpresa, UsaBaseTenant;

    protected $table = 'caja_cobros';

    protected $fillable = [
        'empresa_id',
        'user_id',
        'nombre',
        'tipo',
        'capital',
        'abierto',
    ];

    protected function casts(): array
    {
        return [
            'capital' => 'integer',
            'abierto' => 'boolean',
        ];
    }

    public function gastos(): HasMany
    {
        return $this->hasMany(Gasto::class, 'caja_cobro_id');
    }

    public function prestamos(): HasMany
    {
        return $this->hasMany(Prestamo::class, 'caja_cobro_id');
    }

    public function jornadas(): HasMany
    {
        return $this->hasMany(Jornada::class, 'caja_cobro_id');
    }

    public function integrantes(): HasMany
    {
        return $this->hasMany(CajaCobroUsuario::class, 'caja_cobro_id');
    }

    public function participa(User $usuario): bool
    {
        return (int) $this->user_id === (int) $usuario->id
            || $this->integrantes()->where('user_id', $usuario->id)->exists();
    }

    /**
     * @return list<array{id: int, nombre: string}>
     */
    public function fichaCobradores(): array
    {
        $ids = $this->integrantes()->pluck('user_id');
        if ($ids->isEmpty()) {
            return [];
        }

        return User::query()
            ->whereIn('id', $ids)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (User $usuario) => ['id' => $usuario->id, 'nombre' => $usuario->name])
            ->values()
            ->all();
    }

    public function sincronizarCobradores(array $pedidos): void
    {
        $validos = static::cobradoresDeLaEmpresa()->pluck('id')->map(fn ($id) => (int) $id);
        $ids = collect($pedidos)
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $validos->contains($id) && $id !== (int) $this->user_id)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            $this->integrantes()->delete();

            return;
        }

        $this->integrantes()->whereNotIn('user_id', $ids)->delete();
        foreach ($ids as $id) {
            $this->integrantes()->firstOrCreate(['user_id' => $id]);
        }
    }

    public static function cobradoresDeLaEmpresa(): Collection
    {
        $empresaId = EmpresaActual::id();

        return User::query()
            ->where('rol', 'cobrador')
            ->where('es_maestro', false)
            ->when($empresaId, fn ($q) => $q->where('empresa_id', $empresaId))
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function diaAbierto(?string $fecha = null): bool
    {
        $fecha = $fecha ?? today()->toDateString();
        $jornada = $this->jornadas()->whereDate('fecha', $fecha)->first();

        return $jornada !== null && ! $jornada->cerrada;
    }

    public static function veTodos(User $usuario): bool
    {
        return $usuario->es_maestro
            || $usuario->puede('usuarios.gestionar')
            || $usuario->rol === 'consulta';
    }

    public static function puedeAbrirOtro(User $usuario): bool
    {
        if (! $usuario->puede('cobros.registrar')) {
            return false;
        }

        if ($usuario->es_maestro || $usuario->puede('usuarios.gestionar')) {
            return true;
        }

        return ! static::query()->where('user_id', $usuario->id)->where('abierto', true)->exists();
    }

    public static function visiblesPara(User $usuario)
    {
        return static::query()
            ->when(! static::veTodos($usuario), function ($q) use ($usuario) {
                $q->where(function ($q) use ($usuario) {
                    $q->where('user_id', $usuario->id)
                        ->orWhereHas('integrantes', fn ($i) => $i->where('user_id', $usuario->id));
                });
            })
            ->orderByRaw("case when tipo = 'administrador' then 0 else 1 end")
            ->orderBy('nombre')
            ->get();
    }

    public static function conDiaAbierto(User $usuario)
    {
        return static::visiblesPara($usuario)
            ->filter(fn (self $caja) => $caja->diaAbierto())
            ->values();
    }
}
