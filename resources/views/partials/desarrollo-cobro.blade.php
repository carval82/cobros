@if ($informe && ! $informe['sin_apertura'])
    @if ($informe['falta_cerrar'])
        <p class="aviso">El día {{ $informe['falta_cerrar'] }} sigue abierto. Ciérralo para dejar el valor inicial del siguiente.</p>
    @endif

    @if ($informe['puede_abrir_dia'] && ! empty($informe['edita']))
        <section class="panel">
            <h2>Abrir el día</h2>
            <p class="ayuda">Sales con @dinero($informe['en_caja']), el efectivo que dejó el cierre anterior. La base sigue en @dinero($informe['base']).</p>
            <form method="post" action="{{ cobro_route('cuadre.dia') }}">
                @csrf
                @include('partials.seguir-hoy')
                <input type="hidden" name="caja_id" value="{{ $informe['id'] }}">
                <button class="btn" type="submit">Abrir el día con este valor</button>
            </form>
        </section>
    @endif

    @if ($informe['jornada_abierta'] || $informe['jornada_cerrada'])
        <section class="grid-cifras">
            <article class="cifra">
                <span>Base</span>
                <strong>@dinero($informe['base'])</strong>
            </article>
            <article class="cifra">
                <span>Valor inicial del día</span>
                <strong>@dinero($informe['valor_inicial'])</strong>
            </article>
            <article class="cifra">
                <span>En caja</span>
                <strong>@dinero($informe['en_caja'])</strong>
            </article>
            <article class="cifra">
                <span>{{ $informe['jornada_cerrada'] ? 'Quedó para el siguiente' : 'Para el siguiente día' }}</span>
                <strong>@dinero($informe['jornada_cerrada'] ? $informe['valor_siguiente'] : $informe['cierre'])</strong>
            </article>
            <article class="cifra">
                <span>Recaudado hoy</span>
                <strong>@dinero($informe['recogido_hoy'])</strong>
            </article>
            <article class="cifra">
                <span>Prestado hoy</span>
                <strong>@dinero($informe['prestado_hoy'])</strong>
            </article>
            <article class="cifra">
                <span>Gastos hoy</span>
                <strong>@dinero($informe['gastos_hoy'])</strong>
            </article>
            <article class="cifra">
                <span>Ganancia del día</span>
                <strong>@dinero($informe['ganancia'])</strong>
            </article>
        </section>

        <p class="ayuda">
            Valor inicial @dinero($informe['valor_inicial'])
            + sumado a la base @dinero($informe['refuerzo_hoy'])
            − prestado @dinero($informe['prestado_hoy'])
            + recaudado @dinero($informe['recogido_hoy'])
            − gastos @dinero($informe['gastos_hoy'])
            = @dinero($informe['cierre']).
            Si retiras la ganancia, mañana abres con @dinero($informe['manana_retirar']) y la base se sostiene.
            Si la sumas, mañana abres con @dinero($informe['manana_sumar']) y la base crece.
        </p>

        @if ($informe['jornada_cerrada'])
            <p class="ayuda">
                @if ($informe['decision'] === 'retirar')
                    Este día cerró retirando la ganancia. La base se sostuvo y el siguiente día abre con @dinero($informe['valor_siguiente']).
                @else
                    Este día sumó la ganancia a la base. El siguiente día abre con @dinero($informe['valor_siguiente']).
                @endif
            </p>
        @endif

        <div class="dos">
            <section class="panel">
                <h2>Préstamos del día</h2>
                @forelse ($informe['prestamos_hoy'] as $fila)
                    <p>{{ $fila['cliente'] }} · @dinero($fila['capital'])</p>
                @empty
                    <p class="vacio">Hoy no salió dinero en préstamos.</p>
                @endforelse
                <h2 style="margin-top:22px">Abonos del día</h2>
                @forelse ($informe['pagos_hoy'] as $fila)
                    <p>{{ $fila['cliente'] }} · @dinero($fila['monto'])</p>
                @empty
                    <p class="vacio">Hoy no entraron abonos.</p>
                @endforelse
            </section>
            <section class="panel">
                <h2>Gastos del día</h2>
                @forelse ($informe['gastos_hoy_lista'] as $fila)
                    <p>{{ $fila['nota'] }} · @dinero($fila['monto'])</p>
                @empty
                    <p class="vacio">Hoy no hay gastos.</p>
                @endforelse
            </section>
        </div>

        @if (! empty($informe['edita']) && $informe['jornada_abierta'])
            <div class="dos">
                <section class="panel">
                    <h2>Cerrar el día</h2>
                    <p class="ayuda">Retirar la ganancia deja la base igual. Sumarla hace crecer la base y el efectivo se queda en el cobro.</p>
                    <form method="post" action="{{ cobro_route('cuadre.cerrar') }}">
                        @csrf
                        @include('partials.seguir-hoy')
                        <input type="hidden" name="caja_id" value="{{ $informe['id'] }}">
                        <input type="hidden" name="fecha" value="{{ $fecha }}">
                        <input type="hidden" name="decision" value="retirar">
                        <button class="btn" type="submit">Retirar la ganancia</button>
                    </form>
                    <form method="post" action="{{ cobro_route('cuadre.cerrar') }}" style="margin-top:10px">
                        @csrf
                        @include('partials.seguir-hoy')
                        <input type="hidden" name="caja_id" value="{{ $informe['id'] }}">
                        <input type="hidden" name="fecha" value="{{ $fecha }}">
                        <input type="hidden" name="decision" value="sumar">
                        <button class="btn-linea" type="submit">Sumar la ganancia a la base</button>
                    </form>
                </section>
                <section class="panel">
                    <h2>Sumar dinero a la base</h2>
                    <form method="post" action="{{ cobro_route('cuadre.refuerzo') }}">
                        @csrf
                        @include('partials.seguir-hoy')
                        <input type="hidden" name="caja_id" value="{{ $informe['id'] }}">
                        <input type="hidden" name="fecha" value="{{ $fecha }}">
                        <label>Valor
                            <input name="monto" type="number" min="1" step="1" required>
                        </label>
                        <button class="btn" type="submit">Sumar a la base</button>
                    </form>
                    <h2 style="margin-top:22px">Registrar gasto</h2>
                    <form method="post" action="{{ cobro_route('cuadre.gasto') }}">
                        @csrf
                        @include('partials.seguir-hoy')
                        <input type="hidden" name="caja_id" value="{{ $informe['id'] }}">
                        <label>Valor
                            <input name="monto" type="number" min="1" step="1" required>
                        </label>
                        <label>Fecha
                            <input name="fecha" type="date" value="{{ $fecha }}" required>
                        </label>
                        <label>Nota
                            <input name="nota" maxlength="180" placeholder="Transporte, almuerzo, papelería">
                        </label>
                        <button class="btn" type="submit">Guardar gasto</button>
                    </form>
                </section>
            </div>
        @endif
    @endif
@endif
