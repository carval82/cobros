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
                    <button type="button" data-ir="ruta" data-menu="ruta" hidden>Ruta</button>
                    <button type="button" data-ir="atrasados" data-menu="atrasados" hidden>Atrasados</button>
                    <button type="button" data-ir="clientes" data-menu="clientes" hidden>Clientes</button>
                    <button type="button" data-ir="prestamo" data-menu="prestamo" hidden>Nuevo préstamo</button>
                    <button type="button" data-ir="rendimiento" data-menu="rendimiento" data-libre>Rendimiento</button>
                    <button type="button" data-ir="vivo" data-menu="vivo" data-libre>En vivo</button>
                    <button type="button" data-ir="app" data-menu="app" data-libre>App del celular</button>
                </nav>
                <div class="sesion">
                    <p id="sesion-correo">Visita</p>
                    <p>Visita guiada</p>
                    <button class="salir reiniciar" type="button">Volver a empezar</button>
                    <button class="salir" type="button" id="salir">Salir de la visita</button>
                </div>
            </aside>
            <main>
                <p class="aviso">Estás en una visita. Nada de esto se guarda.</p>
                <div id="pantalla"></div>
                <div class="coach" id="coach">
                    <p class="kicker" id="coach-paso"></p>
                    <p id="coach-texto"></p>
                    <button class="reiniciar" type="button">Volver a empezar</button>
                </div>
            </main>
        </div>
    </section>

    <template id="vista-hoy">
        <header class="encabezado">
            <div>
                <p class="kicker">Jornada</p>
                <h1>Hoy</h1>
                <p>Elige un cobro. Hasta que entras, no se ven cifras ni cuotas.</p>
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
            <article class="cifra"><span>Cartera en la calle</span><strong>$ 240.000</strong></article>
            <article class="cifra"><span>Para cobrar hoy</span><strong>$ 24.000</strong></article>
            <article class="cifra"><span>Atrasado</span><strong>$ 24.000</strong></article>
            <article class="cifra"><span>Cobrado hoy</span><strong>$ 0</strong></article>
        </section>
        <div class="dos">
            <section class="panel">
                <h2>Cuotas de hoy</h2>
                <div class="tabla-wrap"><table>
                    <thead><tr><th>Cliente</th><th>Cuota</th><th>Fecha</th><th class="num">Saldo</th><th></th></tr></thead>
                    <tbody>
                        <tr>
                            <td><strong>Ana Pérez</strong><div class="muted">300 111 2233</div></td>
                            <td>1 de 5</td>
                            <td>Hoy</td>
                            <td class="num">$ 24.000</td>
                            <td><button class="btn" type="button" data-ir="ana">Abrir</button></td>
                        </tr>
                    </tbody>
                </table></div>
                <h2 style="margin-top:28px">Atrasados</h2>
                <div class="tabla-wrap"><table>
                    <thead><tr><th>Cliente</th><th>Cuota</th><th>Fecha</th><th class="num">Saldo</th></tr></thead>
                    <tbody>
                        <tr>
                            <td><strong>Luis Mora</strong><div class="muted">300 444 5566</div></td>
                            <td>2 de 4</td>
                            <td>Hace 2 días<div class="muted">2 días de atraso</div></td>
                            <td class="num">$ 24.000</td>
                        </tr>
                    </tbody>
                </table></div>
            </section>
            <section class="panel">
                <h2>Historial reciente</h2>
                <ul class="historial">
                    <li><time>Ayer</time><div><strong>Luis Mora</strong><div class="muted">Préstamo de $ 80.000</div></div></li>
                    <li><time>Hace 6 días</time><div><strong>Ana Pérez</strong><div class="muted">Préstamo de $ 100.000 al 20 %</div></div></li>
                </ul>
            </section>
        </div>
    </template>

    <template id="vista-prestamo">
        <header class="encabezado">
            <div>
                <h1>Ana Pérez</h1>
                <p>$ 100.000 al 20 % · 5 cuotas semanales <span class="badge badge-ok">Al día</span></p>
            </div>
        </header>
        <section class="grid-cifras">
            <article class="cifra"><span>Capital</span><strong>$ 100.000</strong></article>
            <article class="cifra"><span>Interés</span><strong>$ 20.000</strong></article>
            <article class="cifra"><span>Total</span><strong>$ 120.000</strong></article>
            <article class="cifra"><span>Saldo</span><strong>$ 120.000</strong></article>
        </section>
        <div class="dos">
            <section class="panel">
                <h2>Fechas de pago</h2>
                <div class="tabla-wrap"><table>
                    <thead><tr><th>#</th><th>Fecha</th><th class="num">Cuota</th><th class="num">Pagado</th><th class="num">Saldo</th><th>Estado</th></tr></thead>
                    <tbody>
                        <tr><td>1</td><td>Hoy</td><td class="num">$ 24.000</td><td class="num">$ 0</td><td class="num">$ 24.000</td><td><span class="badge badge-wait">Pendiente</span></td></tr>
                        <tr><td>2</td><td>En 7 días</td><td class="num">$ 24.000</td><td class="num">$ 0</td><td class="num">$ 24.000</td><td><span class="badge badge-wait">Pendiente</span></td></tr>
                        <tr><td>3</td><td>En 14 días</td><td class="num">$ 24.000</td><td class="num">$ 0</td><td class="num">$ 24.000</td><td><span class="badge badge-wait">Pendiente</span></td></tr>
                        <tr><td>4</td><td>En 21 días</td><td class="num">$ 24.000</td><td class="num">$ 0</td><td class="num">$ 24.000</td><td><span class="badge badge-wait">Pendiente</span></td></tr>
                        <tr><td>5</td><td>En 28 días</td><td class="num">$ 24.000</td><td class="num">$ 0</td><td class="num">$ 24.000</td><td><span class="badge badge-wait">Pendiente</span></td></tr>
                    </tbody>
                </table></div>
            </section>
            <section class="panel">
                <h2>Registrar abono</h2>
                <label>Valor<input value="24000" readonly></label>
                <label>Fecha del abono<input value="Hoy" readonly></label>
                <button class="btn" type="button" data-ir="abonar">Registrar abono</button>
            </section>
        </div>
    </template>

    <template id="vista-pagado">
        <p class="aviso aviso-ok">Abono registrado. El saldo ya se puede mandar al cliente.</p>
        <p class="aviso aviso-ok">Hola Ana, registramos tu abono de $ 24.000. Saldo pendiente: $ 96.000. Próxima cuota en 7 días por $ 24.000. <button class="btn" type="button" data-ir="whatsapp">Enviar por WhatsApp</button></p>
        <header class="encabezado">
            <div>
                <h1>Ana Pérez</h1>
                <p>$ 100.000 al 20 % · 5 cuotas semanales <span class="badge badge-ok">Al día</span></p>
            </div>
            <div class="acciones">
                <button class="btn" type="button" data-ir="atrasados">Ver atrasados</button>
            </div>
        </header>
        <section class="grid-cifras">
            <article class="cifra"><span>Capital</span><strong>$ 100.000</strong></article>
            <article class="cifra"><span>Interés</span><strong>$ 20.000</strong></article>
            <article class="cifra"><span>Total</span><strong>$ 120.000</strong></article>
            <article class="cifra"><span>Saldo</span><strong>$ 96.000</strong></article>
        </section>
        <section class="panel">
            <h2>Fechas de pago</h2>
            <div class="tabla-wrap"><table>
                <thead><tr><th>#</th><th>Fecha</th><th class="num">Cuota</th><th class="num">Pagado</th><th class="num">Saldo</th><th>Estado</th></tr></thead>
                <tbody>
                    <tr><td>1</td><td>Hoy</td><td class="num">$ 24.000</td><td class="num">$ 24.000</td><td class="num">$ 0</td><td><span class="badge badge-done">Pagada</span></td></tr>
                    <tr><td>2</td><td>En 7 días</td><td class="num">$ 24.000</td><td class="num">$ 0</td><td class="num">$ 24.000</td><td><span class="badge badge-wait">Pendiente</span></td></tr>
                    <tr><td>3</td><td>En 14 días</td><td class="num">$ 24.000</td><td class="num">$ 0</td><td class="num">$ 24.000</td><td><span class="badge badge-wait">Pendiente</span></td></tr>
                    <tr><td>4</td><td>En 21 días</td><td class="num">$ 24.000</td><td class="num">$ 0</td><td class="num">$ 24.000</td><td><span class="badge badge-wait">Pendiente</span></td></tr>
                    <tr><td>5</td><td>En 28 días</td><td class="num">$ 24.000</td><td class="num">$ 0</td><td class="num">$ 24.000</td><td><span class="badge badge-wait">Pendiente</span></td></tr>
                </tbody>
            </table></div>
        </section>
    </template>

    <template id="vista-atrasados">
        <header class="encabezado">
            <div>
                <h1>Atrasados</h1>
                <p>1 cuota vencida · $ 24.000 por recoger</p>
            </div>
        </header>
        <section class="panel">
            <div class="tabla-wrap"><table>
                <thead><tr><th>Cliente</th><th>Cuota</th><th>Fecha</th><th class="num">Saldo</th><th></th></tr></thead>
                <tbody>
                    <tr>
                        <td><strong>Luis Mora</strong><div class="muted">300 444 5566</div></td>
                        <td>2 de 4</td>
                        <td>Hace 2 días<div class="muted">2 días de atraso</div></td>
                        <td class="num">$ 24.000</td>
                        <td><button class="btn" type="button" data-ir="ruta">Ver en la ruta</button></td>
                    </tr>
                </tbody>
            </table></div>
            <p class="ayuda">Ana ya no está aquí: su cuota de hoy quedó pagada.</p>
        </section>
    </template>

    <template id="vista-ruta">
        <header class="encabezado">
            <div>
                <h1>Ruta de cobro</h1>
                <p>Paradas de hoy y atrasadas, ordenadas por cercanía. El punto del cliente se guarda al registrar el abono en el celular.</p>
            </div>
            <div class="acciones">
                <button class="btn" type="button" data-ir="ruta">Ordenar desde aquí</button>
                <button class="btn-linea" type="button" data-ir="ruta">Abrir en Google Maps</button>
            </div>
        </header>
        <section class="grid-cifras">
            <article class="cifra"><span>Paradas</span><strong>2</strong></article>
            <article class="cifra"><span>En el mapa</span><strong>2</strong></article>
            <article class="cifra"><span>Sin GPS</span><strong>0</strong></article>
            <article class="cifra"><span>Por cobrar</span><strong>$ 24.000</strong></article>
        </section>
        <div class="mapa mapa-visita" aria-hidden="true">
            <span class="pin pin-tu" style="left:24%;top:72%">Tú · Pedro</span>
            <span class="pin" style="left:46%;top:42%">1 · Luis · 400 m</span>
            <span class="pin" style="left:70%;top:58%">2 · Ana · 1,2 km</span>
        </div>
        <section class="panel">
            <article class="parada">
                <span class="parada-orden">1</span>
                <div>
                    <strong>Luis Mora</strong>
                    <div class="muted">Calle 12 · 400 m · 2 días de atraso · $ 24.000</div>
                </div>
                <button class="btn-linea" type="button" data-ir="ruta">Ir</button>
            </article>
            <article class="parada">
                <span class="parada-orden">2</span>
                <div>
                    <strong>Ana Pérez</strong>
                    <div class="muted">Carrera 8 · 1,2 km · el abono de hoy dejó su punto</div>
                </div>
                <button class="btn-linea" type="button" data-ir="ruta">Ir</button>
            </article>
            <p class="ayuda">Quien no tiene GPS queda al final y se marca “sin GPS”. Ordenar desde aquí usa el lugar del teléfono, no una dirección escrita.</p>
            <p style="margin-top:16px"><button class="btn" type="button" data-ir="cuadre">Ir al cuadre</button></p>
        </section>
    </template>

    <template id="vista-cierre">
        <header class="encabezado">
            <div>
                <p class="kicker">Norte</p>
                <h1>Cuadre del día</h1>
                <p>La cuenta es solo de este cobro.</p>
            </div>
        </header>
        <section class="grid-cifras">
            <article class="cifra"><span>Base</span><strong>$ 500.000</strong></article>
            <article class="cifra"><span>Cobrado hoy</span><strong>$ 24.000</strong></article>
            <article class="cifra"><span>Gastos</span><strong>$ 0</strong></article>
            <article class="cifra"><span>Ganancia</span><strong>$ 4.000</strong></article>
            <article class="cifra"><span>En caja</span><strong>$ 524.000</strong></article>
        </section>
        <section class="panel">
            <h2>Cerrar el día</h2>
            <p>De los $ 24.000 cobrados, $ 4.000 son ganancia. Puedes retirarla o dejarla en la base de mañana.</p>
            <div class="acciones">
                <button class="btn" type="button" data-ir="cerrar">Retirar la ganancia</button>
                <button class="btn" type="button" data-ir="cerrar">Sumar a la base</button>
            </div>
        </section>
    </template>

    <template id="vista-sur">
        <header class="encabezado">
            <div>
                <p class="kicker">Cobro</p>
                <h1>Sur</h1>
                <p>Otra caja. Lo de Norte no entra aquí.</p>
            </div>
            <div class="acciones">
                <button class="btn-linea" type="button" data-ir="volver">Volver a los cobros</button>
            </div>
        </header>
        <section class="grid-cifras">
            <article class="cifra"><span>En caja</span><strong>$ 200.000</strong></article>
            <article class="cifra"><span>Cartera en la calle</span><strong>$ 0</strong></article>
            <article class="cifra"><span>Para cobrar hoy</span><strong>$ 0</strong></article>
            <article class="cifra"><span>Atrasado</span><strong>$ 0</strong></article>
            <article class="cifra"><span>Cobrado hoy</span><strong>$ 0</strong></article>
        </section>
        <section class="panel">
            <h2>Cuotas de hoy</h2>
            <p class="vacio">Hoy no hay cuotas por cobrar.</p>
            <p class="ayuda">Ana pagó en Norte. Ese abono y esos $ 96.000 no aparecen en Sur.</p>
            <button class="btn" type="button" data-ir="clientes">Ver clientes</button>
        </section>
    </template>

    <template id="vista-clientes-sur">
        <header class="encabezado">
            <div>
                <h1>Clientes</h1>
                <p>En este cobro el cliente aparece sin el saldo ni el historial de otro.</p>
            </div>
        </header>
        <section class="panel">
            <div class="tabla-wrap"><table>
                <thead><tr><th>Cliente</th><th>Teléfono</th><th>Préstamos</th><th class="num">Saldo</th></tr></thead>
                <tbody>
                    <tr>
                        <td>
                            <button class="btn-linea" type="button" data-ir="ana-sur"><strong>Ana Pérez</strong></button>
                            <span class="badge badge-pause">Restringido</span>
                            <div class="muted">123</div>
                        </td>
                        <td>300 111 2233</td>
                        <td>0</td>
                        <td class="num">$ 0</td>
                    </tr>
                    <tr>
                        <td><strong>Beto Gómez</strong><div class="muted">456</div></td>
                        <td>300 777 8899</td>
                        <td>0</td>
                        <td class="num">$ 0</td>
                    </tr>
                </tbody>
            </table></div>
        </section>
    </template>

    <template id="vista-restringido">
        <header class="encabezado">
            <div>
                <h1>Ana Pérez</h1>
                <p>123 · 300 111 2233 · Carrera 8</p>
            </div>
            <div class="acciones">
                <span class="badge badge-pause">Restringido</span>
            </div>
        </header>
        <div class="dos">
            <section class="panel">
                <h2>Préstamos</h2>
                <p class="ayuda">Aquí solo se ve lo de este cobro. Otro cobro no trae su saldo ni su historial.</p>
                <p class="ayuda">Este cliente ya tiene un préstamo en otro cobro. Queda restringido: el préstamo nuevo lo aprueba el administrador.</p>
                <p class="vacio">Este cliente todavía no tiene préstamos.</p>
                <button class="btn" type="button" data-ir="aprobar">Aprobar como administrador</button>
            </section>
            <section class="panel">
                <h2>Historial</h2>
                <p class="vacio">Sin movimientos.</p>
            </section>
        </div>
    </template>

    <template id="vista-prestamo-nuevo">
        <header class="encabezado">
            <div>
                <p class="kicker">Sur · administrador</p>
                <h1>Nuevo préstamo</h1>
                <p>Ana Pérez · restringido. Crearlo aquí es la aprobación.</p>
            </div>
        </header>
        <section class="panel">
            <label>Capital<input value="10000" readonly></label>
            <label>Porcentaje<input value="20" readonly></label>
            <label>Cuotas<input value="1" readonly></label>
            <label>Frecuencia<input value="Diario" readonly></label>
            <p class="ayuda">10.000 al 20 % quedan en 12.000. En Sur solo se verá este préstamo.</p>
            <button class="btn" type="button" data-ir="crear">Crear préstamo</button>
        </section>
    </template>

    <template id="vista-aprobado">
        <header class="encabezado">
            <div>
                <h1>Ana Pérez</h1>
                <p>Sur · el préstamo de Norte no está en esta ficha</p>
            </div>
        </header>
        <div class="dos">
            <section class="panel">
                <h2>Préstamos</h2>
                <div class="tabla-wrap"><table>
                    <thead><tr><th>Préstamo</th><th>Estado</th><th class="num">Saldo</th></tr></thead>
                    <tbody>
                        <tr>
                            <td>$ 10.000 al 20 %<div class="muted">1 cuota diaria</div></td>
                            <td><span class="badge badge-ok">Al día</span></td>
                            <td class="num">$ 12.000</td>
                        </tr>
                    </tbody>
                </table></div>
            </section>
            <section class="panel">
                <h2>Historial</h2>
                <ul class="historial">
                    <li><time>Hoy</time><div>Préstamo de $ 10.000 al 20 %</div></li>
                </ul>
                <p class="ayuda">Los $ 96.000 de Norte siguen en Norte.</p>
            </section>
        </div>
        <p><button class="btn" type="button" data-ir="app">Ver la app del celular</button></p>
    </template>

    <template id="vista-app">
        <header class="encabezado">
            <div>
                <p class="kicker">En el teléfono</p>
                <h1>La misma caja, en la calle</h1>
                <p>El cobrador abre Cobras con la dirección de la empresa. No crea otra. El teléfono recuerda el servidor, esa dirección y el correo.</p>
            </div>
        </header>
        <div class="dos">
            <section class="panel telefono-visita">
                <p class="kicker">Cobras · Norte</p>
                <h2>Hoy</h2>
                <p>En caja $ 524.000 · por cobrar hoy $ 24.000</p>
                <p class="muted">Ana Pérez · cuota pagada · Luis Mora · atrasado</p>
                <div class="pestanas"><span>Hoy</span><span>Clientes</span><span>Ruta</span><span>Más</span></div>
            </section>
            <section class="panel">
                <h2>Para qué sirve llevarla</h2>
                <ul class="historial">
                    <li><time>Cobro</time><div>Registra el abono ahí mismo. El mensaje de WhatsApp queda listo, con el saldo nuevo.</div></li>
                    <li><time>Cliente</time><div>Ese abono guarda la ubicación del cliente. La próxima ruta lo pone en el mapa y calcula la distancia.</div></li>
                    <li><time>Ruta</time><div>Ordena las paradas desde donde está el cobrador y abre el camino en Google Maps.</div></li>
                    <li><time>Equipo</time><div>Mientras la app sigue abierta, el administrador ve al cobrador en En vivo. En Más está el cuadre, el reporte y los cobradores en vivo.</div></li>
                </ul>
                <p><button class="btn" type="button" data-ir="vivo">Ver cobradores en vivo</button></p>
            </section>
        </div>
    </template>

    <template id="vista-vivo">
        <header class="encabezado">
            <div>
                <p class="kicker">Equipo</p>
                <h1>En vivo</h1>
                <p>Dónde está cada cobrador mientras tiene Cobras abierto. Si cierra la app, queda la última posición.</p>
            </div>
        </header>
        <div class="mapa mapa-visita" aria-hidden="true">
            <span class="pin pin-tu" style="left:48%;top:54%">Pedro Ríos · Norte</span>
        </div>
        <section class="panel">
            <h2>Cobradores</h2>
            <div class="tabla-wrap"><table>
                <thead><tr><th>Nombre</th><th>Estado</th><th>Última señal</th></tr></thead>
                <tbody>
                    <tr>
                        <td><strong>Pedro Ríos</strong><div class="muted">Cobrador · Norte</div></td>
                        <td><span class="badge badge-ok">En ruta</span></td>
                        <td>Hace un momento</td>
                    </tr>
                </tbody>
            </table></div>
            <p class="ayuda">El celular manda el punto solo, más o menos cada 25 segundos. Pedro no escribe nada. En la app el administrador lo abre en Más, en Cobradores en vivo.</p>
            <p><button class="btn" type="button" data-ir="rendimiento">Ver el reporte</button></p>
        </section>
    </template>

    <template id="vista-rendimiento">
        <header class="encabezado">
            <div>
                <p class="kicker">Equipo</p>
                <h1>Rendimiento</h1>
                <p>Lo que cobró cada persona y el porcentaje que le corresponde.</p>
            </div>
        </header>
        <form class="filtros filtros-fechas">
            <label>Desde<input value="1 nov 2026" readonly></label>
            <label>Hasta<input value="Hoy" readonly></label>
            <button class="btn" type="button" data-ir="rendimiento">Ver periodo</button>
        </form>
        <section class="grid-cifras">
            <article class="cifra"><span>Cobrado</span><strong>$ 24.000</strong></article>
            <article class="cifra"><span>A pagar en comisión</span><strong>$ 2.400</strong></article>
            <article class="cifra"><span>Abonos</span><strong>1</strong></article>
        </section>
        <section class="panel">
            <h2>Cobradores</h2>
            <div class="tabla-wrap"><table>
                <thead><tr><th>Cobrador</th><th>% vigente</th><th>Abonos</th><th>Cobrado</th><th>Le corresponde</th></tr></thead>
                <tbody>
                    <tr>
                        <td>Pedro Ríos</td>
                        <td>10%</td>
                        <td>1</td>
                        <td class="num">$ 24.000</td>
                        <td class="num">$ 2.400</td>
                    </tr>
                </tbody>
            </table></div>
            <p class="ayuda">El porcentaje se define en Usuarios. Uno nuevo queda en 10 %. Cada abono guarda el porcentaje de ese momento: si mañana lo subes, el de Ana no cambia.</p>
        </section>
        <section class="panel">
            <h2>Abonos del periodo</h2>
            <div class="tabla-wrap"><table>
                <thead><tr><th>Fecha</th><th>Cliente</th><th>Cobrador</th><th>Abono</th><th>%</th><th>Comisión</th></tr></thead>
                <tbody>
                    <tr>
                        <td>Hoy</td>
                        <td>Ana Pérez</td>
                        <td>Pedro Ríos</td>
                        <td class="num">$ 24.000</td>
                        <td>10%</td>
                        <td class="num">$ 2.400</td>
                    </tr>
                </tbody>
            </table></div>
            <p class="ayuda">Pedro ve este mismo corte en la app, en Mi rendimiento, pero solo lo suyo. Consulta puede mirar la cartera y no registra abonos.</p>
            <p><button class="btn" type="button" data-ir="fin">Terminar la visita</button></p>
        </section>
    </template>

    <template id="vista-final">
        <header class="encabezado">
            <div>
                <p class="kicker">Fin de la visita</p>
                <h1>Así se usa Cobros</h1>
                <p>Cada cobro es su caja. El celular deja al cliente en el mapa, muestra dónde va el cobrador y liquida su porcentaje.</p>
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
            { vista: 'hoy', menu: 'hoy', cobro: '', espera: 'entrar-norte', texto: 'Hoy solo muestra el nombre del cobro. Toca Entrar en Norte.' },
            { vista: 'norte', menu: 'hoy', cobro: 'Norte', espera: 'ana', texto: 'Ya estás dentro. Ves la caja, la cuota de hoy de Ana y el atraso de Luis. Abre la cuota de Ana.' },
            { vista: 'prestamo', menu: 'clientes', cobro: 'Norte', espera: 'abonar', texto: 'El préstamo trae el plan de cuotas. Registra el abono de 24.000, como en el programa.' },
            { vista: 'pagado', menu: 'clientes', cobro: 'Norte', espera: 'atrasados', texto: 'El saldo bajó a 96.000, la primera cuota quedó pagada y el WhatsApp ya está listo. Sigue a Atrasados.' },
            { vista: 'atrasados', menu: 'atrasados', cobro: 'Norte', espera: 'ruta', texto: 'Ana ya no sale vencida. Luis sigue con 2 días de atraso. Mira la ruta.' },
            { vista: 'ruta', menu: 'ruta', cobro: 'Norte', espera: 'cuadre', texto: 'Luis queda primero: debe y está a 400 m. El abono de Ana guardó su punto. Quien no tiene GPS sale al final. Sigue al cuadre.' },
            { vista: 'cierre', menu: 'cuadre', cobro: 'Norte', espera: 'cerrar', texto: 'De lo cobrado, 4.000 son ganancia. Retírala o súmala a la base de mañana.' },
            { vista: 'hoy', menu: 'hoy', cobro: '', espera: 'entrar-sur', texto: 'Norte ya cuadró. Entra a Sur: es otra caja y Ana no debe traer ese saldo.' },
            { vista: 'sur', menu: 'hoy', cobro: 'Sur', espera: 'clientes', texto: 'Sur amanece en cero para Ana. El abono de Norte no está aquí. Abre Clientes.' },
            { vista: 'clientes-sur', menu: 'clientes', cobro: 'Sur', espera: 'ana-sur', texto: 'Ana aparece restringida y con saldo 0. Beto, que no debe en otro cobro, no lleva esa marca. Abre a Ana.' },
            { vista: 'restringido', menu: 'clientes', cobro: 'Sur', espera: 'aprobar', texto: 'No hay préstamos ni historial de Norte. Un cobrador no puede prestarle. El administrador sí aprueba.' },
            { vista: 'prestamo-nuevo', menu: 'prestamo', cobro: 'Sur', espera: 'crear', texto: 'El administrador usa el mismo formulario de préstamo. Crearlo es la aprobación.' },
            { vista: 'aprobado', menu: 'prestamo', cobro: 'Sur', espera: 'app', texto: 'En Sur solo quedó el préstamo de 10.000. Los 96.000 siguen en Norte. Ahora mira qué hace el celular en la calle.' },
            { vista: 'app', menu: 'app', cobro: '', espera: 'vivo', texto: 'La app es la misma caja: abono, WhatsApp, ruta y cuadre. Además comparte dónde va el cobrador y deja al cliente en el mapa. Ábrelo en En vivo.' },
            { vista: 'vivo', menu: 'vivo', cobro: '', espera: 'rendimiento', texto: 'Pedro tiene Cobras abierto y el punto llega solo, cerca de cada 25 segundos. Si cierra la app, esta es la última señal. Sigue al reporte.' },
            { vista: 'rendimiento', menu: 'rendimiento', cobro: '', espera: 'fin', texto: 'De los 24.000 de Ana, a Pedro le corresponden 2.400: el 10 % de ese abono. El cobrador ve lo suyo en Mi rendimiento.' },
            { vista: 'final', menu: 'hoy', cobro: '', espera: '', texto: 'Así se trabaja un día: cobrar, ver atrasos, armar la ruta con el mapa, cuadrar, seguir al cobrador y liquidar su porcentaje.' }
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
                var libre = boton.hasAttribute('data-libre');
                boton.hidden = (esHoy || libre) ? false : !dentro;
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

        function destino(accion) {
            if (accion === 'hoy' || accion === 'volver') {
                return indice >= 7 ? 7 : 0;
            }
            var mapa = {
                'entrar-norte': 1,
                'ana': 2,
                'abonar': 3,
                'atrasados': 4,
                'ruta': 5,
                'cuadre': 6,
                'cerrar': 7,
                'entrar-sur': 8,
                'clientes': 9,
                'ana-sur': 10,
                'aprobar': 11,
                'prestamo': 11,
                'crear': 12,
                'app': 13,
                'vivo': 14,
                'rendimiento': 15,
                'fin': 16
            };
            return Object.prototype.hasOwnProperty.call(mapa, accion) ? mapa[accion] : -1;
        }

        function intentar(accion) {
            if (accion === 'whatsapp') {
                document.getElementById('coach-texto').textContent = 'En la visita ese WhatsApp no se envía. Elige otro botón o vuelve a empezar.';
                return;
            }
            var ir = destino(accion);
            if (ir < 0) {
                return;
            }
            var siguio = accion === pasos[indice].espera;
            var mismo = ir === indice;
            indice = ir;
            pintar();
            if (!siguio && !mismo) {
                document.getElementById('coach-texto').textContent = pasos[indice].texto + ' Puedes seguir desde aquí o volver a empezar.';
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

        document.querySelectorAll('.reiniciar').forEach(function (boton) {
            boton.addEventListener('click', function () {
                indice = 0;
                pintar();
            });
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
