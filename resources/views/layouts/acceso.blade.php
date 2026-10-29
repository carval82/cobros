<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('titulo', 'Entrar · Cobras')</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <link rel="stylesheet" href="{{ asset('css/cobros.css') }}?v={{ filemtime(public_path('css/cobros.css')) }}">
</head>
<body class="pagina-acceso">
    <div class="acceso-marco">
        <aside class="acceso-relato">
            <img class="logo-oro" src="{{ asset('image/cobras-oro.jpg') }}" alt="Cobras, soluciones en cobranzas">
            <div>
                <p class="kicker" style="color:#e4c16a">En la calle</p>
                <h2>La cartera camina con quien cobra.</h2>
            </div>
            <p class="acceso-pie">Cada empresa abre su propia base y entra por su número de tenant, pegado a la raíz del sitio.</p>
        </aside>
        <main class="acceso">
            @if ($errors->any())
                <div class="aviso aviso-error">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif
            @yield('contenido')
        </main>
    </div>
</body>
</html>
