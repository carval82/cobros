<?php

namespace App\Http\Controllers;

use App\Support\RendimientoCobro;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RendimientoController extends Controller
{
    public function index(Request $request): View
    {
        [$desde, $hasta] = $this->periodo($request);

        return view('rendimiento', [
            'propio' => false,
            'desde' => $desde,
            'hasta' => $hasta,
            'informe' => RendimientoCobro::armar($desde, $hasta),
        ]);
    }

    public function mio(Request $request): View
    {
        [$desde, $hasta] = $this->periodo($request);

        return view('rendimiento', [
            'propio' => true,
            'desde' => $desde,
            'hasta' => $hasta,
            'informe' => RendimientoCobro::armar($desde, $hasta, (int) $request->user()->id),
        ]);
    }

    public function datos(Request $request): JsonResponse
    {
        [$desde, $hasta] = $this->periodo($request);

        return response()->json($this->json($desde, $hasta));
    }

    public function datosMios(Request $request): JsonResponse
    {
        [$desde, $hasta] = $this->periodo($request);

        return response()->json($this->json($desde, $hasta, (int) $request->user()->id));
    }

    private function json(string $desde, string $hasta, ?int $usuarioId = null): array
    {
        return RendimientoCobro::armar($desde, $hasta, $usuarioId) + [
            'token' => csrf_token(),
        ];
    }

    private function periodo(Request $request): array
    {
        $datos = $request->validate([
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date'],
        ]);

        $desde = $datos['desde'] ?? now()->startOfMonth()->toDateString();
        $hasta = $datos['hasta'] ?? now()->toDateString();

        if ($desde > $hasta) {
            [$desde, $hasta] = [$hasta, $desde];
        }

        return [$desde, $hasta];
    }
}
