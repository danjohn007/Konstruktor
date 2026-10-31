<div class="row g-3 mb-4" id="stats">
  <div class="col-sm-6 col-xl-3"><div class="stat-card"><div class="stat-label">Cotizaciones</div><div class="stat-value" id="stTotal">–</div></div></div>
  <div class="col-sm-6 col-xl-3"><div class="stat-card"><div class="stat-label">En borrador</div><div class="stat-value" id="stBorrador">–</div></div></div>
  <div class="col-sm-6 col-xl-3"><div class="stat-card"><div class="stat-label">Enviadas (monto)</div><div class="stat-value fs-4" id="stEnviadas">–</div></div></div>
  <div class="col-sm-6 col-xl-3"><div class="stat-card"><div class="stat-label">Aceptadas (monto)</div><div class="stat-value fs-4" id="stAceptadas">–</div></div></div>
</div>

<div class="d-flex justify-content-between align-items-center mb-2">
  <h6 class="mb-0">Cotizaciones recientes</h6>
  <a href="cotizaciones" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg"></i> Nueva cotización</a>
</div>
<div class="card">
  <div class="table-responsive">
  <table class="table mb-0">
    <thead><tr><th>Folio</th><th>Obra</th><th>Cliente</th><th>Fecha</th><th>Estado</th><th class="num">Total</th></tr></thead>
    <tbody id="recientes"><tr><td colspan="6" class="text-muted">Cargando…</td></tr></tbody>
  </table>
  </div>
</div>
<p class="text-muted small mt-3" id="catalogos"></p>

<script>
document.addEventListener('DOMContentLoaded', async () => {
    const d = await api('api/dashboard/resumen');
    const e = d.por_estado;
    document.getElementById('stTotal').textContent = d.total_cotizaciones;
    document.getElementById('stBorrador').textContent = e.borrador?.n ?? 0;
    document.getElementById('stEnviadas').textContent = money(e.enviada?.monto);
    document.getElementById('stAceptadas').textContent = money(e.aceptada?.monto);
    document.getElementById('catalogos').textContent = `${d.insumos} insumos activos · ${d.clientes} clientes`;

    const tb = document.getElementById('recientes');
    tb.innerHTML = d.recientes.length ? '' : '<tr><td colspan="6" class="text-muted">Aún no hay cotizaciones</td></tr>';
    d.recientes.forEach((c) => {
        const tr = document.createElement('tr');
        tr.style.cursor = 'pointer';
        tr.innerHTML = `<td>${esc(c.folio)}</td><td>${esc(c.obra)}</td><td>${esc(c.cliente)}</td><td>${fecha(c.fecha_cotizacion)}</td>
            <td><span class="badge estado-${esc(c.estado)}">${esc(c.estado)}</span></td><td class="num">${money(c.total)}</td>`;
        tr.addEventListener('click', () => { window.location.href = 'cotizaciones/' + c.id; });
        tb.appendChild(tr);
    });
});
</script>
