<div class="d-flex flex-wrap gap-2 justify-content-between align-items-start mb-3">
  <div>
    <h1 class="page-title" id="hObra"></h1>
    <div class="page-subtitle" id="hSub"></div>
  </div>
  <div class="d-flex flex-wrap gap-2">
    <a class="btn btn-outline-secondary" id="lnkCotizacion"><i class="bi bi-arrow-left"></i> Cotización</a>
    <button class="btn btn-outline-success d-none" id="btnCobro"><i class="bi bi-cash-coin"></i> Registrar cobro</button>
    <a class="btn btn-primary" target="_blank" href="estimaciones/<?= (int) $estimacionId ?>/imprimir"><i class="bi bi-printer"></i> Imprimir</a>
    <button class="btn btn-outline-danger d-none" id="btnEliminar" title="Eliminar estimación"><i class="bi bi-trash"></i></button>
  </div>
</div>

<div class="row g-3 mb-3">
  <div class="col-lg-8">
    <form id="formEst" class="card p-3 h-100">
      <div class="row g-2">
        <div class="col-md-3"><label class="form-label">Fecha *</label><input type="date" class="form-control" name="fecha" required></div>
        <div class="col-md-3"><label class="form-label">Periodo del</label><input type="date" class="form-control" name="periodo_inicio"></div>
        <div class="col-md-3"><label class="form-label">al</label><input type="date" class="form-control" name="periodo_fin"></div>
        <div class="col-md-3"><label class="form-label">Estado</label>
          <select class="form-select" name="estado"><option>borrador</option><option>presentada</option><option>autorizada</option><option>pagada</option></select></div>
        <div class="col-12"><label class="form-label">Notas</label><input class="form-control" name="notas"></div>
      </div>
      <div class="mt-2 d-flex justify-content-between align-items-center">
        <span class="small text-muted" id="avisoEstado"></span>
        <div><span id="guardado" class="text-success me-2 d-none"><i class="bi bi-check-circle"></i> Guardado</span><button class="btn btn-sm btn-primary">Guardar</button></div>
      </div>
    </form>
  </div>
  <div class="col-lg-4">
    <div class="card p-3 h-100 bg-light"><dl class="row desglose mb-0" id="resumen"></dl></div>
  </div>
</div>

<div class="card">
  <div class="table-responsive">
  <table class="table table-sm table-cotizacion mb-0 align-middle">
    <thead><tr>
      <th>Clave</th><th>Concepto</th><th>U.</th><th class="num">P.U.</th><th class="num">Contratado</th><th class="num">Anterior</th>
      <th class="num" style="width:130px">Esta estimación</th><th class="num">Acumulado</th><th class="num">Por ejecutar</th><th class="num">Importe</th>
    </tr></thead>
    <tbody id="tabla"></tbody>
  </table>
  </div>
</div>
<p class="text-muted small mt-2">Solo una estimación en <strong>borrador</strong> admite cambios de cantidades. Si el acumulado rebasa lo contratado se marca en rojo (volumen excedente).</p>

<div class="modal fade" id="modalCobro" tabindex="-1">
  <div class="modal-dialog modal-sm">
    <div class="modal-content">
      <form id="formCobro">
        <div class="modal-header"><h5 class="modal-title">Registrar cobro</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <p class="small text-muted" id="cobroMonto"></p>
          <div class="mb-2"><label class="form-label">Fecha de cobro</label><input type="date" class="form-control" name="fecha" required></div>
          <div class="mb-2"><label class="form-label">Forma de pago</label><select class="form-select" name="forma_pago"><option>Transferencia</option><option>Cheque</option><option>Efectivo</option></select></div>
          <div class="mb-2"><label class="form-label">Referencia</label><input class="form-control" name="referencia"></div>
        </div>
        <div class="modal-footer"><button class="btn btn-success">Registrar</button></div>
      </form>
    </div>
  </div>
</div>

<script>
const EST_ID = <?= (int) $estimacionId ?>;
let est = null;
let modalCobro;

document.addEventListener('DOMContentLoaded', async () => {
    modalCobro = new bootstrap.Modal(document.getElementById('modalCobro'));
    document.getElementById('formEst').addEventListener('submit', guardar);
    document.getElementById('formCobro').addEventListener('submit', registrarCobro);
    document.getElementById('btnCobro').addEventListener('click', () => {
        document.querySelector('#formCobro [name=fecha]').value = hoyISO();
        document.getElementById('cobroMonto').textContent = `Se registrará un ingreso por ${money(est.neto)} en el control de la obra y la estimación pasará a “pagada”.`;
        modalCobro.show();
    });
    document.getElementById('btnEliminar').addEventListener('click', eliminar);
    await cargar();
    fillForm(document.getElementById('formEst'), est);
});

