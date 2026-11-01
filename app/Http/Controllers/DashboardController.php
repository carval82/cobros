<?php

namespace App\Http\Controllers;

use App\Models\CajaCobro;
use App\Models\Cuota;
use App\Models\Movimiento;
use App\Models\Pago;
use App\Models\Prestamo;
use App\Models\User;
use App\Services\CuadreCobro;
use App\Services\PrestamoService;
use App\Support\CobroActivo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private PrestamoService $prestamos) {}

    public function index(): View
    {
        $this->prestamos->sincronizarCartera();

        $usuario = auth()->user();
        $cobros = $this->cobros();
        $caja = CobroActivo::caja();
        $cajaId = $caja?->id;
        $informe = $cajaId ? $cobros->firstWhere('id', $cajaId) : null;
        $escritorio = $this->escritorio($cajaId);
        $activo = $informe ?? ($cajaId ? $cobros->firstWhere('id', $cajaId) : null);

        return view('dashboard', [
            'hoy' => $escritorio['hoy'],
            'atrasadas' => $escritorio['atrasadas'],
            'cartera' => $escritorio['cartera'],
            'cobrarHoy' => $escritorio['cobrarHoy'],
            'mora' => $escritorio['mora'],
            'cobradoHoy' => $escritorio['cobradoHoy'],
            'enCaja' => $cajaId ? (int) ($cobros->firstWhere('id', $cajaId)['en_caja'] ?? 0) : 0,
            'cobros' => $cobros,
            'informe' => $informe,
            'mostrar' => $cajaId !== null,
            'cajaActiva' => $cajaId,
            'editaEquipo' => $activo && ! empty($activo['edita']),
            'cobradoresEmpresa' => CajaCobro::cobradoresDeLaEmpresa(),
            'equipoIds' => collect($activo['cobradores'] ?? [])->pluck('id')->map(fn ($id) => (int) $id)->all(),
            'fecha' => now()->toDateString(),
            'puedeAperturar' => $usuario instanceof User && CajaCobro::puedeAbrirOtro($usuario),
            'detenidos' => $escritorio['detenidos'],
            'movimientos' => $escritorio['movimientos'],
        ]);
    }

    public function entrar(CajaCobro $caja): RedirectResponse
    {
        $usuario = auth()->user();
        abort_unless($usuario instanceof User && (CajaCobro::veTodos($usuario) || $caja->participa($usuario)), 403);
        CobroActivo::entrar($caja);

        return redirect()->to(cobro_route('dashboard'));
    }

    public function salir(): RedirectResponse
    {
        CobroActivo::salir();

        return redirect()->to(cobro_route('dashboard'));
    }

    public function datos(): JsonResponse
    {
        $this->prestamos->sincronizarCartera();
        $ids = $this->cajaIdsVisibles();

        $hoy = $this->cuotasAbiertas(null, $ids)
            ->whereDate('fecha_vencimiento', today())
            ->orderBy('id')
            ->get();

        $atrasadas = $this->cuotasAbiertas(null, $ids)
            ->whereDate('fecha_vencimiento', '<', today())
            ->orderBy('fecha_vencimiento')
            ->get();

        $cartera = (int) Cuota::query()
            ->where('saldo', '>', 0)
            ->whereHas('prestamo', function ($q) use ($ids) {
                $q->whereIn('estado', ['activo', 'detenido']);
                if (is_array($ids)) {
                    $q->whereIn('caja_cobro_id', $ids);
                }
            })
            ->sum('saldo');

        return response()->json([
            'token' => csrf_token(),
            'cobrar_hoy' => (int) $hoy->sum('saldo'),
            'mora' => (int) $atrasadas->sum('saldo'),
            'cobrado_hoy' => (int) Pago::query()
                ->when(is_array($ids), fn ($q) => $q->whereIn('caja_cobro_id', $ids))
                ->whereDate('fecha', today())
                ->sum('monto'),
            'cartera' => $cartera,
            'detenidos' => Prestamo::query()
                ->when(is_array($ids), fn ($q) => $q->whereIn('caja_cobro_id', $ids))
                ->where('estado', 'detenido')
                ->count(),
            'hoy' => $hoy->map(fn (Cuota $cuota) => $this->cuotaFila($cuota))->values(),
            'atrasadas' => $atrasadas->map(fn (Cuota $cuota) => $this->cuotaFila($cuota))->values(),
        ]);
    }

    private function cobros()
    {
        $usuario = auth()->user();
        if (! $usuario instanceof User) {
            return collect();
        }

        $fecha = now()->toDateString();

        return CajaCobro::visiblesPara($usuario)->map(function (CajaCobro $caja) use ($usuario, $fecha) {
            $coordina = $usuario->es_maestro || $usuario->puede('usuarios.gestionar');

            return CuadreCobro::armar($caja, $fecha) + [
                'edita' => $usuario->puede('cobros.registrar')
                    && ($coordina || (int) $caja->user_id === (int) $usuario->id),
            ];
        });
    }

    public function atrasados(): View
    {
        $this->prestamos->sincronizarCartera();

        $activo = CobroActivo::id();
        $atrasadas = $this->cuotasAbiertas($activo, $activo ? null : $this->cajaIdsVisibles())
            ->whereDate('fecha_vencimiento', '<', today())
            ->orderBy('fecha_vencimiento')
            ->get();

        return view('atrasados', [
            'atrasadas' => $atrasadas,
            'mora' => (int) $atrasadas->sum('saldo'),
        ]);
    }

    private function escritorio(?int $cajaId): array
    {
        if (! $cajaId) {
            return [
                'hoy' => collect(),
                'atrasadas' => collect(),
                'cartera' => 0,
                'cobrarHoy' => 0,
                'mora' => 0,
                'cobradoHoy' => 0,
                'detenidos' => 0,
                'movimientos' => collect(),
            ];
        }

        $hoy = $this->cuotasAbiertas($cajaId)
            ->whereDate('fecha_vencimiento', today())
            ->orderBy('id')
            ->get();

        $atrasadas = $this->cuotasAbiertas($cajaId)
            ->whereDate('fecha_vencimiento', '<', today())
            ->orderBy('fecha_vencimiento')
            ->get();

        $cartera = (int) Cuota::query()
            ->where('saldo', '>', 0)
            ->whereHas('prestamo', fn ($q) => $q->where('caja_cobro_id', $cajaId)->whereIn('estado', ['activo', 'detenido']))
            ->sum('saldo');

        return [
            'hoy' => $hoy,
            'atrasadas' => $atrasadas,
            'cartera' => $cartera,
            'cobrarHoy' => (int) $hoy->sum('saldo'),
            'mora' => (int) $atrasadas->sum('saldo'),
            'cobradoHoy' => (int) Pago::query()->where('caja_cobro_id', $cajaId)->whereDate('fecha', today())->sum('monto'),
            'detenidos' => Prestamo::query()->where('caja_cobro_id', $cajaId)->where('estado', 'detenido')->count(),
            'movimientos' => Movimiento::query()
                ->with('cliente')
                ->whereHas('prestamo', fn ($q) => $q->where('caja_cobro_id', $cajaId))
                ->latest('id')
                ->limit(8)
                ->get(),
        ];
    }

    private function cuotaFila(Cuota $cuota): array
    {
        $cliente = $cuota->prestamo->cliente;

        return [
            'id' => $cuota->id,
            'prestamo_id' => $cuota->prestamo_id,
            'numero' => $cuota->numero,
            'fecha' => $cuota->fecha_vencimiento->toDateString(),
            'valor' => $cuota->valor,
            'saldo' => $cuota->saldo,
            'estado' => $cuota->estadoVisible(),
            'cliente' => $cliente->nombre,
            'cliente_id' => $cliente->id,
            'direccion' => $cliente->direccion,
            'telefono' => $cliente->telefono,
            'latitud' => $cliente->latitud,
            'longitud' => $cliente->longitud,
        ];
    }

    private function cajaIdsVisibles(): ?array
    {
        $usuario = auth()->user();
        if (! $usuario instanceof User || CajaCobro::veTodos($usuario)) {
            return null;
        }

        return CajaCobro::visiblesPara($usuario)->pluck('id')->all();
    }

    private function cuotasAbiertas(?int $cajaId = null, ?array $cajaIds = null)
    {
        return Cuota::query()
            ->with(['prestamo.cliente'])
            ->where('saldo', '>', 0)
            ->whereHas('prestamo', function ($q) use ($cajaId, $cajaIds) {
                $q->where('estado', 'activo');
                if ($cajaId) {
                    $q->where('caja_cobro_id', $cajaId);
                } elseif (is_array($cajaIds)) {
                    $q->whereIn('caja_cobro_id', $cajaIds);
                }
            });
    }
}
