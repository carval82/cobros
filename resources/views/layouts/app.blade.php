<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('titulo', 'Cobras')</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <link rel="stylesheet" href="{{ asset('css/cobros.css') }}?v={{ filemtime(public_path('css/cobros.css')) }}">
</head>
<body>
    <div class="app">
        <aside class="side">
            <a class="marca" href="{{ auth()->user()->es_maestro ? route('empresas.index') : cobro_route('dashboard') }}">
                <img class="marca-logo" src="{{ asset('image/cobras.png') }}" alt="Cobras, soluciones en cobranzas">
            </a>
            <nav>
                @if (auth()->user()->es_maestro)
                    <a href="{{ cobro_route('empresas.index') }}" @class(['active' => request()->routeIs('empresas.*')])>Empresas</a>
                @endif
                @if (! auth()->user()->es_maestro || session('empresa_id') || app()->bound('empresa.tenant'))
                    <a href="{{ cobro_route('dashboard') }}" @class(['active' => request()->routeIs('dashboard', 'tenant.dashboard')])>Hoy</a>
                    <a href="{{ cobro_route('ruta') }}" @class(['active' => request()->routeIs('ruta', 'tenant.ruta')])>Ruta</a>
                    <a href="{{ cobro_route('atrasados') }}" @class(['active' => request()->routeIs('atrasados', 'tenant.atrasados')])>Atrasados</a>
                    <a href="{{ cobro_route('clientes.index') }}" @class(['active' => request()->routeIs('clientes.*', 'tenant.clientes.*')])>Clientes</a>
                    @if (auth()->user()->puede('prestamos.crear'))
                        <a href="{{ cobro_route('prestamos.create') }}" @class(['active' => request()->routeIs('prestamos.create', 'tenant.prestamos.create')])>Nuevo préstamo</a>
                    @endif
                    @if (auth()->user()->puede('usuarios.gestionar'))
                        <a href="{{ cobro_route('usuarios.index') }}" @class(['active' => request()->routeIs('usuarios.*', 'tenant.usuarios.*')])>Usuarios</a>
                    @endif
                @endif
                <a href="{{ route('app.movil') }}" @class(['active' => request()->routeIs('app.movil')])>App del celular</a>
                <a href="{{ cobro_route('acerca') }}" @class(['active' => request()->routeIs('acerca', 'tenant.acerca')])>Acerca de</a>
            </nav>
            <div class="sesion">
                <p>{{ auth()->user()->name }}</p>
                @unless (auth()->user()->es_maestro)
                    <p>{{ \App\Support\Permisos::ROLES[auth()->user()->rol] ?? 'Administrador' }}</p>
                @endunless
                @if ($empresa = \App\Support\EmpresaActual::empresa())
                    <p>{{ $empresa->nombre }}</p>
                    @if (auth()->user()->es_maestro)
                        <form method="post" action="{{ cobro_route('empresas.salir') }}">
                            @csrf
                            <button class="salir" type="submit">Cambiar empresa</button>
                        </form>
                    @endif
                @endif
                <form method="post" action="{{ cobro_route('logout') }}">
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
