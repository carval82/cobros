<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('titulo', 'Guía · Cobras')</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <link rel="stylesheet" href="{{ asset('css/cobros.css') }}?v={{ filemtime(public_path('css/cobros.css')) }}">
</head>
<body>
    <main class="guia-publica">
        <a class="marca-publica" href="{{ route('guia') }}">
            <img src="{{ asset('image/cobras.png') }}" alt="Cobras" width="120" height="111">
        </a>
        @if ($errors->any())
            <div class="aviso aviso-error">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif
        @yield('contenido')
    </main>
    @stack('scripts')
</body>
</html>
