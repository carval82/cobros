@extends('layouts.publico')

@section('titulo', 'Visita guiada · Cobros')

@section('contenido')
    <section id="puerta">
        <p class="kicker">Visita guiada</p>
        <h1>¿Quieres ver cómo funciona?</h1>
        <p>Escribe cualquier correo y cualquier clave. Entras a un recorrido por los casos de uso. No es el programa real y no se crea una empresa.</p>
        <form id="puerta-form">
            <label>Correo
                <input id="correo" type="email" autocomplete="off" required placeholder="el-que-quieras@correo.com">
            </label>
            <label>Clave
                <input id="clave" type="password" autocomplete="off" required placeholder="la que sea">
            </label>
            <button class="btn" type="submit">Entrar a la visita</button>
        </form>
        @auth
            <p class="direccion-tenant"><span>Envía solo esta dirección</span>{{ $direccionGuia }}</p>
        @endauth
    </section>

    <section id="programa" hidden>
        <div class="app visita-app">
            <aside class="side">
                <a class="marca" href="{{ route('guia') }}">
                    <img class="marca-logo" src="{{ asset('image/cobras.png') }}" alt="Cobras">
                </a>
                <nav id="menu">
                    <button type="button" data-ir="hoy" data-menu="hoy">Hoy</button>
                    <p class="cobro-menu" id="cobro-activo" hidden></p>
                    <button type="button" data-ir="cuadre" data-menu="cuadre" hidden>Cuadre</button>
                    <button type="button" data-ir="clientes" data-menu="clientes" hidden>Clientes</button>
                    <button type="button" data-ir="prestamo" data-menu="prestamo" hidden>Nuevo préstamo</button>
                </nav>
                <div class="sesion">
                    <p id="sesion-correo">Visita</p>
                    <p>Visita guiada</p>
                    <button class="salir" type="button" id="salir">Salir de la visita</button>
                </div>
            </aside>
            <main>
                <p class="aviso">Estás en una visita. Nada de esto se guarda.</p>
                <div id="pantalla"></div>
                <div class="coach" id="coach">
                    <p class="kicker" id="coach-paso"></p>
                    <p id="coach-texto"></p>
                </div>
            </main>
        </div>
    </section>

    <template id="vista-hoy">
        <header class="encabezado">
            <div>
                <p class="kicker">Jornada</p>
                <h1>Hoy</h1>
                <p>Dos cobros. Todavía no entras a ninguno.</p>
            </div>
        </header>
        <section class="panel">
            <h2>Cobros</h2>
            <div class="entrada-cobro">
                <p>Norte</p>
                <button class="btn" type="button" data-ir="entrar-norte">Entrar</button>
            </div>
            <div class="entrada-cobro">
                <p>Sur</p>
                <button class="btn" type="button" data-ir="entrar-sur">Entrar</button>
            </div>
        </section>
    </template>

    <template id="vista-norte">
        <header class="encabezado">
            <div>
                <p class="kicker">Cobro</p>
                <h1>Norte</h1>
                <p>Solo</p>
            </div>
            <div class="acciones">
                <button class="btn-linea" type="button" data-ir="volver">Volver a los cobros</button>
            </div>
        </header>
        <section class="grid-cifras">
            <article class="cifra"><span>En caja</span><strong>$ 500.000</strong></article>
            <article class="cifra"><span>Cartera en la calle</span><strong>$ 120.000</strong></article>
            <article class="cifra"><span>Para cobrar hoy</span><strong>$ 24.000</strong></article>
        </section>
        <section class="panel">
            <h2>Ana Pérez debe en Norte</h2>
            <p>Saldo de este cobro: <strong>$ 120.000</strong>. El historial que ves es solo de Norte.</p>
        </section>
    </template>

    <template id="vista-clientes-norte">
        <header class="encabezado"><div><p class="kicker">Norte</p><h1>Clientes</h1></div></header>
        <section class="panel">
            <button class="btn" type="button" data-ir="ana">Ana Pérez · $ 120.000</button>
        </section>
    </template>

    <template id="vista-ana">
        <header class="encabezado"><div><p class="kicker">Norte</p><h1>Ana Pérez</h1><p>Préstamo de $ 100.000 al 20 %. Saldo $ 120.000.</p></div></header>
        <section class="panel">
            <p>Cuota de esta semana: $ 24.000.</p>
            <button class="btn" type="button" data-ir="abonar">Anotar abono de $ 24.000</button>
        </section>
    </template>

    <template id="vista-abono">
        <header class="encabezado"><div><p class="kicker">Norte</p><h1>Abono anotado</h1></div></header>
        <section class="panel">
            <p>Saldo que sigue en Norte: <strong>$ 96.000</strong>.</p>
            <p class="mini-mensaje">Hola Ana, registramos tu abono de $ 24.000. Saldo pendiente: $ 96.000.</p>
        </section>
    </template>

    <template id="vista-sur">
        <header class="encabezado">
            <div>
                <p class="kicker">Cobro</p>
                <h1>Sur</h1>
                <p>Otra caja</p>
            </div>
            <div class="acciones">
                <button class="btn-linea" type="button" data-ir="volver">Volver a los cobros</button>
            </div>
        </header>
        <section class="grid-cifras">
            <article class="cifra"><span>En caja</span><strong>$ 200.000</strong></article>
            <article class="cifra"><span>Cartera en la calle</span><strong>$ 0</strong></article>
            <article class="cifra"><span>Para cobrar hoy</span><strong>$ 0</strong></article>
        </section>
        <section class="panel">
            <h2>Ana no trae la deuda de Norte</h2>
            <p>En Sur el saldo de Ana es cero y no se ve ese historial.</p>
        </section>
    </template>

    <template id="vista-clientes-sur">
        <header class="encabezado"><div><p class="kicker">Sur</p><h1>Clientes</h1></div></header>
        <section class="panel">
            <p>Ana Pérez <span class="badge badge-pause">Restringido</span></p>
            <p>Saldo en este cobro: <strong>$ 0</strong>. Aquí todavía no tiene préstamos.</p>
            <button class="btn" type="button" data-ir="ana-sur">Abrir a Ana</button>
        </section>
    </template>

    <template id="vista-restringido">
        <header class="encabezado"><div><p class="kicker">Sur</p><h1>Ana Pérez</h1></div></header>
        <section class="panel">
            <p><span class="badge badge-pause">Restringido</span></p>
            <p>Ya debe en Norte. Un cobrador no puede prestarle en Sur. El administrador aprueba al crear el préstamo nuevo.</p>
            <button class="btn" type="button" data-ir="aprobar">Aprobar, como administrador, un préstamo de $ 10.000</button>
        </section>
    </template>

    <template id="vista-aprobado">
        <header class="encabezado"><div><p class="kicker">Sur</p><h1>Préstamo nuevo</h1></div></header>
        <section class="panel">
            <p>En Sur solo se ve este préstamo: <strong>$ 10.000</strong>.</p>
            <p>Norte sigue con sus $ 96.000. No se mezclan.</p>
        </section>
    </template>

    <template id="vista-cierre">
        <header class="encabezado"><div><p class="kicker">Cuadre</p><h1>Cierre del día</h1></div></header>
        <section class="panel">
            <p>La plata del cobro se cuenta. La ganancia se puede retirar o sumar a la base de mañana.</p>
            <div class="acciones">
                <button class="btn" type="button" data-ir="cerrar">Retirar la ganancia</button>
                <button class="btn" type="button" data-ir="cerrar">Sumar a la base</button>
            </div>
        </section>
    </template>

    <template id="vista-final">
        <header class="encabezado">
            <div>
                <p class="kicker">Fin de la visita</p>
                <h1>Así se usa Cobros</h1>
                <p>Cada cobro es su caja. El cliente que ya debe en otro aparece restringido hasta que el administrador aprueba el préstamo nuevo.</p>
            </div>
        </header>
        <section class="panel" id="alta">
            <h2>Crear mi cobro en versión demo</h2>
            <p class="ayuda">30 días y {{ \App\Models\Empresa::LIMITE_DEMO }} préstamos. Al crearlo quedas en el ingreso de tu empresa, para guardarlo en el celular.</p>
            <form method="post" action="{{ route('registrar.store') }}">
                @csrf
                <input type="hidden" name="origen" value="guia">
                <label>Empresa
                    <input name="nombre" value="{{ old('nombre') }}" required>
                </label>
                <label>Administrador
                    <input name="usuario_nombre" value="{{ old('usuario_nombre') }}" required>
                </label>
                <label>Correo
                    <input type="email" name="usuario_email" value="{{ old('usuario_email') }}" autocomplete="username" required>
                </label>
                <label>Contraseña
                    <input type="password" name="usuario_password" autocomplete="new-password" required>
                </label>
                <label>Confirmar contraseña
                    <input type="password" name="usuario_password_confirmation" autocomplete="new-password" required>
                </label>
                <button class="btn" type="submit">Crear mi cobro en demo</button>
            </form>
        </section>
    </template>
