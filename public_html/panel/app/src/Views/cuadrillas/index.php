<div class="d-flex justify-content-between align-items-center mb-3">
  <p class="text-muted mb-0 small">El costo por jornada incluye nómina, aguinaldo, prima vacacional, impuesto sobre nómina y cuotas patronales (tasas en Configuración).</p>
  <button class="btn btn-primary" onclick="nueva()"><i class="bi bi-plus-lg"></i> Nueva cuadrilla</button>
</div>

<div class="row g-3" id="lista"><div class="text-muted">Cargando…</div></div>

<div class="modal fade" id="modalCuadrilla" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form id="formCuadrilla">
        <div class="modal-header">
          <h5 class="modal-title">Cuadrilla</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="id">
          <div class="row g-2 mb-3">
            <div class="col-3"><label class="form-label">Clave *</label><input class="form-control" name="clave" required placeholder="CUAD-3"></div>
            <div class="col-9"><label class="form-label">Nombre *</label><input class="form-control" name="nombre" required></div>
          </div>
          <div class="table-responsive">
          <table class="table table-sm align-middle">
            <thead><tr><th>Puesto</th><th style="width:110px">Cantidad</th><th style="width:170px">Salario semanal</th><th style="width:40px"></th></tr></thead>
            <tbody id="integrantes"></tbody>
          </table>
          </div>
          <button type="button" class="btn btn-sm btn-outline-primary" onclick="agregarIntegrante()"><i class="bi bi-plus"></i> Integrante</button>
        </div>
        <div class="modal-footer justify-content-between">
          <button type="button" class="btn btn-outline-danger" id="btnEliminar">Eliminar</button>
          <button type="submit" class="btn btn-primary">Guardar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
let modal;

document.addEventListener('DOMContentLoaded', () => {
    modal = new bootstrap.Modal(document.getElementById('modalCuadrilla'));
    document.getElementById('formCuadrilla').addEventListener('submit', guardar);
    document.getElementById('btnEliminar').addEventListener('click', eliminar);
    cargar();
});

async function cargar() {
    const data = await api('api/cuadrillas');
    const cont = document.getElementById('lista');
    cont.innerHTML = data.length ? '' : '<div class="text-muted">No hay cuadrillas</div>';
    data.forEach((c) => {
        const k = c.costo;
        const filas = c.integrantes.map((i) => `<tr><td>${esc(i.puesto)}</td><td class="num">${numero(i.cantidad, 0)}</td><td class="num">${money(i.salario_semanal)}</td></tr>`).join('');
        const col = document.createElement('div');
        col.className = 'col-lg-6';
        col.innerHTML = `<div class="card p-3 h-100">
            <div class="d-flex justify-content-between"><div><div class="fw-bold">${esc(c.clave)}</div><div class="text-muted small">${esc(c.nombre)}</div></div>
              <div class="text-end"><div class="stat-label">Costo por jornada</div><div class="fs-4 fw-bold">${money(k.total)}</div><div class="small text-muted">Factor ${numero(k.factor, 4)}</div></div></div>
            <div class="table-responsive"><table class="table table-sm mt-2 mb-2"><thead><tr><th>Puesto</th><th class="num">Cant.</th><th class="num">Salario semanal</th></tr></thead><tbody>${filas}</tbody></table></div>
            <dl class="row desglose small mb-2">
              <dt class="col-7">Nómina diaria</dt><dd class="col-5">${money(k.nomina)}</dd>
              <dt class="col-7">Aguinaldo + prima vacacional</dt><dd class="col-5">${money(k.gratificacion + k.prima_vacacional)}</dd>
              <dt class="col-7">Impuesto sobre nómina</dt><dd class="col-5">${money(k.isn)}</dd>
              <dt class="col-7">IMSS + INFONAVIT + AFORE</dt><dd class="col-5">${money(k.imss + k.infonavit + k.afore)}</dd>
            </dl>
            <div class="text-end"><button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i> Editar</button></div></div>`;
        col.querySelector('button').addEventListener('click', () => editar(c));
        cont.appendChild(col);
    });
}

function filaIntegrante(i = { puesto: '', cantidad: 1, salario_semanal: '' }) {
    const tr = document.createElement('tr');
    tr.innerHTML = `<td><input class="form-control form-control-sm" data-k="puesto" value="${esc(i.puesto)}"></td>
        <td><input type="number" step="0.5" min="0" class="form-control form-control-sm" data-k="cantidad" value="${esc(i.cantidad)}"></td>
        <td><input type="number" step="0.01" min="0" class="form-control form-control-sm" data-k="salario_semanal" value="${esc(i.salario_semanal)}"></td>
        <td><button type="button" class="btn btn-sm btn-link text-danger"><i class="bi bi-x-lg"></i></button></td>`;
    tr.querySelector('button').addEventListener('click', () => tr.remove());
    return tr;
}

function agregarIntegrante(i) {
    document.getElementById('integrantes').appendChild(filaIntegrante(i));
}

function nueva() {
    const f = document.getElementById('formCuadrilla');
    f.reset();
    f.id.value = '';
    document.getElementById('integrantes').innerHTML = '';
    agregarIntegrante();
    document.getElementById('btnEliminar').classList.add('d-none');
    modal.show();
}

function editar(c) {
    fillForm(document.getElementById('formCuadrilla'), c);
    document.getElementById('integrantes').innerHTML = '';
    c.integrantes.forEach((i) => agregarIntegrante(i));
    document.getElementById('btnEliminar').classList.remove('d-none');
    modal.show();
}

async function guardar(e) {
    e.preventDefault();
    const f = e.target;
    const integrantes = [...document.querySelectorAll('#integrantes tr')].map((tr) => {
        const o = {};
        tr.querySelectorAll('input').forEach((inp) => { o[inp.dataset.k] = inp.value; });
        return o;
    });
    const id = f.id.value;
    try {
        await api(id ? `api/cuadrillas/${id}` : 'api/cuadrillas', id ? 'PUT' : 'POST', { clave: f.clave.value, nombre: f.nombre.value, integrantes });
        modal.hide();
        cargar();
    } catch (err) {
        alert(err.message);
    }
}

async function eliminar() {
    const id = document.getElementById('formCuadrilla').id.value;
    if (!confirm('¿Eliminar esta cuadrilla?')) return;
    try {
        await api(`api/cuadrillas/${id}`, 'DELETE');
        modal.hide();
        cargar();
    } catch (err) {
        alert(err.message);
    }
}
</script>