async function cargar() {
    est = await api('api/estimaciones/' + EST_ID);
    const c = est.cotizacion;
    document.getElementById('hObra').textContent = `Estimación ${est.numero} · ${c.obra}`;
    document.getElementById('hSub').innerHTML = `${esc(c.folio)} · ${esc(c.cliente_nombre || 'Sin cliente')} · <span class="badge estado-${esc(est.estado)}">${esc(est.estado)}</span>`;
    document.getElementById('lnkCotizacion').href = 'cotizaciones/' + c.id;
    const borrador = est.estado === 'borrador';
    document.getElementById('btnCobro').classList.toggle('d-none', borrador || est.cobro_registrado);
    document.getElementById('btnEliminar').classList.toggle('d-none', !borrador);
    document.getElementById('avisoEstado').textContent = est.cobro_registrado ? 'Cobro registrado en el control de obra.' : '';

    const fila = (k, v, cls = '') => `<dt class="col-7 ${cls}">${k}</dt><dd class="col-5 ${cls}">${v}</dd>`;
    document.getElementById('resumen').innerHTML =
        fila('Importe de la estimación', money(est.importe), 'fw-semibold text-dark') +
        fila(`Amortización anticipo (${pct(c.pct_anticipo)})`, '−' + money(est.amortizacion)) +
        fila('Subtotal', money(est.subtotal)) +
        fila(`IVA (${pct(c.pct_iva)})`, money(est.iva)) +
        (Number(est.retencion) ? fila(`Fondo de garantía (${pct(c.pct_retencion)})`, '−' + money(est.retencion)) : '') +
        fila('Neto a pagar', money(est.neto), 'fs-5 fw-bold text-dark border-top pt-2') +
        fila('Estimado acumulado', money(est.anteriores.importe + Number(est.importe)), 'small') +
        fila('Contrato (sin IVA)', money(est.contrato), 'small');

    const tb = document.getElementById('tabla');
    tb.innerHTML = '';
    est.partidas.forEach((p) => {
        tb.insertAdjacentHTML('beforeend', `<tr class="partida"><td>${esc(p.clave)}</td><td colspan="9">${esc(p.nombre)}</td></tr>`);
        p.conceptos.forEach((k) => {
            const tr = document.createElement('tr');
            const rojo = k.excedente ? ' text-danger fw-semibold' : '';
            tr.innerHTML = `<td>${esc(k.clave)}</td><td class="desc small">${esc(k.descripcion)}</td><td>${esc(k.unidad)}</td>
                <td class="num">${money(k.pu)}</td><td class="num">${numero(k.contratado)}</td><td class="num">${numero(k.anterior)}</td>
                <td class="num"><input type="number" step="any" min="0" class="form-control form-control-sm input-num ms-auto" value="${k.esta || ''}" ${borrador ? '' : 'disabled'}></td>
                <td class="num${rojo}">${numero(k.acumulado)}</td><td class="num${rojo}">${numero(k.por_ejecutar)}</td><td class="num">${money(k.importe)}</td>`;
            tr.querySelector('input').addEventListener('change', (e) => capturar(k.id, e.target.value));
            tb.appendChild(tr);
        });
    });
}

async function capturar(conceptoId, cantidad) {
    try {
        await api(`api/estimaciones/${EST_ID}/cantidad`, 'PUT', { concepto_id: conceptoId, cantidad });
    } catch (err) {
        alert(err.message);
    }
    await cargar();
}

async function guardar(e) {
    e.preventDefault();
    try {
        await api('api/estimaciones/' + EST_ID, 'PUT', formData(e.target));
        await cargar();
        document.getElementById('guardado').classList.remove('d-none');
        setTimeout(() => document.getElementById('guardado').classList.add('d-none'), 2500);
    } catch (err) {
        alert(err.message);
    }
}

async function registrarCobro(e) {
    e.preventDefault();
    try {
        await api(`api/estimaciones/${EST_ID}/cobro`, 'POST', formData(e.target));
        modalCobro.hide();
        await cargar();
        fillForm(document.getElementById('formEst'), est);
    } catch (err) {
        alert(err.message);
    }
}

async function eliminar() {
    if (!confirm(`¿Eliminar la estimación ${est.numero}?`)) return;
    try {
        await api('api/estimaciones/' + EST_ID, 'DELETE');
        window.location.href = 'cotizaciones/' + est.cotizacion.id;
    } catch (err) {
        alert(err.message);
    }
}
</script>
