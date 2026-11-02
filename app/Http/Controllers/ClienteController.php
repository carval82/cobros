<?php

namespace App\Http\Controllers;

use App\Models\CajaCobro;
use App\Models\Cliente;
use App\Models\Prestamo;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClienteController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));

        return view('clientes.index', [
            'clientes' => $this->lista($q),
            'q' => $q,
        ]);
    }

    public function datos(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));

        return response()->json([
            'token' => csrf_token(),
            'clientes' => $this->lista($q)->map(fn (Cliente $cliente) => $this->fila($cliente))->values(),
        ]);
    }

    public function mostrar(Cliente $cliente): JsonResponse
    {
        $cajaId = CajaCobro::enCurso()?->id;
        $cliente->load(['prestamos' => fn ($q) => $this->delCobro($q, $cajaId)->with('cuotas')->latest('id')]);
        $cliente->setAttribute('restringido', $cliente->abiertoEnOtro($cajaId));
        $cliente->loadCount(['prestamos' => fn ($q) => $this->delCobro($q, $cajaId)]);

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
        $cajaId = CajaCobro::enCurso()?->id;
        $cliente->load([
            'prestamos' => fn ($q) => $this->delCobro($q, $cajaId)->with('cuotas')->latest('id'),
            'movimientos' => fn ($q) => $q
                ->when($cajaId, fn ($q) => $q->whereHas('prestamo', fn ($p) => $p->where('caja_cobro_id', $cajaId)))
                ->when(! $cajaId, fn ($q) => $q->whereRaw('1 = 0'))
                ->latest('id'),
        ]);
        $usuario = auth()->user();

        return view('clientes.show', [
            'cliente' => $cliente,
            'restringido' => $cliente->abiertoEnOtro($cajaId),
            'puedeAprobar' => $usuario instanceof User && $usuario->coordina(),
        ]);
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

    private function lista(string $q)
    {
        $cajaId = CajaCobro::enCurso()?->id;

        return Cliente::query()
            ->withCount(['prestamos' => fn ($consulta) => $this->delCobro($consulta, $cajaId)])
            ->withSum(['cuotas as saldo' => function ($query) use ($cajaId) {
                $query->where('cuotas.saldo', '>', 0)
                    ->whereHas('prestamo', fn ($p) => $this->delCobro($p, $cajaId)->whereIn('estado', ['activo', 'detenido']));
            }], 'saldo')
            ->withExists(['prestamos as restringido' => function ($query) use ($cajaId) {
                $query->whereIn('estado', ['activo', 'detenido'])
                    ->when($cajaId, fn ($q) => $q->where(function ($q) use ($cajaId) {
                        $q->where('caja_cobro_id', '!=', $cajaId)->orWhereNull('caja_cobro_id');
                    }));
            }])
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($filtro) use ($q) {
                    $filtro->where('nombre', 'like', "%{$q}%")
                        ->orWhere('documento', 'like', "%{$q}%")
                        ->orWhere('telefono', 'like', "%{$q}%");
                });
            })
            ->orderBy('nombre')
            ->get();
    }

    private function delCobro(object $query, ?int $cajaId): object
    {
        return $cajaId
            ? $query->where($query->getModel()->getTable().'.caja_cobro_id', $cajaId)
            : $query->whereRaw('1 = 0');
    }

    private function fila(Cliente $cliente, bool $desdePrestamos = false): array
    {
        $cajaId = CajaCobro::enCurso()?->id;
        $saldo = $desdePrestamos
            ? (int) $cliente->prestamos->sum(fn (Prestamo $prestamo) => in_array($prestamo->estado, ['activo', 'detenido'], true) ? $prestamo->saldo() : 0)
            : (int) ($cliente->getAttribute('saldo') ?? 0);
        $restringido = $cliente->offsetExists('restringido')
            ? (bool) $cliente->restringido
            : $cliente->abiertoEnOtro($cajaId);
        $usuario = auth()->user();

        return [
            'id' => $cliente->id,
            'nombre' => $cliente->nombre,
            'documento' => $cliente->documento,
            'telefono' => $cliente->telefono,
            'direccion' => $cliente->direccion,
            'notas' => $cliente->notas,
            'latitud' => $cliente->latitud,
            'longitud' => $cliente->longitud,
            'prestamos' => (int) ($cliente->prestamos_count ?? $cliente->prestamos->count()),
            'saldo' => $saldo,
            'restringido' => $restringido,
            'bloqueado' => $restringido && ! ($usuario instanceof User && $usuario->coordina()),
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
