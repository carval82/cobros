<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class AppMovilController extends Controller
{
    public function index(): View
    {
        $descargas = [];

        foreach ([
            'cobras.apk' => 'Android de 64 bits',
            'cobras-32.apk' => 'Android de 32 bits',
        ] as $nombre => $etiqueta) {
            $ruta = public_path('descargas/'.$nombre);
            if (! is_file($ruta)) {
                continue;
            }

            $descargas[] = [
                'nombre' => $nombre,
                'etiqueta' => $etiqueta,
                'megas' => number_format(filesize($ruta) / 1048576, 1, ',', ''),
            ];
        }

        return view('app-movil', ['descargas' => $descargas]);
    }
}
