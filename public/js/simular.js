function dinero(valor) {
    return '$ ' + Math.round(valor).toLocaleString('es-CO');
}

function parseLocal(iso) {
    const [anio, mes, dia] = iso.split('-').map(Number);
    return new Date(anio, mes - 1, dia);
}

function iso(fecha) {
    const dos = (n) => String(n).padStart(2, '0');
    return fecha.getFullYear() + '-' + dos(fecha.getMonth() + 1) + '-' + dos(fecha.getDate());
}

function avanzar(fecha, frecuencia) {
    const copia = new Date(fecha.getTime());
    if (frecuencia === 'diario') {
        copia.setDate(copia.getDate() + 1);
        return copia;
    }
    if (frecuencia === 'semanal') {
        copia.setDate(copia.getDate() + 7);
        return copia;
    }
    if (frecuencia === 'quincenal') {
        copia.setDate(copia.getDate() + 15);
        return copia;
    }
    const dia = copia.getDate();
    copia.setDate(1);
    copia.setMonth(copia.getMonth() + 1);
    const ultimo = new Date(copia.getFullYear(), copia.getMonth() + 1, 0).getDate();
    copia.setDate(Math.min(dia, ultimo));
    return copia;
}

function simular() {
    const capital = Math.round(Number(document.getElementById('capital').value) || 0);
    const porcentaje = Number(document.getElementById('porcentaje').value) || 0;
    const cuotas = parseInt(document.getElementById('cuotas').value, 10) || 0;
    const frecuencia = document.getElementById('frecuencia').value;
    const inicio = document.getElementById('fecha_primer_pago').value;
    const caja = document.getElementById('preview');

    if (capital < 1 || cuotas < 1 || !inicio) {
        caja.innerHTML = '<p class="vacio">Completa capital, cuotas y fecha para ver el plan.</p>';
        return;
    }

    const interes = Math.round(capital * porcentaje / 100);
    const total = capital + interes;
    const capitalBase = Math.floor(capital / cuotas);
    const capitalResiduo = capital % cuotas;
    const interesBase = Math.floor(interes / cuotas);
    const interesResiduo = interes % cuotas;
    let fecha = parseLocal(inicio);
    let saldo = total;
    let filas = '';
    let valorPrimera = 0;
    let valorUltima = 0;

    for (let i = 1; i <= cuotas; i++) {
        const ultima = i === cuotas;
        const parteCapital = capitalBase + (ultima ? capitalResiduo : 0);
        const parteInteres = interesBase + (ultima ? interesResiduo : 0);
        const valor = parteCapital + parteInteres;
        saldo -= valor;
        if (i === 1) valorPrimera = valor;
        valorUltima = valor;
        const textoFecha = fecha.toLocaleDateString('es-CO', { day: '2-digit', month: 'short', year: 'numeric' });
        filas += '<tr><td>' + i + '</td><td>' + textoFecha + '</td><td class="num">' + dinero(valor) + '</td><td class="num">' + dinero(saldo) + '</td></tr>';
        fecha = avanzar(fecha, frecuencia);
    }

    const cuotaTxt = valorPrimera === valorUltima
        ? dinero(valorPrimera)
        : dinero(valorPrimera) + ' y última ' + dinero(valorUltima);

    caja.innerHTML = ''
        + '<div class="resumen-preview">'
        + '<div><span class="muted">Interés</span><br><strong>' + dinero(interes) + '</strong></div>'
        + '<div><span class="muted">Total a cobrar</span><br><strong>' + dinero(total) + '</strong></div>'
        + '<div><span class="muted">Cuota</span><br><strong>' + cuotaTxt + '</strong></div>'
        + '</div>'
        + '<div class="preview-tabla"><table><thead><tr><th>#</th><th>Fecha</th><th class="num">Cuota</th><th class="num">Saldo</th></tr></thead><tbody>'
        + filas
        + '</tbody></table></div>';
}

['capital', 'porcentaje', 'cuotas', 'frecuencia', 'fecha_primer_pago'].forEach((id) => {
    document.getElementById(id).addEventListener('input', simular);
});
simular();
