<div class="d-flex justify-content-between align-items-center mb-3">
  <p class="text-muted small mb-0"><strong>Admin</strong>: todo, incluida configuración y eliminaciones. <strong>Cotizador</strong>: cotizaciones, estimaciones, control de obra y catálogos. <strong>Ingeniero</strong>: residente de obra; ve las obras aceptadas y registra avance y gastos con fotos (web y app).</p>
  <button class="btn btn-primary" onclick="nuevo()"><i class="bi bi-plus-lg"></i> Nuevo usuario</button>
</div>

<div class="card">
  <div class="table-responsive">
  <table class="table mb-0">
    <thead><tr><th>Nombre</th><th>Correo</th><th>Rol</th><th>Estado</th><th></th></tr></thead>
    <tbody id="tabla"><tr><td colspan="5" class="text-muted">Cargando…</td></tr></tbody>
  </table>
  </div>
</div>

<div class="modal fade" id="modalUsuario" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="formUsuario">
        <div class="modal-header"><h5 class="modal-title">Usuario</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <input type="hidden" name="id">
          <div class="mb-2"><label class="form-label">Nombre *</label><input class="form-control" name="nombre" required></div>
          <div class="mb-2"><label class="form-label">Correo *</label><input type="email" class="form-control" name="email" required></div>
          <div class="mb-2"><label class="form-label">Rol</label><select class="form-select" name="rol_id"></select></div>
          <div class="mb-2"><label class="form-label" id="lblPassword">Contraseña *</label><input type="password" class="form-control" name="password" minlength="8" autocomplete="new-password"></div>
          <div class="form-check"><input class="form-check-input" type="checkbox" name="activo" id="chkActivo"><label class="form-check-label" for="chkActivo">Activo</label></div>
        </div>
        <div class="modal-footer"><button class="btn btn-primary">Guardar</button></div>
      </form>
    </div>
  </div>
</div>

<script>
let modal;

document.addEventListener('DOMContentLoaded', () => {
    modal = new bootstrap.Modal(document.getElementById('modalUsuario'));
    document.getElementById('formUsuario').addEventListener('submit', guardar);
    cargar();
});

async function cargar() {
    const d = await api('api/usuarios');
    const sel = document.querySelector('#formUsuario [name=rol_id]');
    sel.innerHTML = d.roles.map((r) => `<option value="${r.id}">${esc(r.nombre)}</option>`).join('');
    const tb = document.getElementById('tabla');
    tb.innerHTML = '';
    d.usuarios.forEach((u) => {
        const tr = document.createElement('tr');
        tr.innerHTML = `<td>${esc(u.nombre)}</td><td>${esc(u.email)}</td><td>${esc(u.rol_nombre)}</td>
            <td>${Number(u.activo) ? '<span class="badge bg-success-subtle text-success-emphasis">Activo</span>' : '<span class="badge bg-secondary-subtle text-secondary-emphasis">Inactivo</span>'}</td>
            <td class="text-end"><button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button></td>`;
        tr.querySelector('button').addEventListener('click', () => editar(u));
        tb.appendChild(tr);
    });
}

function nuevo() {
    const f = document.getElementById('formUsuario');
    f.reset();
    f.id.value = '';
    f.email.disabled = false;
    f.activo.checked = true;
    f.password.required = true;
    document.getElementById('lblPassword').textContent = 'Contraseña *';
    modal.show();
}

function editar(u) {
    const f = document.getElementById('formUsuario');
    f.reset();
    fillForm(f, u);
    f.email.disabled = true;
    f.password.required = false;
    document.getElementById('lblPassword').textContent = 'Nueva contraseña (déjala vacía para no cambiarla)';
    modal.show();
}

async function guardar(e) {
    e.preventDefault();
    const d = formData(e.target);
    try {
        await api(d.id ? 'api/usuarios/' + d.id : 'api/usuarios', d.id ? 'PUT' : 'POST', d);
        modal.hide();
        cargar();
    } catch (err) {
        alert(err.message);
    }
}
</script>
