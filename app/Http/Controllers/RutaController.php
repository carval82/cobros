<?php

namespace App\Http\Controllers;

use App\Services\RutaCobro;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RutaController extends Controller
{
    public function index(Request $request, RutaCobro $ruta): View
    {
        $datos = $request->validate([
            'latitud' => ['nullable', 'numeric', 'between:-90,90'],
            'longitud' => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        $lat = isset($datos['latitud']) ? (float) $datos['latitud'] : null;
        $lng = isset($datos['longitud']) ? (float) $datos['longitud'] : null;

        return view('ruta', [
            'ruta' => $ruta->armar($lat, $lng),
            'origen' => $lat !== null && $lng !== null,
        ]);
    }
}
