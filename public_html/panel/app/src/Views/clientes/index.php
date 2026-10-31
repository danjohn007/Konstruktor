<div class="row g-3 mb-3">
  <div class="col-6 col-xl-3"><div class="kpi"><span class="kpi-icon tono-brand"><i class="bi bi-people"></i></span>
    <div class="kpi-body"><div class="kpi-label">Clientes</div><div class="kpi-value" id="kClientes">–</div><div class="kpi-sub" id="kClientesSub"></div></div></div></div>
  <div class="col-6 col-xl-3"><div class="kpi"><span class="kpi-icon tono-success"><i class="bi bi-briefcase"></i></span>
    <div class="kpi-body"><div class="kpi-label">Contratado total</div><div class="kpi-value" id="kContratado">–</div><div class="kpi-sub">Cotizaciones aceptadas, con IVA</div></div></div></div>
  <div class="col-6 col-xl-3"><div class="kpi"><span class="kpi-icon tono-teal"><i class="bi bi-arrow-repeat"></i></span>
    <div class="kpi-body"><div class="kpi-label">Clientes recurrentes</div><div class="kpi-value" id="kRecurrentes">–</div><div class="kpi-sub">Con más de una obra</div></div></div></div>
  <div class="col-6 col-xl-3"><div class="kpi"><span class="kpi-icon tono-purple"><i class="bi bi-trophy"></i></span>
    <div class="kpi-body"><div class="kpi-label">Cliente principal</div><div class="kpi-value text-truncate" id="kPrincipal" style="font-size:1.05rem">–</div><div class="kpi-sub" id="kPrincipalSub"></div></div></div></div>
</div>

<div class="card">
  <div class="filtros">
    <div class="buscador"><i class="bi bi-search"></i><input type="text" id="buscarCliente" class="form-control" placeholder="Nombre, RFC o contacto…"></div>
    <button class="btn btn-primary ms-auto" onclick="nuevoCliente()"><i class="bi bi-plus-lg"></i> Nuevo cliente</button>
  </div>
  <div class="table-responsive tabla-alta">
  <table class="table mb-0">
    <thead><tr><th>Cliente</th><th>Contacto</th><th class="num">Cotizaciones</th><th class="num">Obras</th><th class="num">Contratado</th><th>Última cotización</th><th></th></tr></thead>
    <tbody id="tablaClientes"><tr><td colspan="7" class="vacio-tabla">Cargando…</td></tr></tbody>
  </table>
  </div>
</div>

<div class="modal fade" id="modalCliente" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="formCliente">
        <div class="modal-header">
          <h5 class="modal-title">Cliente</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="id">
          <div class="mb-2"><label class="form-label">Nombre o razón social *</label><input class="form-control" name="nombre" required></div>
          <div class="mb-2"><label class="form-label">RFC</label><input class="form-control" name="rfc" maxlength="13"></div>
          <div class="mb-2"><label class="form-label">Contacto (atención)</label><input class="form-control" name="contacto"></div>
          <div class="mb-2"><label class="form-label">Teléfono</label><input class="form-control" name="telefono"></div>
          <div class="mb-2"><label class="form-label">Correo</label><input type="email" class="form-control" name="email"></div>
          <div class="mb-2"><label class="form-label">Dirección</label><input class="form-control" name="direccion"></div>
        </div>
        <div class="modal-footer"><button type="submit" class="btn btn-primary">Guardar</button></div>
      </form>
    </div>
  </div>
</div>

<script>
let modalCliente;

document.addEventListener('DOMContentLoaded', () => {
    modalCliente = new bootstrap.Modal(document.getElementById('modalCliente'));
    cargarClientes();
    document.getElementById('buscarCliente').addEventListener('input', debounce(cargarClientes, 300));
    document.getElementById('formCliente').addEventListener('submit', guardarCliente);
});

async function cargarClientes() {
    const q = document.getElementById('buscarCliente').value;
    const data = await api('api/clientes?q=' + encodeURIComponent(q));
    const tbody = document.getElementById('tablaClientes');
    tbody.innerHTML = '';
    if (!q) resumenClientes(data);
    if (!data.length) {
        tbody.innerHTML = `<tr><td colspan="7" class="vacio-tabla"><i class="bi bi-people"></i>${q ? 'Sin resultados' : 'Sin clientes registrados'}</td></tr>`;
        return;
    }
    data.forEach((c) => {
        const tr = document.createElement('tr');
        const contacto = [c.contacto, c.telefono, c.email].filter(Boolean).map(esc).join(' · ');
        tr.innerHTML = `<td><div class="fw-semibold">${esc(c.nombre)}</div>${c.rfc ? `<div class="small text-muted">${esc(c.rfc)}</div>` : ''}</td>
            <td class="small">${contacto || '<span class="text-muted">—</span>'}</td>
            <td class="num">${c.cotizaciones}</td><td class="num">${Number(c.obras) || 0}</td><td class="num fw-semibold">${Number(c.contratado) ? money(c.contratado) : '—'}</td>
            <td class="text-nowrap">${c.ultima ? fecha(c.ultima) : '<span class="text-muted">—</span>'}</td><td class="text-end"></td>`;
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'btn btn-sm btn-outline-secondary';
        btn.innerHTML = '<i class="bi bi-pencil"></i>';
        btn.addEventListener('click', () => editarCliente(c));
        tr.lastElementChild.appendChild(btn);
        tbody.appendChild(tr);
    });
}

/** Indicadores de la cartera de clientes (sobre la lista completa, no la búsqueda). */
function resumenClientes(data) {
    const contratado = data.reduce((s, c) => s + Number(c.contratado), 0);
    document.getElementById('kClientes').textContent = data.length;
    document.getElementById('kClientesSub').textContent = `${data.filter((c) => Number(c.obras) > 0).length} con obra contratada`;
    document.getElementById('kContratado').textContent = moneyEntero(contratado);
    document.getElementById('kRecurrentes').textContent = data.filter((c) => Number(c.obras) > 1).length;
    const top = [...data].sort((a, b) => b.contratado - a.contratado)[0];
    document.getElementById('kPrincipal').textContent = top && Number(top.contratado) ? top.nombre : '—';
    document.getElementById('kPrincipalSub').textContent = top && Number(top.contratado) && contratado ? `${pct(top.contratado / contratado)} de lo contratado` : 'Sin obras contratadas';
}

function nuevoCliente() {
    const form = document.getElementById('formCliente');
    form.reset();
    form.id.value = '';
    modalCliente.show();
}

function editarCliente(c) {
    fillForm(document.getElementById('formCliente'), c);
    modalCliente.show();
}

async function guardarCliente(e) {
    e.preventDefault();
    const d = formData(e.target);
    try {
        await api(d.id ? `api/clientes/${d.id}` : 'api/clientes', d.id ? 'PUT' : 'POST', d);
        modalCliente.hide();
        cargarClientes();
    } catch (err) {
        alert(err.message);
    }
}
</script>
