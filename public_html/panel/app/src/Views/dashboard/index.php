<div class="row g-3 mb-3">
  <div class="col-6 col-xl-3">
    <a class="kpi" href="cotizaciones?estado=enviada">
      <span class="kpi-icon tono-brand"><i class="bi bi-send"></i></span>
      <div class="kpi-body"><div class="kpi-label">Por cerrar (enviadas)</div><div class="kpi-value" id="kPorCerrar">–</div><div class="kpi-sub" id="kPorCerrarSub"></div></div>
    </a>
  </div>
  <div class="col-6 col-xl-3">
    <a class="kpi" href="cotizaciones?estado=aceptada">
      <span class="kpi-icon tono-success"><i class="bi bi-briefcase"></i></span>
      <div class="kpi-body"><div class="kpi-label">Contratado (aceptadas)</div><div class="kpi-value" id="kContratado">–</div><div class="kpi-sub" id="kContratadoSub"></div></div>
    </a>
  </div>
  <div class="col-6 col-xl-3">
    <div class="kpi" title="Aceptadas entre aceptadas más rechazadas (las que el cliente ya decidió)">
      <span class="kpi-icon tono-teal"><i class="bi bi-bullseye"></i></span>
      <div class="kpi-body"><div class="kpi-label">Tasa de cierre</div><div class="kpi-value" id="kCierre">–</div><div class="kpi-sub" id="kCierreSub"></div></div>
    </div>
  </div>
  <div class="col-6 col-xl-3">
    <div class="kpi" title="Obras cuyo avance real va detrás de lo que el programa dice a hoy">
      <span class="kpi-icon tono-warning" id="kAtrasoIcono"><i class="bi bi-exclamation-triangle"></i></span>
      <div class="kpi-body"><div class="kpi-label">Obras atrasadas</div><div class="kpi-value" id="kAtraso">–</div><div class="kpi-sub" id="kAtrasoSub"></div></div>
    </div>
  </div>
</div>

<div class="row g-3 mb-3">
  <div class="col-xl-8">
    <div class="card h-100">
      <div class="card-titulo"><div><h3>Cotizado por mes</h3><p>Total con IVA por fecha de cotización, según su estado actual · últimos 12 meses</p></div></div>
      <div class="p-3"><div class="grafica" id="gMeses"></div></div>
    </div>
  </div>
  <div class="col-xl-4">
    <div class="card h-100">
      <div class="card-titulo"><div><h3>Cartera por estado</h3><p>Monto total de todas las cotizaciones</p></div></div>
      <div class="p-3"><div class="grafica" id="gEstados"></div></div>
    </div>
  </div>
</div>

<div class="row g-3 mb-3">
  <div class="col-xl-7">
    <div class="card h-100">
      <div class="card-titulo">
        <div><h3>Obras en ejecución</h3><p>Avance real contra programado a hoy (la marca es lo programado)</p></div>
        <a href="cotizaciones?estado=aceptada" class="btn btn-sm btn-outline-secondary">Ver todas</a>
      </div>
      <div class="table-responsive">
        <table class="table mb-0">
          <thead><tr><th>Obra</th><th style="width:30%">Avance</th><th class="num">Cobrado</th><th class="num">Gastado</th><th class="num">Caja</th></tr></thead>
          <tbody id="obras"><tr><td colspan="5" class="vacio-tabla">Cargando…</td></tr></tbody>
        </table>
      </div>
    </div>
  </div>
  <div class="col-xl-5">
    <div class="card h-100">
      <div class="card-titulo"><div><h3>Flujo de caja</h3><p>Ingresos y egresos registrados en control de obra · últimos 12 meses</p></div></div>
      <div class="p-3"><div class="grafica" id="gCaja"></div></div>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-titulo">
    <div><h3>Cotizaciones recientes</h3><p id="catalogos"></p></div>
    <a href="cotizaciones" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg"></i> Nueva cotización</a>
  </div>
  <div class="table-responsive">
  <table class="table mb-0">
    <thead><tr><th>Folio</th><th>Obra</th><th>Cliente</th><th>Fecha</th><th>Estado</th><th class="num">Total</th></tr></thead>
    <tbody id="recientes"><tr><td colspan="6" class="vacio-tabla">Cargando…</td></tr></tbody>
  </table>
  </div>
