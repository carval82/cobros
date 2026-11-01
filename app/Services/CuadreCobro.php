<?php

namespace App\Services;

use App\Models\CajaCobro;
use App\Models\Gasto;
use App\Models\Jornada;
use App\Models\Pago;
use App\Models\PagoCuota;
use App\Models\Prestamo;
use App\Support\Dinero;

class CuadreCobro
{
    public static function efectivo(CajaCobro $caja): int
    {
        $primera = $caja->jornadas()->orderBy('fecha')->orderBy('id')->first();
        $apertura = $primera ? (int) $primera->valor_inicial : (int) $caja->capital;
        $refuerzos = (int) $caja->jornadas()->sum('refuerzo');
        $retiros = (int) $caja->jornadas()->sum('retiro');
        $prestado = self::prestado($caja->id);
        $recogido = (int) Pago::query()->where('caja_cobro_id', $caja->id)->sum('monto');
        $gastos = (int) Gasto::query()->where('caja_cobro_id', $caja->id)->sum('monto');

        return $apertura + $refuerzos - $prestado + $recogido - $gastos - $retiros;
    }

    public static function enCaja(CajaCobro $caja): int
    {
        return self::efectivo($caja);
    }

    public static function armar(CajaCobro $caja, string $fecha): array
    {
        $jornadas = $caja->jornadas()->orderBy('fecha')->orderBy('id')->get();
        $jornada = $jornadas->first(fn (Jornada $item) => $item->fecha->toDateString() === $fecha);
        $abiertaAntes = $jornadas->first(fn (Jornada $item) => ! $item->cerrada && $item->fecha->toDateString() < $fecha);
        $vivo = self::delDia($caja->id, $fecha);
        $cerrada = $jornada?->cerrada === true;

        $valorInicial = $jornada ? (int) $jornada->valor_inicial : null;
        $refuerzo = $cerrada ? (int) $jornada->refuerzo : ($jornada ? (int) $jornada->refuerzo : 0);
        $prestadoHoy = $cerrada ? (int) $jornada->prestado : $vivo['prestado'];
        $recaudadoHoy = $cerrada ? (int) $jornada->recaudado : $vivo['recaudado'];
        $gastosHoy = $cerrada ? (int) $jornada->gastos : $vivo['gastos'];
        $interesHoy = $cerrada ? (int) $jornada->interes : $vivo['interes'];
        $ganancia = $cerrada ? (int) $jornada->ganancia : $interesHoy - $gastosHoy;
        $cierre = $cerrada
            ? (int) $jornada->cierre
            : ($valorInicial === null ? null : $valorInicial + $refuerzo - $prestadoHoy + $recaudadoHoy - $gastosHoy);
        $gananciaPositiva = max($ganancia, 0);

        $informe = [
            'id' => $caja->id,
            'nombre' => $caja->nombre,
            'tipo' => $caja->tipo,
            'capital' => (int) $caja->capital,
            'base' => (int) $caja->capital,
            'sin_apertura' => $jornadas->isEmpty(),
            'jornada_abierta' => $jornada !== null && ! $cerrada,
            'jornada_cerrada' => $cerrada,
            'puede_abrir_dia' => $fecha === now()->toDateString()
                && $jornada === null
                && $jornadas->isNotEmpty()
                && $abiertaAntes === null
                && $jornadas->every(fn (Jornada $item) => $item->cerrada),
            'falta_cerrar' => $abiertaAntes?->fecha->toDateString(),
            'valor_inicial' => $valorInicial,
            'refuerzo_hoy' => $refuerzo,
            'prestado' => self::prestado($caja->id),
            'prestado_hoy' => $prestadoHoy,
            'recogido' => (int) Pago::query()->where('caja_cobro_id', $caja->id)->sum('monto'),
            'recogido_hoy' => $recaudadoHoy,
            'gastos' => (int) Gasto::query()->where('caja_cobro_id', $caja->id)->sum('monto'),
            'gastos_hoy' => $gastosHoy,
            'cruce_hoy' => $recaudadoHoy - $prestadoHoy - $gastosHoy,
            'ganancia' => $ganancia,
            'cierre' => $cierre,
            'manana_retirar' => $cierre === null ? null : $cierre - $gananciaPositiva,
            'manana_sumar' => $cierre,
            'decision' => $jornada?->decision,
            'retiro' => $jornada ? (int) $jornada->retiro : 0,
            'valor_siguiente' => $jornada?->valor_siguiente,
            'en_caja' => self::efectivo($caja),
            'prestamos_hoy' => Prestamo::query()
                ->where('caja_cobro_id', $caja->id)
                ->where('estado', '!=', 'anulado')
                ->whereDate('created_at', $fecha)
                ->with('cliente:id,nombre')
                ->latest('id')
                ->get()
                ->map(fn (Prestamo $prestamo) => [
                    'cliente' => $prestamo->cliente?->nombre ?? 'Cliente',
                    'capital' => (int) $prestamo->capital,
                ])->values()->all(),
            'pagos_hoy' => Pago::query()
                ->where('caja_cobro_id', $caja->id)
                ->whereDate('fecha', $fecha)
                ->with('cliente:id,nombre')
                ->latest('id')
                ->get()
                ->map(fn (Pago $pago) => [
                    'cliente' => $pago->cliente?->nombre ?? 'Cliente',
                    'monto' => (int) $pago->monto,
                ])->values()->all(),
            'gastos_hoy_lista' => Gasto::query()
                ->where('caja_cobro_id', $caja->id)
                ->whereDate('fecha', $fecha)
                ->latest('id')
                ->get()
                ->map(fn (Gasto $item) => [
                    'nota' => $item->nota ?: 'Gasto',
                    'monto' => (int) $item->monto,
                ])->values()->all(),
        ];

        $informe['cobradores'] = $caja->fichaCobradores();
        $informe['equipo'] = self::fraseEquipo($informe['cobradores']);
        $informe['linea'] = self::linea($informe);

        return $informe;
    }

