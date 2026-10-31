<div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
  <div class="input-group" style="max-width:320px">
    <span class="input-group-text"><i class="bi bi-search"></i></span>
    <input type="text" id="buscarCliente" class="form-control" placeholder="Buscar cliente...">
  </div>
  <button class="btn btn-primary" onclick="nuevoCliente()"><i class="bi bi-plus-lg"></i> Nuevo cliente</button>
</div>

<div class="card">
  <div class="table-responsive">
  <table class="table mb-0">
    <thead><tr><th>Nombre</th><th>RFC</th><th>Contacto</th><th>Teléfono</th><th>Correo</th><th></th></tr></thead>
    <tbody id="tablaClientes"><tr><td colspan="6" class="text-muted">Cargando…</td></tr></tbody>
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
    if (!data.length) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-muted">Sin clientes registrados</td></tr>';
        return;
    }
    data.forEach((c) => {
        const tr = document.createElement('tr');
        tr.innerHTML = `<td>${esc(c.nombre)}</td><td>${esc(c.rfc)}</td><td>${esc(c.contacto)}</td><td>${esc(c.telefono)}</td>
            <td>${esc(c.email)}</td><td class="text-end"></td>`;
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'btn btn-sm btn-outline-secondary';
        btn.innerHTML = '<i class="bi bi-pencil"></i>';
        btn.addEventListener('click', () => editarCliente(c));
        tr.lastElementChild.appendChild(btn);
        tbody.appendChild(tr);
    });
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
