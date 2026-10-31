<?php $esIngeniero = ($user['rol_nombre'] ?? '') === 'Ingeniero'; // solo ve las obras (aceptadas) ?>
<div class="card">
  <?php if (!$esIngeniero): ?>
  <div class="px-3 pt-2">
    <ul class="nav nav-tabs nav-tabs-scroll" id="tabsEstado">
      <?php foreach (['' => 'Todas', 'borrador' => 'Borrador', 'enviada' => 'Enviadas', 'aceptada' => 'Aceptadas (obras)', 'rechazada' => 'Rechazadas'] as $valor => $etiqueta): ?>
      <li class="nav-item"><button class="nav-link" type="button" data-estado="<?= $valor ?>"><?= $etiqueta ?> <span class="cuenta" data-cuenta="<?= $valor ?>">–</span></button></li>
      <?php endforeach; ?>
    </ul>
  </div>
  <?php endif; ?>
  <div class="filtros">
    <div class="buscador"><i class="bi bi-search"></i><input type="text" id="buscar" class="form-control" placeholder="Folio, obra o cliente…" value="<?= htmlspecialchars($_GET['q'] ?? '') ?>"></div>
    <span class="small text-muted" id="sumaLista"></span>
    <?php if (!$esIngeniero): ?>
    <button class="btn btn-primary ms-auto" onclick="nueva()"><i class="bi bi-plus-lg"></i> Nueva cotización</button>
    <?php endif; ?>
  </div>
  <div class="table-responsive">
  <table class="table mb-0">
    <thead><tr><th>Folio</th><th>Obra</th><th>Cliente</th><th>Fecha</th><th>Estado</th><?php if (!$esIngeniero): ?><th>Ingeniero</th><?php endif; ?><th class="num">Subtotal</th><th class="num">Total con IVA</th></tr></thead>
    <tbody id="tabla"><tr><td colspan="7" class="vacio-tabla">Cargando…</td></tr></tbody>
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
let cotizaciones = [];
const CON_INGENIERO = <?= $esIngeniero ? 'false' : 'true' ?>;
let estado = new URLSearchParams(location.search).get('estado') || '';

document.addEventListener('DOMContentLoaded', () => {
    modal = new bootstrap.Modal(document.getElementById('modalNueva'));
    document.querySelectorAll('#tabsEstado [data-estado]').forEach((b) => b.addEventListener('click', () => { estado = b.dataset.estado; pintar(); }));
    document.getElementById('buscar').addEventListener('input', debounce(cargar, 300));
    document.getElementById('formNueva').addEventListener('submit', crear);
    cargar();
});

/** La búsqueda va al servidor; las pestañas de estado filtran en el navegador para mostrar sus cuentas. */
async function cargar() {
    cotizaciones = await api('api/cotizaciones?' + new URLSearchParams({ q: document.getElementById('buscar').value }));
    const cuenta = (e) => cotizaciones.filter((c) => !e || c.estado === e).length;
    document.querySelectorAll('[data-cuenta]').forEach((el) => { el.textContent = cuenta(el.dataset.cuenta); });
    pintar();
}

function pintar() {
    document.querySelectorAll('#tabsEstado [data-estado]').forEach((b) => b.classList.toggle('active', b.dataset.estado === estado));
    const data = cotizaciones.filter((c) => !estado || c.estado === estado);
    const suma = data.reduce((s, c) => s + Number(c.total), 0);
    document.getElementById('sumaLista').textContent = data.length ? `${data.length} · ${money(suma)} con IVA` : '';
    const tb = document.getElementById('tabla');
    tb.innerHTML = data.length ? '' : `<tr><td colspan="8" class="vacio-tabla"><i class="bi bi-file-earmark-spreadsheet"></i>${CON_INGENIERO ? 'Sin cotizaciones' : 'No tienes obras asignadas. Pide a la oficina que te asigne a tu obra.'}</td></tr>`;
    data.forEach((c) => {
        const tr = document.createElement('tr');
        tr.className = 'fila-click';
        tr.innerHTML = `<td class="text-nowrap fw-semibold">${esc(c.folio)}</td><td>${esc(c.obra)}</td><td class="text-muted">${esc(c.cliente || '—')}</td><td class="text-nowrap">${fecha(c.fecha_cotizacion)}</td>
            <td><span class="badge estado-${esc(c.estado)}">${esc(c.estado)}</span></td>
            ${CON_INGENIERO ? `<td class="small">${c.ingenieros ? esc(c.ingenieros) : (c.estado === 'aceptada' ? '<span class="text-danger">Sin asignar</span>' : '<span class="text-muted">—</span>')}</td>` : ''}
            <td class="num">${money(c.subtotal)}</td><td class="num fw-semibold">${money(c.total)}</td>`;
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