    public static function fraseEquipo(array $cobradores): string
    {
        $nombres = array_values(array_filter(array_map(
            fn ($fila) => is_array($fila) ? (string) ($fila['nombre'] ?? '') : '',
            $cobradores
        )));

        if ($nombres === []) {
            return 'Solo';
        }
        if (count($nombres) === 1) {
            return 'Con '.$nombres[0];
        }

        $ultimo = array_pop($nombres);

        return 'Con '.implode(', ', $nombres).' y '.$ultimo;
    }

    public static function linea(array $informe): string
    {
        $tipo = ($informe['tipo'] ?? '') === 'administrador' ? 'administrador' : 'cobrador';
        $estado = match (true) {
            ! empty($informe['sin_apertura']) => 'Sin apertura',
            ! empty($informe['jornada_abierta']) => 'Día abierto',
            ! empty($informe['jornada_cerrada']) => 'Día cerrado',
            ! empty($informe['puede_abrir_dia']) => 'Día por abrir',
            default => 'Día pendiente',
        };

        return $informe['nombre'].' · '.$tipo
            .' · '.($informe['equipo'] ?? 'Solo')
            .' · Base '.Dinero::formato($informe['base'])
            .' · En caja '.Dinero::formato($informe['en_caja'])
            .' · Prestó '.Dinero::formato($informe['prestado_hoy'])
            .' · Recaudó '.Dinero::formato($informe['recogido_hoy'])
            .' · Ganancia '.Dinero::formato($informe['ganancia'])
            .' · '.$estado;
    }

    public static function cerrar(CajaCobro $caja, Jornada $jornada, string $decision): Jornada
    {
        $fecha = $jornada->fecha->toDateString();
        $vivo = self::delDia($caja->id, $fecha);
        $ganancia = $vivo['interes'] - $vivo['gastos'];
        $cierre = (int) $jornada->valor_inicial + (int) $jornada->refuerzo - $vivo['prestado'] + $vivo['recaudado'] - $vivo['gastos'];
        $retiro = $decision === 'retirar' && $ganancia > 0 ? $ganancia : 0;

        if ($decision === 'sumar' && $ganancia > 0) {
            $caja->increment('capital', $ganancia);
        }

        $jornada->update([
            'prestado' => $vivo['prestado'],
            'recaudado' => $vivo['recaudado'],
            'gastos' => $vivo['gastos'],
            'interes' => $vivo['interes'],
            'ganancia' => $ganancia,
            'cierre' => $cierre,
            'decision' => $decision,
            'retiro' => $retiro,
            'valor_siguiente' => $cierre - $retiro,
            'cerrada' => true,
        ]);

        return $jornada->fresh();
    }

    /**
     * @return array{prestado: int, recaudado: int, gastos: int, interes: int}
     */
    public static function delDia(int $cajaId, string $fecha): array
    {
        return [
            'prestado' => (int) Prestamo::query()
                ->where('caja_cobro_id', $cajaId)
                ->where('estado', '!=', 'anulado')
                ->whereDate('created_at', $fecha)
                ->sum('capital'),
            'recaudado' => (int) Pago::query()
                ->where('caja_cobro_id', $cajaId)
                ->whereDate('fecha', $fecha)
                ->sum('monto'),
            'gastos' => (int) Gasto::query()
                ->where('caja_cobro_id', $cajaId)
                ->whereDate('fecha', $fecha)
                ->sum('monto'),
            'interes' => self::interesDelDia($cajaId, $fecha),
        ];
    }

    private static function prestado(int $cajaId): int
    {
        return (int) Prestamo::query()
            ->where('caja_cobro_id', $cajaId)
            ->where('estado', '!=', 'anulado')
            ->sum('capital');
    }

    private static function interesDelDia(int $cajaId, string $fecha): int
    {
        $total = 0;
        $aplicaciones = PagoCuota::query()
            ->whereHas('pago', fn ($q) => $q->where('caja_cobro_id', $cajaId)->whereDate('fecha', $fecha))
            ->with('cuota:id,valor,interes')
            ->get();

        foreach ($aplicaciones as $aplicacion) {
            $valor = (int) ($aplicacion->cuota->valor ?? 0);
            if ($valor < 1) {
                continue;
            }
            $total += (int) round((int) $aplicacion->monto * (int) $aplicacion->cuota->interes / $valor);
        }

        return $total;
    }
}