@endsection

@push('scripts')
    <script>
        var pasos = [
            { vista: 'hoy', menu: null, cobro: '', espera: 'entrar-norte', texto: 'Esto es Hoy. Solo ves el nombre de cada cobro, sin cifras. Toca Entrar en Norte.' },
            { vista: 'norte', menu: 'hoy', cobro: 'Norte', espera: 'clientes', texto: 'Ya entraste a Norte. El menú y las cifras son solo de este cobro. Toca Clientes.' },
            { vista: 'clientes-norte', menu: 'clientes', cobro: 'Norte', espera: 'ana', texto: 'Ana debe 120.000 en Norte. Ábrela.' },
            { vista: 'ana', menu: 'clientes', cobro: 'Norte', espera: 'abonar', texto: 'Este préstamo es de Norte. Cuando paga, anotas el abono.' },
            { vista: 'abono', menu: 'clientes', cobro: 'Norte', espera: 'volver', texto: 'El saldo bajó y el mensaje de WhatsApp quedó listo. Vuelve a los cobros.' },
            { vista: 'hoy', menu: null, cobro: '', espera: 'entrar-sur', texto: 'Ahora entra a Sur. Ana no debe aparecer con la deuda de Norte.' },
            { vista: 'sur', menu: 'hoy', cobro: 'Sur', espera: 'clientes', texto: 'Sur tiene su propia caja, en cero para Ana. Toca Clientes.' },
            { vista: 'clientes-sur', menu: 'clientes', cobro: 'Sur', espera: 'ana-sur', texto: 'Ana sale con la marca Restringido y saldo en cero. Ábrela.' },
            { vista: 'restringido', menu: 'prestamo', cobro: 'Sur', espera: 'aprobar', texto: 'Un cobrador no puede prestarle aquí. El administrador sí: al crear el préstamo lo aprueba.' },
            { vista: 'aprobado', menu: 'prestamo', cobro: 'Sur', espera: 'cuadre', texto: 'En Sur solo se ve el préstamo nuevo. Toca Cuadre para cerrar el día.' },
            { vista: 'cierre', menu: 'cuadre', cobro: 'Sur', espera: 'cerrar', texto: 'Al final se cuenta la caja. Elige si retiras la ganancia o la sumas a mañana.' },
            { vista: 'final', menu: null, cobro: '', espera: '', texto: 'Terminó la visita. Si quieres el tuyo de verdad, créalo en demo. Quedarás en el ingreso de tu empresa.' }
        ];

        var indice = 0;
        var correoVisita = '';
        var programa = document.getElementById('programa');
        var puerta = document.getElementById('puerta');
        var pantalla = document.getElementById('pantalla');
        var conErrores = {{ $errors->any() ? 'true' : 'false' }};

        function vista(nombre) {
            var molde = document.getElementById('vista-' + nombre);
            pantalla.replaceChildren(molde.content.cloneNode(true));
            pantalla.querySelectorAll('[data-ir]').forEach(function (boton) {
                boton.addEventListener('click', function () { intentar(boton.getAttribute('data-ir')); });
            });
        }

        function menu(paso) {
            var dentro = paso.cobro !== '';
            document.getElementById('cobro-activo').hidden = !dentro;
            document.getElementById('cobro-activo').textContent = paso.cobro;
            document.querySelectorAll('#menu [data-menu]').forEach(function (boton) {
                var esHoy = boton.getAttribute('data-menu') === 'hoy';
                boton.hidden = esHoy ? false : !dentro;
                boton.classList.toggle('activo', boton.getAttribute('data-menu') === paso.menu || (esHoy && paso.vista === 'hoy'));
            });
        }

        function pintar() {
            var paso = pasos[indice];
            vista(paso.vista);
            menu(paso);
            document.getElementById('coach-paso').textContent = 'Paso ' + (indice + 1) + ' de ' + pasos.length;
            document.getElementById('coach-texto').textContent = paso.texto;
        }

        function intentar(accion) {
            var paso = pasos[indice];
            if (accion !== paso.espera) {
                document.getElementById('coach-texto').textContent = 'Sigue este paso. ' + paso.texto;
                return;
            }
            if (indice < pasos.length - 1) {
                indice += 1;
                pintar();
            }
        }

        function entrarVisita(correo) {
            correoVisita = correo;
            document.getElementById('sesion-correo').textContent = correo;
            puerta.hidden = true;
            programa.hidden = false;
            document.querySelector('.guia-publica').classList.add('visita-activa');
            indice = 0;
            pintar();
        }

        document.getElementById('puerta-form').addEventListener('submit', function (evento) {
            evento.preventDefault();
            entrarVisita(document.getElementById('correo').value.trim());
        });

        document.getElementById('salir').addEventListener('click', function () {
            programa.hidden = true;
            puerta.hidden = false;
            document.querySelector('.guia-publica').classList.remove('visita-activa');
        });

        document.querySelectorAll('#menu [data-ir]').forEach(function (boton) {
            boton.addEventListener('click', function () { intentar(boton.getAttribute('data-ir')); });
        });

        if (conErrores) {
            entrarVisita('visita@correo.com');
            indice = pasos.length - 1;
            pintar();
        }
    </script>
@endpush
