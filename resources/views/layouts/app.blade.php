<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('titulo', 'Cobros')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Newsreader:opsz,wght@6..72,500;6..72,650&family=Outfit:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/cobros.css') }}">
</head>
<body>
    <div class="app">
        <aside class="side">
            <a class="marca" href="{{ route('dashboard') }}">
                <span class="marca-sigla">Cb</span>
                <span>
                    <strong>Cobros</strong>
                    <small>Cartera y cuotas</small>
                </span>
            </a>
            <nav>
                <a href="{{ route('dashboard') }}" @class(['active' => request()->routeIs('dashboard')])>Hoy</a>
                <a href="{{ route('atrasados') }}" @class(['active' => request()->routeIs('atrasados')])>Atrasados</a>
                <a href="{{ route('clientes.index') }}" @class(['active' => request()->routeIs('clientes.*')])>Clientes</a>
                <a href="{{ route('prestamos.create') }}" @class(['active' => request()->routeIs('prestamos.create')])>Nuevo préstamo</a>
            </nav>
            <div class="sesion">
                <p>{{ auth()->user()->name }}</p>
                <form method="post" action="{{ route('logout') }}">
                    @csrf
                    <button class="salir" type="submit">Salir</button>
                </form>
            </div>
        </aside>
        <main>
            @if (session('ok'))
                <p class="aviso aviso-ok">{{ session('ok') }}</p>
            @endif
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
    @stack('scripts')
</body>
</html>
