@extends('layouts.app')

@section('titulo', 'Usuarios · Cobras')

@section('contenido')
    <header class="encabezado">
        <div>
            <p class="kicker">Equipo</p>
            <h1>Usuarios</h1>
            <p>El administrador abre la empresa. El cobrador registra abonos y préstamos. Consulta solo mira la cartera.</p>
        </div>
    </header>

    <div class="tablero">
        <section class="panel">
            <h2>Nuevo usuario</h2>
            <form method="post" action="{{ cobro_route('usuarios.store') }}">
                @csrf
                <label>Nombre
                    <input name="name" value="{{ old('name') }}" required>
                </label>
                <label>Correo
                    <input type="email" name="email" value="{{ old('email') }}" required>
                </label>
                <label>Contraseña
                    <input type="password" name="password" autocomplete="new-password" required>
                </label>
                <label>Confirmar contraseña
                    <input type="password" name="password_confirmation" autocomplete="new-password" required>
                </label>
                <label>Rol
                    <select name="rol" required>
                        @foreach ($roles as $clave => $etiqueta)
                            <option value="{{ $clave }}" @selected(old('rol') === $clave)>{{ $etiqueta }}</option>
                        @endforeach
                    </select>
                </label>
                <button class="btn" type="submit">Crear usuario</button>
            </form>
        </section>

        <section class="panel">
            <h2>Quienes entran</h2>
            <table>
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Correo</th>
                        <th>Rol</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($usuarios as $usuario)
                        <tr>
                            <td>{{ $usuario->name }}</td>
                            <td>{{ $usuario->email }}</td>
                            <td>
                                <form method="post" action="{{ cobro_route('usuarios.update', ['usuario' => $usuario]) }}">
                                    @csrf
                                    <select name="rol" onchange="this.form.submit()">
                                        @foreach ($roles as $clave => $etiqueta)
                                            <option value="{{ $clave }}" @selected($usuario->rol === $clave)>{{ $etiqueta }}</option>
                                        @endforeach
                                    </select>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>
    </div>
@endsection