</div>

<script>
// COLORES viene de app.js, que carga después de este script: se lee ya con la página lista.
const estados = () => ({ borrador: ['Borrador', COLORES.slate], enviada: ['Enviada', COLORES.brand], aceptada: ['Aceptada', COLORES.success], rechazada: ['Rechazada', COLORES.danger] });

document.addEventListener('DOMContentLoaded', () => {
    cargarResumen();
    cargarObras();
});

async function cargarResumen() {
    const d = await api('api/dashboard/resumen');
    const e = d.por_estado;
    const n = (k) => e[k]?.n ?? 0;
    const monto = (k) => e[k]?.monto ?? 0;

    document.getElementById('kPorCerrar').textContent = moneyEntero(monto('enviada'));
    document.getElementById('kPorCerrarSub').textContent = `${n('enviada')} cotización(es) esperando respuesta`;
    document.getElementById('kContratado').textContent = moneyEntero(monto('aceptada'));
    document.getElementById('kContratadoSub').textContent = `${n('aceptada')} obra(s) · ${n('borrador')} en borrador`;
    const decididas = n('aceptada') + n('rechazada');
    document.getElementById('kCierre').textContent = decididas ? pct(n('aceptada') / decididas) : '—';
    document.getElementById('kCierreSub').textContent = decididas ? `${n('aceptada')} de ${decididas} decididas` : 'Aún no hay cotizaciones decididas';
    document.getElementById('catalogos').textContent = `${d.total_cotizaciones} cotizaciones · ${d.insumos} insumos activos · ${d.clientes} clientes`;

    grafica('gMeses', {
        xAxis: ejeCategorias(d.por_mes.map((m) => mesCorto(m.mes))),
        yAxis: ejeMontos(),
        series: Object.entries(estados()).map(([k, [nombre, color]]) => ({
            name: nombre, type: 'bar', stack: 'total', barMaxWidth: 34, color,
            itemStyle: { borderRadius: k === 'rechazada' ? [4, 4, 0, 0] : 0 },
            data: d.por_mes.map((m) => m[k]),
        })),
    });

    const datos = Object.entries(estados()).map(([k, [nombre, color]]) => ({ name: nombre, value: monto(k), n: n(k), itemStyle: { color } }))
        .filter((x) => x.value > 0);
    grafica('gEstados', {
        tooltip: { trigger: 'item', backgroundColor: COLORES.navy, borderWidth: 0, textStyle: { color: '#fff', fontSize: 12 },
            formatter: (p) => `${p.name}: ${money(p.value)}<br>${p.data.n} cotización(es) · ${p.percent}%` },
        legend: { bottom: 0, left: 'center', icon: 'circle', itemWidth: 9, textStyle: { color: COLORES.texto, fontSize: 12 } },
        series: [{
            type: 'pie', radius: ['52%', '76%'], center: ['50%', '44%'], avoidLabelOverlap: true,
            itemStyle: { borderColor: '#fff', borderWidth: 2 }, label: { show: false }, data: datos,
        }],
        graphic: [{ type: 'text', left: 'center', top: '36%', style: { text: moneyCorto(datos.reduce((s, x) => s + x.value, 0)), fill: COLORES.navy, fontSize: 18, fontWeight: 700 } },
            { type: 'text', left: 'center', top: '46%', style: { text: 'en cartera', fill: COLORES.texto, fontSize: 11 } }],
    });

    let saldo = 0;
    grafica('gCaja', {
        xAxis: ejeCategorias(d.caja_mes.map((m) => mesCorto(m.mes))),
        yAxis: ejeMontos(),
        series: [
            { name: 'Ingresos', type: 'bar', barMaxWidth: 16, color: COLORES.success, itemStyle: { borderRadius: [3, 3, 0, 0] }, data: d.caja_mes.map((m) => m.ingresos) },
            { name: 'Egresos', type: 'bar', barMaxWidth: 16, color: COLORES.claro, itemStyle: { borderRadius: [3, 3, 0, 0] }, data: d.caja_mes.map((m) => m.egresos) },
            { name: 'Saldo acumulado', type: 'line', smooth: true, symbol: 'circle', symbolSize: 5, color: COLORES.navy,
                data: d.caja_mes.map((m) => (saldo += m.ingresos - m.egresos)) },
        ],
    });

    const tb = document.getElementById('recientes');
    tb.innerHTML = d.recientes.length ? '' : '<tr><td colspan="6" class="vacio-tabla"><i class="bi bi-file-earmark-plus"></i>Aún no hay cotizaciones</td></tr>';
    d.recientes.forEach((c) => {
        const tr = document.createElement('tr');
        tr.className = 'fila-click';
        tr.innerHTML = `<td class="text-nowrap fw-semibold">${esc(c.folio)}</td><td>${esc(c.obra)}</td><td class="text-muted">${esc(c.cliente || '—')}</td><td class="text-nowrap">${fecha(c.fecha_cotizacion)}</td>
            <td><span class="badge estado-${esc(c.estado)}">${esc(c.estado)}</span></td><td class="num fw-semibold">${money(c.total)}</td>`;
        tr.addEventListener('click', () => { window.location.href = 'cotizaciones/' + c.id; });
        tb.appendChild(tr);
    });
}

