@extends('layouts.app')

@section('titulo', 'Licencia · Cobros')

@section('contenido')
    <header class="encabezado">
        <div>
            <h1>Activar {{ $empresa->nombre }}</h1>
            <p>
                @if ($empresa->puedeOperar())
                    La licencia está al día
                    @if ($empresa->fecha_vencimiento)
                        hasta {{ $empresa->fecha_vencimiento->format('d/m/Y') }}
                    @endif
                    .
                @else
                    Esta empresa no puede cobrar hasta activar la licencia.
                @endif
            </p>
        </div>
    </header>

    <section class="panel" style="max-width:640px">
        <p>Lleva este código de solicitud al activador y pega aquí el código que te devuelva.</p>
        <p><strong>{{ $empresa->codigo_solicitud }}</strong></p>
        <form method="post" action="{{ route('licencia.activar') }}">
            @csrf
            <label>Código de activación
                <input name="codigo" value="{{ old('codigo') }}" autocomplete="off" required>
            </label>
            <button class="btn" type="submit">Activar</button>
        </form>
    </section>
@endsection
