<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\EmpresaActual;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UbicacionCobradorController extends Controller
{
    public function guardar(Request $request): JsonResponse
    {
        $usuario = $request->user();
        abort_unless($usuario->rol === 'cobrador', 403, 'Solo el cobrador comparte su ubicación.');

        $datos = $request->validate([
            'latitud' => ['required', 'numeric', 'between:-90,90'],
            'longitud' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $usuario->forceFill([
            'latitud' => $datos['latitud'],
            'longitud' => $datos['longitud'],
            'ubicacion_en' => now(),
        ])->save();

        return response()->json(['ok' => true, 'token' => csrf_token()]);
    }

    public function index(): View
    {
        return view('en-vivo', ['cobradores' => $this->lista()]);
    }

    public function datos(): JsonResponse
    {
        return response()->json([
            'cobradores' => $this->lista(),
            'token' => csrf_token(),
        ]);
    }

    private function lista(): array
    {
        $empresaId = EmpresaActual::id();
        $limite = now()->subMinutes(2);

        return User::query()
            ->when($empresaId, fn ($q) => $q->where('empresa_id', $empresaId))
            ->when($empresaId === null, fn ($q) => $q->whereNull('empresa_id'))
            ->where('es_maestro', false)
            ->where('rol', 'cobrador')
            ->orderBy('name')
            ->get()
            ->map(function (User $usuario) use ($limite) {
                $enLinea = $usuario->ubicacion_en && $usuario->ubicacion_en->greaterThan($limite);

                return [
                    'id' => $usuario->id,
                    'nombre' => $usuario->name,
                    'latitud' => $usuario->latitud !== null ? (float) $usuario->latitud : null,
                    'longitud' => $usuario->longitud !== null ? (float) $usuario->longitud : null,
                    'en_linea' => $enLinea,
                    'hace' => $usuario->ubicacion_en
                        ? $usuario->ubicacion_en->locale('es')->diffForHumans()
                        : null,
                ];
            })
            ->values()
            ->all();
    }
}
