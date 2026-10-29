@extends('layouts.app')

@section('titulo', ($cliente->exists ? 'Editar cliente' : 'Nuevo cliente').' · Cobros')

@section('contenido')
    <header class="encabezado">
        <div>
            <h1>{{ $cliente->exists ? 'Editar cliente' : 'Nuevo cliente' }}</h1>
        </div>
    </header>
    <section class="panel" style="max-width:640px">
        <form method="post" action="{{ $cliente->exists ? cobro_route('clientes.update', $cliente) : cobro_route('clientes.store') }}">
            @csrf
            @if ($cliente->exists)
                @method('PUT')
            @endif
            <label>Nombre
                <input name="nombre" value="{{ old('nombre', $cliente->nombre) }}" required>
            </label>
            <div class="fila-form">
                <label>Documento
                    <input name="documento" value="{{ old('documento', $cliente->documento) }}">
                </label>
                <label>Teléfono
                    <input name="telefono" value="{{ old('telefono', $cliente->telefono) }}">
                </label>
            </div>
            <label>Dirección
                <input name="direccion" value="{{ old('direccion', $cliente->direccion) }}">
            </label>
            <label>Notas
                <textarea name="notas">{{ old('notas', $cliente->notas) }}</textarea>
            </label>
            <button class="btn" type="submit">Guardar</button>
        </form>
    </section>
@endsection
