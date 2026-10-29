<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Prestamo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClienteController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));

        $clientes = Cliente::query()
            ->withCount('prestamos')
            ->withSum(['cuotas as saldo' => function ($query) {
                $query->where('cuotas.saldo', '>', 0)
                    ->whereHas('prestamo', fn ($p) => $p->whereIn('estado', ['activo', 'detenido']));
            }], 'saldo')
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($filtro) use ($q) {
                    $filtro->where('nombre', 'like', "%{$q}%")
                        ->orWhere('documento', 'like', "%{$q}%")
                        ->orWhere('telefono', 'like', "%{$q}%");
                });
            })
            ->orderBy('nombre')
            ->get();

        return view('clientes.index', [
            'clientes' => $clientes,
            'q' => $q,
        ]);
    }

    public function datos(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));

        $clientes = Cliente::query()
            ->withCount('prestamos')
            ->withSum(['cuotas as saldo' => function ($query) {
                $query->where('cuotas.saldo', '>', 0)
                    ->whereHas('prestamo', fn ($p) => $p->whereIn('estado', ['activo', 'detenido']));
            }], 'saldo')
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($filtro) use ($q) {
                    $filtro->where('nombre', 'like', "%{$q}%")
                        ->orWhere('documento', 'like', "%{$q}%")
                        ->orWhere('telefono', 'like', "%{$q}%");
                });
            })
            ->orderBy('nombre')
            ->get();

        return response()->json([
            'token' => csrf_token(),
            'clientes' => $clientes->map(fn (Cliente $cliente) => $this->fila($cliente))->values(),
        ]);
    }

    public function mostrar(Cliente $cliente): JsonResponse
    {
        $cliente->load(['prestamos' => fn ($q) => $q->with('cuotas')->latest('id')]);
        $cliente->loadCount('prestamos');

        return response()->json([
            'token' => csrf_token(),
            'cliente' => $this->fila($cliente, desdePrestamos: true),
            'prestamos' => $cliente->prestamos->map(fn (Prestamo $prestamo) => [
                'id' => $prestamo->id,
                'capital' => $prestamo->capital,
                'total' => $prestamo->total,
                'saldo' => $prestamo->saldo(),
                'estado' => $prestamo->estado,
                'frecuencia' => $prestamo->frecuenciaNombre(),
                'cuotas' => $prestamo->numero_cuotas,
            ])->values(),
        ]);
    }

    public function create(): View
    {
        return view('clientes.form', ['cliente' => new Cliente]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $cliente = Cliente::create($this->validar($request));

        if ($request->expectsJson()) {
            return response()->json($this->fila($cliente));
        }

        return redirect()
            ->to(cobro_route('clientes.show', $cliente))
            ->with('ok', 'Cliente guardado.');
    }

    public function show(Cliente $cliente): View
    {
        $cliente->load([
            'prestamos' => fn ($q) => $q->with('cuotas')->latest('id'),
            'movimientos' => fn ($q) => $q->latest('id'),
        ]);

        return view('clientes.show', ['cliente' => $cliente]);
    }

    public function edit(Cliente $cliente): View
    {
        return view('clientes.form', ['cliente' => $cliente]);
    }

    public function update(Request $request, Cliente $cliente): RedirectResponse|JsonResponse
    {
        $cliente->update($this->validar($request));

        if ($request->expectsJson()) {
            return response()->json($this->fila($cliente));
        }

        return redirect()
            ->to(cobro_route('clientes.show', $cliente))
            ->with('ok', 'Datos del cliente actualizados.');
    }

    public function ubicar(Request $request, Cliente $cliente): JsonResponse
    {
        $datos = $request->validate([
            'latitud' => ['required', 'numeric', 'between:-90,90'],
            'longitud' => ['required', 'numeric', 'between:-180,180'],
        ], [
            'latitud.required' => 'Falta la latitud.',
            'longitud.required' => 'Falta la longitud.',
        ]);

        $cliente->update([
            'latitud' => (float) $datos['latitud'],
            'longitud' => (float) $datos['longitud'],
        ]);

        return response()->json([
            'id' => $cliente->id,
            'latitud' => $cliente->latitud,
            'longitud' => $cliente->longitud,
            'token' => csrf_token(),
        ]);
    }

    private function fila(Cliente $cliente, bool $desdePrestamos = false): array
    {
        $saldo = $desdePrestamos
            ? (int) $cliente->prestamos->sum(fn (Prestamo $prestamo) => in_array($prestamo->estado, ['activo', 'detenido'], true) ? $prestamo->saldo() : 0)
            : (int) ($cliente->getAttribute('saldo') ?? 0);

        return [
            'id' => $cliente->id,
            'nombre' => $cliente->nombre,
            'documento' => $cliente->documento,
            'telefono' => $cliente->telefono,
            'direccion' => $cliente->direccion,
            'notas' => $cliente->notas,
            'latitud' => $cliente->latitud,
            'longitud' => $cliente->longitud,
            'prestamos' => (int) ($cliente->prestamos_count ?? 0),
            'saldo' => $saldo,
        ];
    }

    private function validar(Request $request): array
    {
        return $request->validate([
            'nombre' => ['required', 'string', 'max:150'],
            'documento' => ['nullable', 'string', 'max:40'],
            'telefono' => ['nullable', 'string', 'max:40'],
            'direccion' => ['nullable', 'string', 'max:180'],
            'notas' => ['nullable', 'string', 'max:1000'],
        ], [
            'nombre.required' => 'Escribe el nombre del cliente.',
        ]);
    }
}
