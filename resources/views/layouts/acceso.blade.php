<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('titulo', 'Entrar · Cobros')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Newsreader:opsz,wght@6..72,500;6..72,650&family=Outfit:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/cobros.css') }}">
</head>
<body class="pagina-acceso">
    <main class="acceso">
        <a class="marca marca-clara" href="{{ route('login') }}">
            <span class="marca-sigla">Cb</span>
            <span>
                <strong>Cobros</strong>
                <small>Cartera y cuotas</small>
            </span>
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
</body>
</html>
