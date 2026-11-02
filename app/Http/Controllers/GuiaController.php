<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class GuiaController extends Controller
{
    public function index(): View
    {
        return view('guia', [
            'hoy' => cobro_route('dashboard'),
            'celular' => route('app.movil'),
        ]);
    }
}
