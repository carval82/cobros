<?php

namespace App\Http\Controllers;

use App\Models\Cuota;
use App\Models\Movimiento;
use App\Models\Pago;
use App\Models\Prestamo;
use App\Services\PrestamoService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private PrestamoService $prestamos) {}

    public function index(): View
    {
        $this->prestamos->sincronizarCartera();

        $hoy = $this->cuotasAbiertas()
            ->whereDate('fecha_vencimiento', today())
            ->orderBy('id')
            ->get();

        $atrasadas = $this->cuotasAbiertas()
            ->whereDate('fecha_vencimiento', '<', today())
            ->orderBy('fecha_vencimiento')
            ->get();

        $cartera = (int) Cuota::query()
            ->where('saldo', '>', 0)
            ->whereHas('prestamo', fn ($q) => $q->whereIn('estado', ['activo', 'detenido']))
            ->sum('saldo');

        return view('dashboard', [
            'hoy' => $hoy,
            'atrasadas' => $atrasadas,
            'cartera' => $cartera,
            'cobrarHoy' => (int) $hoy->sum('saldo'),
            'mora' => (int) $atrasadas->sum('saldo'),
            'cobradoHoy' => (int) Pago::query()->whereDate('fecha', today())->sum('monto'),
            'detenidos' => Prestamo::query()->where('estado', 'detenido')->count(),
            'movimientos' => Movimiento::query()->with('cliente')->latest('id')->limit(8)->get(),
        ]);
    }

    public function datos(): JsonResponse
    {
        $this->prestamos->sincronizarCartera();

        $hoy = $this->cuotasAbiertas()
            ->whereDate('fecha_vencimiento', today())
            ->orderBy('id')
            ->get();

        $atrasadas = $this->cuotasAbiertas()
            ->whereDate('fecha_vencimiento', '<', today())
            ->orderBy('fecha_vencimiento')
            ->get();

        $cartera = (int) Cuota::query()
            ->where('saldo', '>', 0)
            ->whereHas('prestamo', fn ($q) => $q->whereIn('estado', ['activo', 'detenido']))
            ->sum('saldo');

        return response()->json([
            'token' => csrf_token(),
            'cobrar_hoy' => (int) $hoy->sum('saldo'),
            'mora' => (int) $atrasadas->sum('saldo'),
            'cobrado_hoy' => (int) Pago::query()->whereDate('fecha', today())->sum('monto'),
            'cartera' => $cartera,
            'detenidos' => Prestamo::query()->where('estado', 'detenido')->count(),
            'hoy' => $hoy->map(fn (Cuota $cuota) => $this->cuotaFila($cuota))->values(),
            'atrasadas' => $atrasadas->map(fn (Cuota $cuota) => $this->cuotaFila($cuota))->values(),
        ]);
    }

    public function atrasados(): View
    {
        $this->prestamos->sincronizarCartera();

        $atrasadas = $this->cuotasAbiertas()
            ->whereDate('fecha_vencimiento', '<', today())
            ->orderBy('fecha_vencimiento')
            ->get();

        return view('atrasados', [
            'atrasadas' => $atrasadas,
            'mora' => (int) $atrasadas->sum('saldo'),
        ]);
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

    private function cuotasAbiertas()
    {
        return Cuota::query()
            ->with(['prestamo.cliente'])
            ->where('saldo', '>', 0)
            ->whereHas('prestamo', fn ($q) => $q->where('estado', 'activo'));
    }
}
