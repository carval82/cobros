@if ($cobradoresEmpresa->isNotEmpty())
    <fieldset class="equipo-cobro">
        <legend>Cobradores en este cobro</legend>
        <p class="ayuda">Sin marcar, el administrador lo desarrolla solo. Con uno o varios, ellos hacen la ruta de este mismo cobro.</p>
        @foreach ($cobradoresEmpresa as $persona)
            <label class="eleccion">
                <input type="checkbox" name="cobradores[]" value="{{ $persona->id }}" @checked(in_array($persona->id, $equipoIds ?? [], true))>
                {{ $persona->name }}
            </label>
        @endforeach
    </fieldset>
@endif
