@extends('layouts.app')

@section('titulo', 'Guía · Cobros')

@section('contenido')
    <header class="encabezado">
        <div>
            <p class="kicker">Para el equipo</p>
            <h1>Guía de Cobros</h1>
            <p>Una charla corta para aprender el manejo. Elige un caso y sigue los pasos.</p>
        </div>
    </header>

    <div class="relato">
        <article class="charla charla-inicio" id="inicio">
            <div class="escenario">
                <p class="mini-nota">Dos cobros, dos cajas</p>
                <div class="mini-par">
                    <div class="mini-cobro">
                        <span>Norte</span>
                        <strong>$ 500.000</strong>
                        <p>Su plata y sus préstamos</p>
                    </div>
                    <div class="mini-cobro mini-otro">
                        <span>Sur</span>
                        <strong>$ 200.000</strong>
                        <p>Otra caja, otra historia</p>
                    </div>
                </div>
            </div>
            <div class="burbuja">
                <img src="{{ asset('image/cobras.png') }}" alt="" width="72" height="66">
                <div>
                    <p class="kicker">Guía</p>
                    <h2>¿Por dónde empezamos?</h2>
                    <p>Cobros es el cuaderno de quien presta y cobra en la calle. Cada cobro es una caja distinta. Tú eliges y yo te llevo.</p>
                    <div class="respuestas">
                        <a class="btn" href="#dia-caja">Ver un día de cobro</a>
                        <a class="btn" href="#roles">Quién puede hacer qué</a>
                        <a class="btn" href="#otro-debe">Un cliente que ya debe en otro cobro</a>
                        <a class="btn" href="#celular">Usarlo en el celular</a>
                    </div>
                </div>
            </div>
        </article>

        <article class="charla" id="dia-caja">
            <div class="escenario">
                <p class="mini-nota">Lo de Norte se queda en Norte</p>
                <div class="mini-par">
                    <div class="mini-cobro">
                        <span>Norte</span>
                        <strong>Ana</strong>
                        <p>Debe $ 120.000 aquí</p>
                    </div>
                    <div class="mini-cobro mini-otro">
                        <span>Sur</span>
                        <strong>Ana</strong>
                        <p>Saldo $ 0 en este cobro</p>
                    </div>
                </div>
            </div>
            <div class="burbuja">
                <div>
                    <p class="kicker">Paso 1 de 6</p>
                    <h2>Cada cobro es su propia caja</h2>
                    <p>Norte tiene su plata y sus préstamos. Sur tiene los suyos. Lo que se presta en uno se cobra en ese mismo. El otro cobro no trae esa deuda ni esa historia.</p>
                    <div class="acciones">
                        <a class="btn" href="#dia-abrir">Siguiente</a>
                        <a class="btn-linea" href="#inicio">Elegir otro caso</a>
                    </div>
                </div>
            </div>
        </article>

        <article class="charla" id="dia-abrir">
            <div class="escenario">
                <p class="mini-nota">Así se abre el día</p>
                <div class="mini-cobro">
                    <span>Nuevo cobro</span>
                    <strong>$ 500.000</strong>
                    <p>La plata con la que se sale</p>
                </div>
                <div class="mini-menu">
                    <span>Solo</span>
                    <span>O con cobradores</span>
                </div>
            </div>
            <div class="burbuja">
                <div>
                    <p class="kicker">Paso 2 de 6</p>
                    <h2>El día empieza al abrir</h2>
                    <p>Se anota con cuánta plata se sale. Esa es la base. El administrador puede abrir varios cobros. El cobrador abre uno. Puede ir solo o llevar a otros cobradores en el mismo cobro.</p>
                    <div class="acciones">
                        <a class="btn" href="#dia-entrar">Siguiente</a>
                        <a class="btn-linea" href="#dia-caja">Atrás</a>
                    </div>
                </div>
            </div>
        </article>

        <article class="charla" id="dia-entrar">
            <div class="escenario">
                <p class="mini-nota">Hoy, antes de entrar</p>
                <div class="mini-fila">
                    <div>
                        <strong>Ruta centro</strong>
                        <p>Solo el nombre</p>
                    </div>
                    <span class="mini-entrar">Entrar</span>
                </div>
                <p class="mini-nota">Después de entrar</p>
                <div class="mini-menu">
                    <span>Cuadre</span>
                    <span>Ruta</span>
                    <span>Atrasados</span>
                    <span>Clientes</span>
                    <span>Nuevo préstamo</span>
                </div>
            </div>
            <div class="burbuja">
                <div>
                    <p class="kicker">Paso 3 de 6</p>
                    <h2>Primero se entra al cobro</h2>
                    <p>En Hoy se ve el nombre y el botón Entrar. Las cifras, la ruta y los clientes aparecen después. Para cambiar de cobro se toca Volver a los cobros.</p>
                    <div class="acciones">
                        <a class="btn" href="#dia-prestar">Siguiente</a>
                        <a class="btn-linea" href="#dia-abrir">Atrás</a>
                    </div>
                </div>
            </div>
        </article>

        <article class="charla" id="dia-prestar">
            <div class="escenario">
                <p class="mini-nota">Cliente y préstamo de este cobro</p>
                <div class="mini-persona">
                    <strong>Ana Pérez</strong>
                    <p>300 111 2233</p>
                </div>
                <div class="mini-cobro">
                    <span>Se le entregan $ 100.000</span>
                    <strong>$ 120.000</strong>
                    <p>Al 20 %, en pagos semanales</p>
                </div>
            </div>
            <div class="burbuja">
                <div>
                    <p class="kicker">Paso 4 de 6</p>
                    <h2>Se anota la persona y el préstamo</h2>
                    <p>Nombre y teléfono. Luego, cuánto se le entrega, el interés y cada cuánto paga. Esa plata sale de la caja de este cobro.</p>
                    <div class="acciones">
                        <a class="btn" href="#dia-cobrar">Siguiente</a>
                        <a class="btn-linea" href="#dia-entrar">Atrás</a>
                    </div>
                </div>
            </div>
        </article>

        <article class="charla" id="dia-cobrar">
            <div class="escenario">
                <p class="mini-nota">El cliente pagó</p>
                <div class="mini-cobro">
                    <span>Abono de hoy</span>
                    <strong>$ 24.000</strong>
                    <p>Saldo que sigue: $ 96.000</p>
                </div>
                <div class="mini-mensaje">Hola Ana, registramos tu abono. Saldo pendiente: $ 96.000.</div>
            </div>
            <div class="burbuja">
                <div>
                    <p class="kicker">Paso 5 de 6</p>
                    <h2>Cuando paga, se anota</h2>
                    <p>El abono baja el saldo. Si el teléfono es de Colombia, queda armado un mensaje de WhatsApp para contarle cuánto debe y cuándo sigue.</p>
                    <div class="acciones">
                        <a class="btn" href="#dia-cerrar">Siguiente</a>
                        <a class="btn-linea" href="#dia-prestar">Atrás</a>
                    </div>
                </div>
            </div>
        </article>

        <article class="charla" id="dia-cerrar">
            <div class="escenario">
                <p class="mini-nota">Cierre del día</p>
                <div class="mini-par">
                    <div class="mini-cobro">
                        <span>Retirar</span>
                        <strong>La ganancia</strong>
                        <p>Mañana se sale con la misma base</p>
                    </div>
                    <div class="mini-cobro mini-otro">
                        <span>Sumar</span>
                        <strong>A la base</strong>
                        <p>Mañana se sale con más plata</p>
                    </div>
                </div>
            </div>
            <div class="burbuja">
                <div>
                    <p class="kicker">Paso 6 de 6</p>
                    <h2>Al final se cuenta la caja</h2>
                    <p>Se revisa que la plata cuadre. La ganancia se puede retirar o dejar sumada para el día siguiente. Los gastos de la ruta también se anotan ahí.</p>
                    <div class="respuestas">
                        <a class="btn" href="#otro-debe">Ver el cliente que ya debe en otro cobro</a>
                        <a class="btn" href="{{ $hoy }}">Ir a Hoy</a>
                        <a class="btn-linea" href="#inicio">Volver al inicio</a>
                    </div>
                </div>
            </div>
        </article>

        <article class="charla" id="roles">
            <div class="escenario">
                <div class="mini-rol">
                    <span class="badge badge-plan">Administrador</span>
                    <p>Abre cobros, arma el equipo y aprueba.</p>
                </div>
                <div class="mini-rol">
                    <span class="badge badge-pause">Cobrador</span>
                    <p>Cobra, presta y anota en su ruta.</p>
                </div>
                <div class="mini-rol">
                    <span class="badge badge-wait">Consulta</span>
                    <p>Solo mira. No cambia nada.</p>
                </div>
            </div>
            <div class="burbuja">
                <div>
                    <p class="kicker">El equipo</p>
                    <h2>Quién puede hacer qué</h2>
                    <p>El administrador ve los cobros, abre varios y es quien aprueba un préstamo si la persona ya debe en otro. El cobrador trabaja su ruta: cobra y presta, y no aprueba ese caso. Consulta entra a mirar, sin cobrar ni prestar.</p>
                    <div class="respuestas">
                        <a class="btn" href="#dia-caja">Ver un día de cobro</a>
                        <a class="btn" href="#otro-debe">Ver el cliente restringido</a>
                        <a class="btn-linea" href="#inicio">Volver al inicio</a>
                    </div>
                </div>
            </div>
        </article>

        <article class="charla" id="otro-debe">
            <div class="escenario">
                <p class="mini-nota">Cobro Norte</p>
                <div class="mini-persona">
                    <strong>Ana Pérez</strong>
                    <p>Préstamo abierto en este cobro</p>
                </div>
                <div class="mini-cobro">
                    <span>Saldo en Norte</span>
                    <strong>$ 120.000</strong>
                </div>
            </div>
            <div class="burbuja">
                <div>
                    <p class="kicker">Paso 1 de 3</p>
                    <h2>Ana ya debe en Norte</h2>
                    <p>Ese préstamo, ese saldo y esa historia son de Norte. Si mañana se abre otro cobro, Norte sigue con lo suyo.</p>
                    <div class="acciones">
                        <a class="btn" href="#otro-limpio">Siguiente</a>
                        <a class="btn-linea" href="#inicio">Elegir otro caso</a>
                    </div>
                </div>
            </div>
        </article>

        <article class="charla" id="otro-limpio">
            <div class="escenario">
                <p class="mini-nota">Cobro Sur, el mismo cliente</p>
                <div class="mini-persona">
                    <strong>Ana Pérez</strong>
                    <span class="badge badge-pause">Restringido</span>
                </div>
                <div class="mini-cobro mini-otro">
                    <span>Saldo en Sur</span>
                    <strong>$ 0</strong>
                    <p>Aquí todavía no tiene préstamos</p>
                </div>
            </div>
            <div class="burbuja">
                <div>
                    <p class="kicker">Paso 2 de 3</p>
                    <h2>En el cobro nuevo aparece limpia</h2>
                    <p>Ana sí sale en la lista de Sur. Su saldo aquí es cero y no se ve el historial de Norte. La marca Restringido avisa que ya debe en otro cobro.</p>
                    <div class="acciones">
                        <a class="btn" href="#otro-aprueba">Siguiente</a>
                        <a class="btn-linea" href="#otro-debe">Atrás</a>
                    </div>
                </div>
            </div>
        </article>

        <article class="charla" id="otro-aprueba">
            <div class="escenario">
                <div class="mini-par">
                    <div class="mini-rol">
                        <span class="badge badge-pause">Cobrador</span>
                        <p>No puede prestarle en Sur.</p>
                    </div>
                    <div class="mini-rol">
                        <span class="badge badge-plan">Administrador</span>
                        <p>Al crear el préstamo, lo aprueba.</p>
                    </div>
                </div>
                <div class="mini-cobro mini-otro">
                    <span>Lo que se ve en Sur después</span>
                    <strong>$ 10.000</strong>
                    <p>Solo el préstamo nuevo</p>
                </div>
            </div>
            <div class="burbuja">
                <div>
                    <p class="kicker">Paso 3 de 3</p>
                    <h2>El administrador aprueba con el préstamo</h2>
                    <p>El cobrador encuentra a Ana restringida y no puede prestarle en Sur. El administrador sí puede. Ese préstamo nuevo es la aprobación. En Sur solo se ve lo de Sur. Norte conserva sus $ 120.000.</p>
                    <div class="respuestas">
                        <a class="btn" href="{{ $hoy }}">Ir a Hoy</a>
                        <a class="btn" href="#celular">Ver el celular</a>
                        <a class="btn-linea" href="#inicio">Volver al inicio</a>
                    </div>
                </div>
            </div>
        </article>

        <article class="charla" id="celular">
            <div class="escenario">
                <div class="mini-telefono">
                    <p class="mini-nota">Hoy</p>
                    <div class="mini-fila">
                        <strong>Ruta centro</strong>
                        <span class="mini-entrar">Entrar</span>
                    </div>
                    <p>La misma cuenta, en la calle.</p>
                </div>
            </div>
            <div class="burbuja">
                <div>
                    <p class="kicker">En el teléfono</p>
                    <h2>La misma ruta, en el celular</h2>
                    <p>Se entra con el mismo correo. Lo que se cobra o se presta queda en el mismo cobro. La primera vez se pega la dirección de la empresa y, de ahí en adelante, abre en Hoy.</p>
                    <div class="respuestas">
                        <a class="btn" href="{{ $celular }}">Ver cómo se configura</a>
                        <a class="btn" href="#dia-caja">Ver un día de cobro</a>
                        <a class="btn-linea" href="#inicio">Volver al inicio</a>
                    </div>
                </div>
            </div>
        </article>
    </div>
@endsection

@push('scripts')
    <script>
        function enfocarGuia() {
            var id = location.hash.replace('#', '') || 'inicio';
            var escena = document.getElementById(id) || document.getElementById('inicio');
            var titulo = escena && escena.querySelector('h2');
            if (!titulo) return;
            titulo.setAttribute('tabindex', '-1');
            titulo.focus({ preventScroll: true });
            escena.scrollIntoView({ block: 'nearest' });
        }
        window.addEventListener('hashchange', enfocarGuia);
        if (location.hash) enfocarGuia();
    </script>
@endpush
