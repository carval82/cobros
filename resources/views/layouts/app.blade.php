<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('titulo', 'Cobros')</title>
    <link rel="stylesheet" href="{{ asset('css/cobros.css') }}">
</head>
<body>
    <div class="app">
        <aside class="side">
            <a class="marca" href="{{ cobro_route('dashboard') }}">
                <span class="marca-sigla">Cb</span>
                <span>
                    <strong>Cobros</strong>
                    <small>Ruta de caja</small>
                </span>
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
                    <a href="{{ cobro_route('prestamos.create') }}" @class(['active' => request()->routeIs('prestamos.create', 'tenant.prestamos.create')])>Nuevo préstamo</a>
                @endif
                <a href="{{ cobro_route('acerca') }}" @class(['active' => request()->routeIs('acerca', 'tenant.acerca')])>Acerca de</a>
            </nav>
            <div class="sesion">
                <p>{{ auth()->user()->name }}</p>
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
