<?php $esIngeniero = ($user['rol_nombre'] ?? '') === 'Ingeniero'; // solo ve las obras (aceptadas) ?>
<div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
  <div class="d-flex flex-wrap gap-2">
    <div class="input-group" style="max-width:320px">
      <span class="input-group-text"><i class="bi bi-search"></i></span>
      <input type="text" id="buscar" class="form-control" placeholder="Folio, obra o cliente..." value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
    </div>
    <select id="filtroEstado" class="form-select<?= $esIngeniero ? ' d-none' : '' ?>" style="max-width:180px">
      <option value="">Todos los estados</option>
      <option value="borrador">Borrador</option>
      <option value="enviada">Enviada</option>
      <option value="aceptada">Aceptada</option>
      <option value="rechazada">Rechazada</option>
    </select>
  </div>
  <?php if (!$esIngeniero): ?>
  <button class="btn btn-primary" onclick="nueva()"><i class="bi bi-plus-lg"></i> Nueva cotización</button>
  <?php endif; ?>
</div>

<div class="card">
  <div class="table-responsive">
  <table class="table mb-0">
    <thead><tr><th>Folio</th><th>Obra</th><th>Cliente</th><th>Fecha</th><th>Estado</th><th class="num">Subtotal</th><th class="num">Total con IVA</th></tr></thead>
    <tbody id="tabla"><tr><td colspan="7" class="text-muted">Cargando…</td></tr></tbody>
  </table>
  </div>
</div>

<div class="modal fade" id="modalNueva" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="formNueva">
        <div class="modal-header">
          <h5 class="modal-title">Nueva cotización</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-2"><label class="form-label">Obra *</label><input class="form-control" name="obra" required></div>
          <div class="mb-2"><label class="form-label">Cliente</label><select class="form-select" name="cliente_id"><option value="">— Sin cliente —</option></select></div>
          <div class="row g-2">
            <div class="col-8"><label class="form-label">Dirección</label><input class="form-control" name="direccion"></div>
            <div class="col-4"><label class="form-label">Municipio</label><input class="form-control" name="municipio"></div>
            <div class="col-6"><label class="form-label">Fecha de cotización</label><input type="date" class="form-control" name="fecha_cotizacion"></div>
            <div class="col-6"><label class="form-label">Inicio de obra</label><input type="date" class="form-control" name="fecha_inicio"></div>
          </div>
          <div class="form-text mt-2">Indirectos, utilidad, IVA y textos se toman de Configuración; puedes cambiarlos en la cotización.</div>
        </div>
        <div class="modal-footer"><button type="submit" class="btn btn-primary">Crear</button></div>
      </form>
    </div>
  </div>
</div>

<script>
let modal;

document.addEventListener('DOMContentLoaded', () => {
    modal = new bootstrap.Modal(document.getElementById('modalNueva'));
    cargar();
    document.getElementById('buscar').addEventListener('input', debounce(cargar, 300));
    document.getElementById('filtroEstado').addEventListener('change', cargar);
    document.getElementById('formNueva').addEventListener('submit', crear);
});

async function cargar() {
    const qs = new URLSearchParams({ q: document.getElementById('buscar').value, estado: document.getElementById('filtroEstado').value });
    const data = await api('api/cotizaciones?' + qs);
    const tb = document.getElementById('tabla');
    tb.innerHTML = data.length ? '' : '<tr><td colspan="7" class="text-muted">Sin cotizaciones</td></tr>';
    data.forEach((c) => {
        const tr = document.createElement('tr');
        tr.style.cursor = 'pointer';
        tr.innerHTML = `<td class="text-nowrap">${esc(c.folio)}</td><td>${esc(c.obra)}</td><td>${esc(c.cliente)}</td><td>${fecha(c.fecha_cotizacion)}</td>
            <td><span class="badge estado-${esc(c.estado)}">${esc(c.estado)}</span></td><td class="num">${money(c.subtotal)}</td><td class="num fw-semibold">${money(c.total)}</td>`;
        tr.addEventListener('click', () => { window.location.href = 'cotizaciones/' + c.id; });
        tb.appendChild(tr);
    });
}

async function nueva() {
    const f = document.getElementById('formNueva');
    f.reset();
    f.fecha_cotizacion.value = hoyISO();
    const sel = f.cliente_id;
    sel.length = 1;
    (await api('api/clientes')).forEach((c) => sel.insertAdjacentHTML('beforeend', `<option value="${c.id}">${esc(c.nombre)}</option>`));
    modal.show();
}

async function crear(e) {
    e.preventDefault();
    try {
        const r = await api('api/cotizaciones', 'POST', formData(e.target));
        window.location.href = 'cotizaciones/' + r.id;
    } catch (err) {
        alert(err.message);
    }
}
</script>
