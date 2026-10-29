<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
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

    public function create(): View
    {
        return view('clientes.form', ['cliente' => new Cliente]);
    }

    public function store(Request $request): RedirectResponse
    {
        $cliente = Cliente::create($this->validar($request));

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

    public function update(Request $request, Cliente $cliente): RedirectResponse
    {
        $cliente->update($this->validar($request));

        return redirect()
            ->to(cobro_route('clientes.show', $cliente))
            ->with('ok', 'Datos del cliente actualizados.');
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
