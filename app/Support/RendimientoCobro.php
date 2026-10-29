<?php

namespace App\Support;

use App\Models\Pago;
use App\Models\User;
use Illuminate\Support\Collection;

class RendimientoCobro
{
    public static function armar(string $desde, string $hasta, ?int $soloUsuarioId = null): array
    {
        $usuarios = self::usuarios($soloUsuarioId);

        $pagos = Pago::query()
            ->whereDate('fecha', '>=', $desde)
            ->whereDate('fecha', '<=', $hasta)
            ->when($soloUsuarioId, fn ($q) => $q->where('user_id', $soloUsuarioId))
            ->with('cliente:id,nombre')
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->get();

        $nombres = $usuarios->keyBy('id');
        $conAbonos = $pagos->pluck('user_id')->filter()->unique();
        $filas = $usuarios
            ->filter(fn (User $usuario) => $usuario->rol === 'cobrador' || $conAbonos->contains($usuario->id))
            ->map(function (User $usuario) use ($pagos) {
                $suyos = $pagos->where('user_id', $usuario->id);

                return [
                    'id' => $usuario->id,
                    'nombre' => $usuario->name,
                    'rol' => $usuario->rol ?: 'administrador',
                    'porcentaje' => (float) $usuario->porcentaje_cobro,
                    'abonos' => $suyos->count(),
                    'cobrado' => (int) $suyos->sum('monto'),
                    'comision' => (int) $suyos->sum('comision'),
                ];
            })
            ->sortByDesc('cobrado')
            ->values();

        return [
            'desde' => $desde,
            'hasta' => $hasta,
            'cobrado' => (int) $pagos->sum('monto'),
            'comision' => (int) $pagos->sum('comision'),
            'abonos' => $pagos->count(),
            'cobradores' => $filas->all(),
            'movimientos' => $pagos->take(80)->map(fn (Pago $pago) => [
                'fecha' => $pago->fecha?->toDateString(),
                'cliente' => $pago->cliente?->nombre ?? 'Cliente',
                'cobrador' => $nombres->get($pago->user_id)?->name ?? 'Sin asignar',
                'monto' => (int) $pago->monto,
                'porcentaje' => (float) $pago->porcentaje,
                'comision' => (int) $pago->comision,
            ])->values()->all(),
        ];
    }

    private static function usuarios(?int $soloUsuarioId): Collection
    {
        $empresaId = EmpresaActual::id();

        return User::query()
            ->when($soloUsuarioId, fn ($q) => $q->whereKey($soloUsuarioId))
            ->when($empresaId, fn ($q) => $q->where('empresa_id', $empresaId))
            ->when($empresaId === null && ! $soloUsuarioId, fn ($q) => $q->whereNull('empresa_id'))
            ->where('es_maestro', false)
            ->orderBy('name')
            ->get();
    }
}
