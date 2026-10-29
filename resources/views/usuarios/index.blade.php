@extends('layouts.app')

@section('titulo', 'Usuarios · Cobras')

@section('contenido')
    <header class="encabezado">
        <div>
            <p class="kicker">Equipo</p>
            <h1>Usuarios</h1>
            <p>El administrador abre la empresa. El cobrador registra abonos y se queda con un porcentaje de lo que cobre. Consulta solo mira la cartera.</p>
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
                            <option value="{{ $clave }}" @selected(old('rol', 'cobrador') === $clave)>{{ $etiqueta }}</option>
                        @endforeach
                    </select>
                </label>
                <label>% de lo que cobre
                    <input type="number" name="porcentaje_cobro" min="0" max="100" step="0.5" value="{{ old('porcentaje_cobro', '10') }}" required>
                </label>
                <p class="ayuda">De cada abono que esa persona registre. Un cobrador nuevo queda en 10%.</p>
                <button class="btn" type="submit">Crear usuario</button>
            </form>
        </section>

        <section class="panel">
            <h2>Quienes entran</h2>
            <div class="tabla-wrap"><table>
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Correo</th>
                        <th>Rol y porcentaje</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($usuarios as $usuario)
                        <tr>
                            <td>{{ $usuario->name }}</td>
                            <td>{{ $usuario->email }}</td>
                            <td>
                                <form class="equipo-form" method="post" action="{{ cobro_route('usuarios.update', ['usuario' => $usuario]) }}">
                                    @csrf
                                    <select name="rol">
                                        @foreach ($roles as $clave => $etiqueta)
                                            <option value="{{ $clave }}" @selected($usuario->rol === $clave)>{{ $etiqueta }}</option>
                                        @endforeach
                                    </select>
                                    <input type="number" name="porcentaje_cobro" min="0" max="100" step="0.5" value="{{ $usuario->porcentaje_cobro }}" aria-label="Porcentaje de {{ $usuario->name }}">
                                    <button class="btn" type="submit">Guardar</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table></div>
        </section>
    </div>
@endsection
