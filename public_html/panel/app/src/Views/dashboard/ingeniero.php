<div class="row g-3 mb-3">
  <div class="col-6 col-xl-3"><a class="kpi" href="cotizaciones"><span class="kpi-icon tono-brand"><i class="bi bi-cone-striped"></i></span>
    <div class="kpi-body"><div class="kpi-label">Mis obras</div><div class="kpi-value" id="kObras">–</div><div class="kpi-sub" id="kObrasSub"></div></div></a></div>
  <div class="col-6 col-xl-3"><div class="kpi"><span class="kpi-icon tono-success"><i class="bi bi-bricks"></i></span>
    <div class="kpi-body"><div class="kpi-label">Avances esta semana</div><div class="kpi-value" id="kAvances">–</div><div class="kpi-sub">Registros desde el lunes</div></div></div></div>
  <div class="col-6 col-xl-3"><div class="kpi"><span class="kpi-icon tono-warning"><i class="bi bi-receipt"></i></span>
    <div class="kpi-body"><div class="kpi-label">Gastos esta semana</div><div class="kpi-value" id="kGastado">–</div><div class="kpi-sub" id="kGastos"></div></div></div></div>
  <div class="col-6 col-xl-3"><div class="kpi"><span class="kpi-icon tono-danger" id="kSinIcono"><i class="bi bi-paperclip"></i></span>
    <div class="kpi-body"><div class="kpi-label">Gastos sin comprobante</div><div class="kpi-value" id="kSin">–</div><div class="kpi-sub" id="kSinSub"></div></div></div></div>
</div>

<div class="card mb-3">
  <div class="card-titulo"><div><h3>Mis obras</h3><p>Avance real contra lo que el programa dice a hoy (la marca es lo programado)</p></div></div>
  <div class="table-responsive">
    <table class="table mb-0">
      <thead><tr><th>Obra</th><th style="width:28%">Avance</th><th>Último registro</th><th class="text-end">Registrar</th></tr></thead>
      <tbody id="obras"><tr><td colspan="4" class="vacio-tabla">Cargando…</td></tr></tbody>
    </table>
  </div>
</div>

<div class="row g-3">
  <div class="col-xl-6">
    <div class="card h-100">
      <div class="card-titulo"><div><h3>Mis últimos avances</h3><p>Lo que has registrado en tus obras</p></div></div>
      <div class="table-responsive tabla-alta"><table class="table table-sm mb-0"><tbody id="avances"></tbody></table></div>
    </div>
  </div>
  <div class="col-xl-6">
    <div class="card h-100">
      <div class="card-titulo"><div><h3>Mis últimos gastos</h3><p>Sube la foto del ticket o factura a los que no la tienen</p></div></div>
      <div class="table-responsive tabla-alta"><table class="table table-sm mb-0"><tbody id="gastos"></tbody></table></div>
    </div>
  </div>
</div>

<script>
const NOMBRE_CAT = { MAT: 'Materiales', MO: 'Mano de obra', EQ: 'Equipo', SUBCONTRATO: 'Subcontrato', INDIRECTO: 'Indirectos', OTRO: 'Otros' };

document.addEventListener('DOMContentLoaded', async () => {
    const [obras, r] = await Promise.all([api('api/obras'), api('api/ingeniero/resumen')]);
    const s = r.semana;

    const atrasadas = obras.filter((o) => o.pct_ejecutado + 0.005 < o.pct_programado).length;
    document.getElementById('kObras').textContent = obras.length;
    document.getElementById('kObrasSub').textContent = !obras.length ? 'Pide a la oficina que te asigne' : atrasadas ? `${atrasadas} atrasada(s) respecto al programa` : 'Todas al corriente';
    document.getElementById('kAvances').textContent = s.avances;
    document.getElementById('kGastado').textContent = moneyEntero(s.gastado);
    document.getElementById('kGastos').textContent = `${s.gastos} gasto(s) desde el lunes`;
    document.getElementById('kSin').textContent = s.sin_comprobante;
    document.getElementById('kSinSub').textContent = s.sin_comprobante ? 'Súbeles la foto del ticket' : 'Todo con comprobante';
    document.getElementById('kSinIcono').className = 'kpi-icon ' + (s.sin_comprobante ? 'tono-danger' : 'tono-success');

    document.getElementById('obras').innerHTML = obras.map((o) => `<tr>
        <td><a class="fw-semibold" href="cotizaciones/${o.id}">${esc(o.obra)}</a><div class="small text-muted">${esc(o.folio)}${o.direccion ? ' · ' + esc(o.direccion) : ''}</div></td>
        <td><div class="d-flex align-items-center gap-2">${barraRealVsProgramado(o.pct_ejecutado, o.pct_programado)}<span class="small fw-semibold">${pct(o.pct_ejecutado)}</span></div>
          <div class="small text-muted">programado ${pct(o.pct_programado)}</div></td>
        <td class="small text-nowrap">${o.ultimo_registro ? fecha(o.ultimo_registro) : '<span class="text-muted">Sin registros</span>'}</td>
        <td class="text-end text-nowrap">
          <a class="btn btn-sm btn-primary" href="cotizaciones/${o.id}#avance"><i class="bi bi-plus-lg"></i> Avance</a>
          <a class="btn btn-sm btn-outline-secondary" href="cotizaciones/${o.id}#control"><i class="bi bi-receipt"></i> Gasto</a></td></tr>`).join('')
        || '<tr><td colspan="4" class="vacio-tabla"><i class="bi bi-cone-striped"></i>No tienes obras asignadas. Pide a la oficina que te asigne a tu obra.</td></tr>';

    document.getElementById('avances').innerHTML = r.avances.map((a) => `<tr class="fila-click" onclick="location.href='cotizaciones/${a.cotizacion_id}#avance'">
        <td class="text-nowrap">${fecha(a.fecha)}</td>
        <td><span class="fw-semibold">${esc(a.clave)}</span> ${esc(String(a.descripcion).slice(0, 45))}<div class="small text-muted">${esc(a.folio)}${a.notas ? ' · ' + esc(a.notas) : ''}</div></td>
        <td class="num">${numero(a.cantidad)} ${esc(a.unidad)}</td>
        <td>${Number(a.foto) ? '<i class="bi bi-image text-success" title="Con foto"></i>' : '<i class="bi bi-image text-muted opacity-50" title="Sin foto"></i>'}</td></tr>`).join('')
        || '<tr><td class="vacio-tabla"><i class="bi bi-bricks"></i>Aún no registras avance</td></tr>';

    document.getElementById('gastos').innerHTML = r.gastos.map((g) => `<tr class="fila-click" onclick="location.href='cotizaciones/${g.cotizacion_id}#control'">
        <td class="text-nowrap">${fecha(g.fecha)}</td>
        <td>${esc(g.descripcion)}<div class="small text-muted">${esc(g.folio)} · ${NOMBRE_CAT[g.categoria] || esc(g.categoria)}</div></td>
        <td class="num fw-semibold">${money(g.monto)}</td>
        <td>${Number(g.comprobante) ? '<i class="bi bi-paperclip text-success" title="Con comprobante"></i>' : '<span class="badge bg-danger-subtle text-danger-emphasis">Sin ticket</span>'}</td></tr>`).join('')
        || '<tr><td class="vacio-tabla"><i class="bi bi-receipt"></i>Aún no registras gastos</td></tr>';
});
</script>