async function cargarObras() {
    const obras = await api('api/obras');
    const atrasadas = obras.filter((o) => o.pct_ejecutado + 0.005 < o.pct_programado);
    const enCurso = obras.filter((o) => o.pct_ejecutado < 0.995);
    document.getElementById('kAtraso').textContent = obras.length ? `${atrasadas.length} de ${enCurso.length}` : '—';
    const sub = document.getElementById('kAtrasoSub');
    sub.textContent = !obras.length ? 'Sin obras aceptadas' : atrasadas.length ? 'Avance real detrás del programa' : 'Todas al corriente';
    sub.className = 'kpi-sub ' + (obras.length ? (atrasadas.length ? 'mal' : 'bien') : '');
    document.getElementById('kAtrasoIcono').className = 'kpi-icon ' + (atrasadas.length ? 'tono-warning' : 'tono-success');

    // Primero las que van más atrasadas respecto a su programa
    obras.sort((a, b) => (a.pct_ejecutado - a.pct_programado) - (b.pct_ejecutado - b.pct_programado));
    const tb = document.getElementById('obras');
    tb.innerHTML = obras.length ? '' : '<tr><td colspan="5" class="vacio-tabla"><i class="bi bi-cone-striped"></i>Cuando una cotización se acepta aparece aquí como obra</td></tr>';
    obras.slice(0, 8).forEach((o) => {
        const tr = document.createElement('tr');
        tr.className = 'fila-click';
        const caja = o.cobrado - o.gastado;
        tr.innerHTML = `<td><div class="fw-semibold">${esc(o.obra)}</div><div class="small text-muted">${esc(o.folio)}${o.cliente ? ' · ' + esc(o.cliente) : ''}</div></td>
            <td><div class="d-flex align-items-center gap-2">${barraRealVsProgramado(o.pct_ejecutado, o.pct_programado)}<span class="small fw-semibold text-nowrap">${pct(o.pct_ejecutado)}</span></div>
              <div class="small text-muted">programado ${pct(o.pct_programado)}</div></td>
            <td class="num">${moneyCorto(o.cobrado)}</td><td class="num">${moneyCorto(o.gastado)}</td>
            <td class="num fw-semibold ${caja < 0 ? 'text-danger' : ''}">${moneyCorto(caja)}</td>`;
        tr.addEventListener('click', () => { window.location.href = 'cotizaciones/' + o.id; });
        tb.appendChild(tr);
    });
}
</script>
