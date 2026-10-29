<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>App del celular · Cobras</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <link rel="stylesheet" href="{{ asset('css/cobros.css') }}?v={{ filemtime(public_path('css/cobros.css')) }}">
</head>
<body>
    <main class="guia">
        <img src="{{ asset('image/cobras.png') }}" alt="Cobras" width="120" height="111">
        <p class="kicker">En el teléfono</p>
        <h1>Configura el celular</h1>
        <p>La primera vez dejas lista la empresa. Después la app abre directo en Hoy, con la misma dirección y el mismo correo.</p>

        @if ($descargas)
            <section class="panel">
                <h2>Bajar la app</h2>
                <p class="ayuda">Instálala desde esta página. Si el teléfono pregunta, permite orígenes desconocidos solo para este archivo.</p>
                @foreach ($descargas as $archivo)
                    <p>
                        <a class="btn" href="{{ asset('descargas/'.$archivo['nombre']) }}" download>{{ $archivo['etiqueta'] }} · {{ $archivo['megas'] }} MB</a>
                    </p>
                @endforeach
                <p class="ayuda">Casi todos los teléfonos de ahora usan 64 bits. El de 32 bits es para un equipo más viejo.</p>
            </section>
        @else
            <p class="aviso aviso-error">El archivo de la app todavía no está publicado en este servidor.</p>
        @endif

        <section class="panel">
            <h2>La primera vez</h2>
            <ol>
                <li>Abre Cobras. Si vas a estrenar la empresa, déjala en <strong>Crear empresa</strong>: nombre, administrador, correo y clave. La app guarda la dirección <strong>/tenant/</strong> que se genera.</li>
                <li>Si la empresa ya existe, toca <strong>Ya tengo empresa</strong>. En Servidor deja la raíz del sitio. En Dirección pega el enlace completo, el que termina en <strong>/tenant/</strong> y el número de la empresa.</li>
                <li>Escribe el correo y la clave de esa persona. Puede ser el administrador, un cobrador o consulta.</li>
                <li>Acepta la ubicación. Sin eso no se guarda el punto del cobro ni se ordena la ruta desde donde estás.</li>
            </ol>
        </section>

        <section class="panel">
            <h2>Lo que queda guardado</h2>
            <p>El teléfono recuerda el servidor, la dirección de la empresa, el correo y la sesión. La próxima vez que abras Cobras entras a Hoy sin volver a escribir nada.</p>
            <p class="ayuda">Salir, en la pestaña Más, cierra la sesión. La dirección y el correo siguen ahí: solo pides la clave otra vez.</p>
        </section>

        <p class="ayuda"><a href="{{ route('dashboard') }}">Volver al inicio</a></p>
    </main>
</body>
</html>
