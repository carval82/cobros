<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('titulo', 'Entrar · Cobros')</title>
    <link rel="stylesheet" href="{{ asset('css/cobros.css') }}">
</head>
<body class="pagina-acceso">
    <div class="acceso-marco">
        <aside class="acceso-relato">
            <a class="marca" href="{{ cobro_route('login') }}">
                <span class="marca-sigla">Cb</span>
                <span>
                    <strong>Cobros</strong>
                    <small>Ruta de caja</small>
                </span>
            </a>
            <div>
                <p class="kicker" style="color:#dff25a">En la calle</p>
                <h2>La cartera camina con quien cobra.</h2>
                <span class="ruta-dibujo" aria-hidden="true"></span>
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
