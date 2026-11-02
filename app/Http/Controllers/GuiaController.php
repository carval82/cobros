<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class GuiaController extends Controller
{
    public function index(): View
    {
        return view('guia', [
            'hoy' => auth()->check() ? cobro_route('dashboard') : null,
            'celular' => route('app.movil'),
            'puedeCrear' => auth()->guest(),
            'direccionGuia' => route('guia'),
        ]);
    }
}
