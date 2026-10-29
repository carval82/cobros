<?php

namespace App\Services;

use App\Models\Cuota;
use App\Models\Movimiento;
use App\Models\Pago;
use App\Models\Prestamo;
use App\Support\Dinero;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PrestamoService
{
    public function crear(int $clienteId, int $capital, float $porcentaje, int $numeroCuotas, string $frecuencia, string $fechaPrimerPago, ?string $nota = null): Prestamo
    {
        if ($clienteId < 1) {
            throw ValidationException::withMessages([
                'cliente_id' => 'Elige el cliente. Un préstamo no se abre sin él.',
            ]);
        }

        $cliente = \App\Models\Cliente::query()->find($clienteId);

        if (! $cliente) {
            throw ValidationException::withMessages([
                'cliente_id' => 'Ese cliente no existe.',
            ]);
        }

        $this->exigirCupoDemo($cliente);
        $plan = PlanPagos::simular($capital, $porcentaje, $numeroCuotas, $frecuencia, $fechaPrimerPago);

        return DB::transaction(function () use ($cliente, $plan, $nota) {
            $prestamo = Prestamo::create([
                'empresa_id' => $cliente->empresa_id,
                'cliente_id' => $cliente->id,
                'capital' => $plan['capital'],
                'porcentaje' => $plan['porcentaje'],
                'interes' => $plan['interes'],
                'total' => $plan['total'],
                'numero_cuotas' => $plan['cuotas'],
                'frecuencia' => $plan['frecuencia'],
                'fecha_primer_pago' => $plan['filas'][0]['fecha'],
                'nota' => $nota,
                'estado' => 'activo',
            ]);

            foreach ($plan['filas'] as $fila) {
                $prestamo->cuotas()->create([
                    'numero' => $fila['numero'],
                    'fecha_vencimiento' => $fila['fecha'],
                    'capital' => $fila['capital'],
                    'interes' => $fila['interes'],
                    'valor' => $fila['valor'],
                    'pagado' => 0,
                    'saldo' => $fila['valor'],
                    'estado' => 'pendiente',
                ]);
            }

            $this->movimiento(
                $cliente->id,
                $prestamo->id,
                'prestamo',
                'Préstamo de '.Dinero::formato($plan['capital']).' al '.Dinero::porcentaje($plan['porcentaje'])
                    .' en '.$plan['cuotas'].' cuotas '.strtolower(PlanPagos::FRECUENCIAS[$plan['frecuencia']])
                    .'. Total a cobrar '.Dinero::formato($plan['total']).'.',
                $plan['total'],
                $plan['filas'][0]['fecha']
            );

            return $prestamo;
        });
    }

    public function registrarPago(Prestamo $prestamo, int $monto, string $fecha, ?string $nota = null, ?float $latitud = null, ?float $longitud = null): Pago
    {
        if (! $prestamo->cliente_id) {
            throw ValidationException::withMessages([
                'monto' => 'Este préstamo no tiene cliente.',
            ]);
        }

        if (! in_array($prestamo->estado, ['activo', 'detenido'], true)) {
            throw ValidationException::withMessages([
                'monto' => 'Este préstamo no admite pagos.',
            ]);
        }

        return DB::transaction(function () use ($prestamo, $monto, $fecha, $nota, $latitud, $longitud) {
            $cuotas = $prestamo->cuotas()->orderBy('numero')->lockForUpdate()->get();
            $saldo = (int) $cuotas->sum('saldo');

            if ($monto > $saldo) {
                throw ValidationException::withMessages([
                    'monto' => 'El abono supera el saldo de '.Dinero::formato($saldo).'.',
                ]);
            }

            $pago = Pago::create([
                'empresa_id' => $prestamo->empresa_id,
                'prestamo_id' => $prestamo->id,
                'cliente_id' => $prestamo->cliente_id,
                'monto' => $monto,
                'fecha' => $fecha,
                'nota' => $nota,
                'latitud' => $latitud,
                'longitud' => $longitud,
            ]);

            if ($latitud !== null && $longitud !== null) {
                $cliente = $prestamo->cliente;
                if ($cliente->latitud === null || $cliente->longitud === null) {
                    $cliente->update([
                        'latitud' => $latitud,
                        'longitud' => $longitud,
                    ]);
                }
            }

            $restante = $monto;
            $cubiertas = [];

            foreach ($cuotas as $cuota) {
                if ($restante <= 0 || $cuota->saldo <= 0) {
                    continue;
                }

                $aplica = min($cuota->saldo, $restante);
                $cuota->pagado += $aplica;
                $cuota->saldo -= $aplica;
                $cuota->estado = $cuota->saldo === 0 ? 'pagada' : 'parcial';
                $cuota->save();

                $pago->aplicaciones()->create([
                    'cuota_id' => $cuota->id,
                    'monto' => $aplica,
                ]);

                $cubiertas[] = 'cuota '.$cuota->numero;
                $restante -= $aplica;
            }

            $this->movimiento(
                $prestamo->cliente_id,
                $prestamo->id,
                'pago',
                'Abono de '.Dinero::formato($monto).' aplicado a '.implode(', ', $cubiertas).'.',
                $monto,
                $fecha
            );

            $nuevoSaldo = (int) $prestamo->cuotas()->sum('saldo');
            if ($nuevoSaldo === 0) {
                $prestamo->update([
                    'estado' => 'liquidado',
                    'detenido_en' => null,
                    'motivo_detencion' => null,
                ]);

                $this->movimiento(
                    $prestamo->cliente_id,
                    $prestamo->id,
                    'liquidar',
                    'Préstamo liquidado. No queda saldo.',
                    null,
                    $fecha
                );
            }

            return $pago;
        });
    }

    public function refinanciar(Prestamo $prestamo, int $adicional, float $porcentaje, int $numeroCuotas, string $frecuencia, string $fechaPrimerPago, ?string $nota = null): Prestamo
    {
        if ($prestamo->estado !== 'activo') {
            throw ValidationException::withMessages([
                'adicional' => 'Solo se refinancia un préstamo activo.',
            ]);
        }

        if ($adicional < 0) {
            throw ValidationException::withMessages([
                'adicional' => 'El dinero adicional no puede ser negativo.',
            ]);
        }

        return DB::transaction(function () use ($prestamo, $adicional, $porcentaje, $numeroCuotas, $frecuencia, $fechaPrimerPago, $nota) {
            $cuotas = $prestamo->cuotas()->orderBy('numero')->lockForUpdate()->get();
            $saldo = (int) $cuotas->sum('saldo');

            if ($saldo < 1) {
                throw ValidationException::withMessages([
                    'adicional' => 'Este préstamo ya no tiene saldo. El siguiente dinero se entrega en un préstamo nuevo.',
                ]);
            }

            $plan = PlanPagos::simular($saldo + $adicional, $porcentaje, $numeroCuotas, $frecuencia, $fechaPrimerPago);
            $ultimoNumero = (int) $cuotas->max('numero');

            foreach ($cuotas as $cuota) {
                if ($cuota->saldo <= 0) {
                    continue;
                }

                if ($cuota->pagado > 0) {
                    $pagado = (int) $cuota->pagado;
                    $capital = min((int) $cuota->capital, $pagado);
                    $cuota->update([
                        'capital' => $capital,
                        'interes' => $pagado - $capital,
                        'valor' => $pagado,
                        'saldo' => 0,
                        'estado' => 'pagada',
                    ]);

                    continue;
                }

                $cuota->delete();
            }

            foreach ($plan['filas'] as $indice => $fila) {
                $prestamo->cuotas()->create([
                    'numero' => $ultimoNumero + $indice + 1,
                    'fecha_vencimiento' => $fila['fecha'],
                    'capital' => $fila['capital'],
                    'interes' => $fila['interes'],
                    'valor' => $fila['valor'],
                    'pagado' => 0,
                    'saldo' => $fila['valor'],
                    'estado' => 'pendiente',
                ]);
            }

            $prestamo->update([
                'capital' => (int) $prestamo->capital + $adicional,
                'porcentaje' => $porcentaje,
                'interes' => (int) $prestamo->cuotas()->sum('interes'),
                'total' => (int) $prestamo->cuotas()->sum('valor'),
                'numero_cuotas' => (int) $prestamo->cuotas()->count(),
                'frecuencia' => $frecuencia,
                'nota' => $nota ?: $prestamo->nota,
            ]);

            $texto = $adicional > 0
                ? 'Mismo préstamo ampliado en '.Dinero::formato($adicional).'. El saldo de '.Dinero::formato($saldo)
                    .' más ese dinero queda en '.Dinero::formato($plan['total']).' a '.Dinero::porcentaje($porcentaje)
                    .' en '.$plan['cuotas'].' cuotas '.strtolower(PlanPagos::FRECUENCIAS[$frecuencia]).'.'
                : 'Mismo préstamo refinanciado. El saldo de '.Dinero::formato($saldo).' quedó en '
                    .Dinero::formato($plan['total']).' a '.Dinero::porcentaje($porcentaje)
                    .' en '.$plan['cuotas'].' cuotas '.strtolower(PlanPagos::FRECUENCIAS[$frecuencia]).'.';

            $this->movimiento(
                $prestamo->cliente_id,
                $prestamo->id,
                'refinanciar',
                $texto,
                $adicional > 0 ? $adicional : $plan['total'],
                $fechaPrimerPago
            );

            return $prestamo->fresh();
        });
    }

    public function detener(Prestamo $prestamo, string $motivo): void
    {
        if ($prestamo->estado !== 'activo') {
            throw ValidationException::withMessages([
                'motivo' => 'Solo se puede detener un préstamo activo.',
            ]);
        }

        DB::transaction(function () use ($prestamo, $motivo) {
            $prestamo->update([
                'estado' => 'detenido',
                'detenido_en' => now(),
                'motivo_detencion' => $motivo,
            ]);

            $this->movimiento(
                $prestamo->cliente_id,
                $prestamo->id,
                'detener',
                'Cobro detenido: '.$motivo.'. Mientras siga detenido no se cuenta como atraso.',
                null,
                today()->toDateString()
            );
        });
    }

    public function reanudar(Prestamo $prestamo): int
    {
        if ($prestamo->estado !== 'detenido' || $prestamo->detenido_en === null) {
            throw ValidationException::withMessages([
                'motivo' => 'Este préstamo no está detenido.',
            ]);
        }

        return DB::transaction(function () use ($prestamo) {
            $dias = (int) round($prestamo->detenido_en->copy()->startOfDay()->diffInDays(now()->startOfDay(), true));

            if ($dias > 0) {
                $prestamo->cuotas()
                    ->where('saldo', '>', 0)
                    ->orderBy('numero')
                    ->get()
                    ->each(function (Cuota $cuota) use ($dias) {
                        $cuota->update([
                            'fecha_vencimiento' => $cuota->fecha_vencimiento->copy()->addDays($dias),
                        ]);
                    });
            }

            $prestamo->update([
                'estado' => 'activo',
                'detenido_en' => null,
                'motivo_detencion' => null,
            ]);

            $this->movimiento(
                $prestamo->cliente_id,
                $prestamo->id,
                'reanudar',
                $dias > 0
                    ? 'Cobro reanudado. Las cuotas pendientes se corrieron '.$dias.' día'.($dias === 1 ? '' : 's').'.'
                    : 'Cobro reanudado el mismo día. Las fechas de pago no cambiaron.',
                null,
                today()->toDateString()
            );

            $this->sincronizarCartera();

            return $dias;
        });
    }

    public function aplazar(Cuota $cuota, string $nuevaFecha, ?string $nota = null): void
    {
        $prestamo = $cuota->prestamo;

        if (! in_array($prestamo->estado, ['activo', 'detenido'], true)) {
            throw ValidationException::withMessages([
                'fecha' => 'Este préstamo no se puede aplazar.',
            ]);
        }

        if ($cuota->saldo <= 0) {
            throw ValidationException::withMessages([
                'fecha' => 'Esa cuota ya está pagada.',
            ]);
        }

        $destino = \Carbon\Carbon::parse($nuevaFecha)->startOfDay();
        $origen = $cuota->fecha_vencimiento->copy()->startOfDay();

        if ($destino->lte($origen)) {
            throw ValidationException::withMessages([
                'fecha' => 'La nueva fecha tiene que ser posterior a '.$origen->translatedFormat('d M Y').'.',
            ]);
        }

        $dias = (int) round($origen->diffInDays($destino, true));

        DB::transaction(function () use ($prestamo, $cuota, $destino, $dias, $nota) {
            $prestamo->cuotas()
                ->where('saldo', '>', 0)
                ->where('numero', '>=', $cuota->numero)
                ->orderBy('numero')
                ->get()
                ->each(function (Cuota $pendiente) use ($dias) {
                    $pendiente->update([
                        'fecha_vencimiento' => $pendiente->fecha_vencimiento->copy()->addDays($dias),
                    ]);
                });

            $texto = 'Cuota '.$cuota->numero.' aplazada al '.$destino->translatedFormat('d M Y')
                .'. Las cuotas siguientes se corrieron '.$dias.' día'.($dias === 1 ? '' : 's').'.';

            if ($nota) {
                $texto .= ' '.$nota;
            }

            $this->movimiento(
                $prestamo->cliente_id,
                $prestamo->id,
                'aplazar',
                $texto,
                null,
                today()->toDateString()
            );

            $this->sincronizarCartera();
        });
    }

    public function anular(Prestamo $prestamo, string $motivo): void
    {
        if ($prestamo->pagos()->exists()) {
            throw ValidationException::withMessages([
                'motivo' => 'No se puede anular un préstamo que ya tiene abonos.',
            ]);
        }

        if (! in_array($prestamo->estado, ['activo', 'detenido'], true)) {
            throw ValidationException::withMessages([
                'motivo' => 'Este préstamo ya no está vigente.',
            ]);
        }

        DB::transaction(function () use ($prestamo, $motivo) {
            $prestamo->update([
                'estado' => 'anulado',
                'detenido_en' => null,
                'motivo_detencion' => null,
            ]);

            $this->movimiento(
                $prestamo->cliente_id,
                $prestamo->id,
                'anular',
                'Préstamo anulado: '.$motivo,
                null,
                today()->toDateString()
            );
        });
    }

    public function sincronizarCartera(): void
    {
        Cuota::query()
            ->where('saldo', '>', 0)
            ->where('pagado', 0)
            ->whereDate('fecha_vencimiento', '<', today())
            ->whereHas('prestamo', fn ($q) => $q->where('estado', 'activo'))
            ->update(['estado' => 'vencida']);

        Cuota::query()
            ->where('saldo', '>', 0)
            ->where('pagado', '>', 0)
            ->whereDate('fecha_vencimiento', '<', today())
            ->whereHas('prestamo', fn ($q) => $q->where('estado', 'activo'))
            ->update(['estado' => 'vencida']);

        Cuota::query()
            ->where('estado', 'vencida')
            ->where('pagado', 0)
            ->where(function ($q) {
                $q->whereDate('fecha_vencimiento', '>=', today())
                    ->orWhereHas('prestamo', fn ($p) => $p->where('estado', '!=', 'activo'));
            })
            ->update(['estado' => 'pendiente']);

        Cuota::query()
            ->where('estado', 'vencida')
            ->where('pagado', '>', 0)
            ->where(function ($q) {
                $q->whereDate('fecha_vencimiento', '>=', today())
                    ->orWhereHas('prestamo', fn ($p) => $p->where('estado', '!=', 'activo'));
            })
            ->update(['estado' => 'parcial']);
    }

    private function exigirCupoDemo(\App\Models\Cliente $cliente): void
    {
        $empresa = $cliente->empresa_id
            ? \App\Models\Empresa::query()->find($cliente->empresa_id)
            : (app()->bound('empresa.tenant') ? app('empresa.tenant') : null);

        if (! $empresa instanceof \App\Models\Empresa || $empresa->esCompleta()) {
            return;
        }

        $usados = \App\Support\Tenencia::activa()
            ? Prestamo::query()->count()
            : Prestamo::withoutGlobalScopes()->where('empresa_id', $empresa->id)->count();

        if ($usados >= \App\Models\Empresa::LIMITE_DEMO) {
            throw ValidationException::withMessages([
                'cliente_id' => 'La demo permite '.\App\Models\Empresa::LIMITE_DEMO.' préstamos. En Acerca de pegas la clave y pasa a versión completa.',
            ]);
        }
    }

    private function movimiento(int $clienteId, ?int $prestamoId, string $tipo, string $descripcion, ?int $monto, string $fecha): void
    {
        Movimiento::create([
            'empresa_id' => \App\Models\Cliente::query()->whereKey($clienteId)->value('empresa_id'),
            'cliente_id' => $clienteId,
            'prestamo_id' => $prestamoId,
            'tipo' => $tipo,
            'descripcion' => $descripcion,
            'monto' => $monto,
            'fecha' => $fecha,
        ]);
    }
}
