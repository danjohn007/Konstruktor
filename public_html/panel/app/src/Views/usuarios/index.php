<div class="row g-3 mb-3">
  <?php foreach ([
      ['Admin', 'shield-lock', 'tono-navy', 'Todo el sistema, incluida configuración, usuarios y eliminaciones.'],
      ['Cotizador', 'calculator', 'tono-brand', 'Cotizaciones, estimaciones, control de obra y catálogos.'],
      ['Ingeniero', 'cone-striped', 'tono-warning', 'Residente de obra: ve las obras aceptadas y registra avance y gastos con fotos, en la web y la app.'],
  ] as [$rol, $icono, $tono, $texto]): ?>
  <div class="col-md-4">
    <div class="kpi">
      <span class="kpi-icon <?= $tono ?>"><i class="bi bi-<?= $icono ?>"></i></span>
      <div class="kpi-body">
        <div class="kpi-label"><?= $rol ?></div>
        <div class="kpi-value" data-rol="<?= $rol ?>">–</div>
        <div class="kpi-sub"><?= $texto ?></div>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<div class="card">
  <div class="card-titulo">
    <div><h3>Usuarios</h3><p>Un usuario desactivado pierde la sesión de inmediato, también en la app.</p></div>
    <button class="btn btn-primary" onclick="nuevo()"><i class="bi bi-plus-lg"></i> Nuevo usuario</button>
  </div>
  <div class="table-responsive">
  <table class="table mb-0">
    <thead><tr><th>Nombre</th><th>Correo</th><th>Rol</th><th>Estado</th><th></th></tr></thead>
    <tbody id="tabla"><tr><td colspan="5" class="vacio-tabla">Cargando…</td></tr></tbody>
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
    document.querySelectorAll('[data-rol]').forEach((el) => {
        const activos = d.usuarios.filter((u) => u.rol_nombre === el.dataset.rol && Number(u.activo)).length;
        el.textContent = `${activos} usuario${activos === 1 ? '' : 's'}`;
    });
    d.usuarios.forEach((u) => {
        const tr = document.createElement('tr');
        const iniciales = u.nombre.trim().split(/\s+/).slice(0, 2).map((p) => p[0].toUpperCase()).join('');
        tr.innerHTML = `<td><div class="d-flex align-items-center gap-2"><span class="avatar" style="width:32px;height:32px;font-size:.72rem">${esc(iniciales)}</span><span class="fw-semibold">${esc(u.nombre)}</span></div></td>
            <td class="text-muted">${esc(u.email)}</td><td><span class="badge rol-${esc(u.rol_nombre)}">${esc(u.rol_nombre)}</span></td>
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
