<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Cuota;
use App\Models\Prestamo;
use App\Services\PlanPagos;
use App\Services\PrestamoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PrestamoController extends Controller
{
    public function __construct(private PrestamoService $prestamos) {}

    public function create(Request $request): View|RedirectResponse
    {
        if (Cliente::query()->doesntExist()) {
            return redirect()
                ->to(cobro_route('clientes.create'))
                ->with('ok', 'Primero crea el cliente. Un préstamo no se abre sin él.');
        }

        $clienteId = $request->integer('cliente');

        $empresa = \App\Support\EmpresaActual::empresa();

        return view('prestamos.create', [
            'clientes' => Cliente::query()->orderBy('nombre')->get(),
            'clienteId' => $clienteId,
            'frecuencias' => PlanPagos::FRECUENCIAS,
            'demo' => $empresa && ! $empresa->esCompleta(),
            'usados' => $empresa && ! $empresa->esCompleta() ? Prestamo::query()->count() : 0,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'cliente_id' => ['required', 'exists:clientes,id'],
            'capital' => ['required', 'integer', 'min:1'],
            'porcentaje' => ['required', 'numeric', 'min:0', 'max:1000'],
            'cuotas' => ['required', 'integer', 'min:1', 'max:366'],
            'frecuencia' => ['required', 'in:diario,semanal,quincenal,mensual'],
            'fecha_primer_pago' => ['required', 'date'],
            'nota' => ['nullable', 'string', 'max:500'],
        ], [
            'cliente_id.required' => 'Elige el cliente. Un préstamo no se abre sin él.',
            'cliente_id.exists' => 'Ese cliente no existe.',
            'capital.required' => 'Escribe el valor prestado.',
            'capital.integer' => 'El capital va en pesos, sin centavos.',
            'porcentaje.required' => 'Escribe el porcentaje de interés.',
            'cuotas.required' => 'Indica cuántas cuotas son.',
            'fecha_primer_pago.required' => 'Indica la fecha del primer pago.',
        ]);

        $prestamo = $this->prestamos->crear(
            (int) $datos['cliente_id'],
            (int) $datos['capital'],
            (float) $datos['porcentaje'],
            (int) $datos['cuotas'],
            $datos['frecuencia'],
            $datos['fecha_primer_pago'],
            $datos['nota'] ?? null
        );

        return redirect()
            ->to(cobro_route('prestamos.show', $prestamo))
            ->with('ok', 'Préstamo creado. El plan de pagos ya tiene fechas y cuotas.');
    }

    public function show(Prestamo $prestamo): View
    {
        $this->prestamos->sincronizarCartera();

        $prestamo->load([
            'cliente',
            'cuotas' => fn ($q) => $q->orderBy('numero'),
            'pagos' => fn ($q) => $q->latest('fecha')->latest('id'),
        ]);
        $prestamo->cuotas->each->setRelation('prestamo', $prestamo);

        return view('prestamos.show', ['prestamo' => $prestamo]);
    }

    public function pagar(Request $request, Prestamo $prestamo): RedirectResponse
    {
        $datos = $request->validate([
            'monto' => ['required', 'integer', 'min:1'],
            'fecha' => ['required', 'date'],
            'nota' => ['nullable', 'string', 'max:180'],
            'latitud' => ['nullable', 'numeric', 'between:-90,90'],
            'longitud' => ['nullable', 'numeric', 'between:-180,180'],
        ], [
            'monto.required' => 'Escribe el valor del abono.',
            'monto.integer' => 'El abono va en pesos, sin centavos.',
        ]);

        $latitud = isset($datos['latitud']) ? (float) $datos['latitud'] : null;
        $longitud = isset($datos['longitud']) ? (float) $datos['longitud'] : null;
        if ($latitud === null || $longitud === null) {
            $latitud = null;
            $longitud = null;
        }

        $this->prestamos->registrarPago(
            $prestamo,
            (int) $datos['monto'],
            $datos['fecha'],
            $datos['nota'] ?? null,
            $latitud,
            $longitud
        );

        return back()->with('ok', 'Abono registrado.');
    }

    public function detener(Request $request, Prestamo $prestamo): RedirectResponse
    {
        $datos = $request->validate([
            'motivo' => ['required', 'string', 'max:180'],
        ], [
            'motivo.required' => 'Escribe por qué se detiene el cobro.',
        ]);

        $this->prestamos->detener($prestamo, $datos['motivo']);

        return back()->with('ok', 'Cobro detenido. No suma mora mientras siga así.');
    }

    public function reanudar(Prestamo $prestamo): RedirectResponse
    {
        $dias = $this->prestamos->reanudar($prestamo);
        $texto = $dias > 0
            ? "Cobro reanudado. Las cuotas pendientes se corrieron {$dias} días."
            : 'Cobro reanudado.';

        return back()->with('ok', $texto);
    }

    public function aplazar(Request $request, Cuota $cuota): RedirectResponse
    {
        $datos = $request->validate([
            'fecha' => ['required', 'date'],
            'nota' => ['nullable', 'string', 'max:180'],
        ], [
            'fecha.required' => 'Elige la nueva fecha de esa cuota.',
        ]);

        $this->prestamos->aplazar($cuota, $datos['fecha'], $datos['nota'] ?? null);

        return back()->with('ok', 'Cuota aplazada. Las siguientes también se corrieron.');
    }

    public function refinanciar(Request $request, Prestamo $prestamo): RedirectResponse
    {
        $datos = $request->validate([
            'adicional' => ['required', 'integer', 'min:0'],
            'porcentaje' => ['required', 'numeric', 'min:0', 'max:1000'],
            'cuotas' => ['required', 'integer', 'min:1', 'max:366'],
            'frecuencia' => ['required', 'in:diario,semanal,quincenal,mensual'],
            'fecha_primer_pago' => ['required', 'date'],
            'nota' => ['nullable', 'string', 'max:500'],
        ], [
            'adicional.required' => 'Escribe el dinero adicional. Cero si solo rearmas el saldo.',
            'adicional.integer' => 'El adicional va en pesos, sin centavos.',
            'porcentaje.required' => 'Escribe el interés del nuevo plan.',
            'cuotas.required' => 'Indica cuántas cuotas nuevas son.',
            'fecha_primer_pago.required' => 'Indica la fecha del primer pago del nuevo plan.',
        ]);

        $this->prestamos->refinanciar(
            $prestamo,
            (int) $datos['adicional'],
            (float) $datos['porcentaje'],
            (int) $datos['cuotas'],
            $datos['frecuencia'],
            $datos['fecha_primer_pago'],
            $datos['nota'] ?? null
        );

        return back()->with('ok', 'El mismo préstamo quedó refinanciado. El cliente no cambió.');
    }

    public function anular(Request $request, Prestamo $prestamo): RedirectResponse
    {
        $datos = $request->validate([
            'motivo' => ['required', 'string', 'max:180'],
        ], [
            'motivo.required' => 'Escribe por qué se anula.',
        ]);

        $this->prestamos->anular($prestamo, $datos['motivo']);

        return back()->with('ok', 'Préstamo anulado. Ya no entra en la cartera.');
    }
}
