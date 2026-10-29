<?php

namespace App\Http\Controllers;

use App\Models\Cuota;
use App\Models\Movimiento;
use App\Models\Pago;
use App\Models\Prestamo;
use App\Services\PrestamoService;
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

    private function cuotasAbiertas()
    {
        return Cuota::query()
            ->with(['prestamo.cliente'])
            ->where('saldo', '>', 0)
            ->whereHas('prestamo', fn ($q) => $q->where('estado', 'activo'));
    }
}
