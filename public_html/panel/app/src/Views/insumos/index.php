<div class="row g-3 mb-3">
  <div class="col-6 col-xl-3">
    <div class="kpi"><span class="kpi-icon tono-brand"><i class="bi bi-box-seam"></i></span>
      <div class="kpi-body"><div class="kpi-label">Insumos activos</div><div class="kpi-value" id="kTotal">–</div><div class="kpi-sub" id="kTotalSub"></div></div></div>
  </div>
  <div class="col-6 col-xl-3">
    <div class="kpi"><span class="kpi-icon tono-warning"><i class="bi bi-person-gear"></i></span>
      <div class="kpi-body"><div class="kpi-label">Mano de obra (cuadrillas)</div><div class="kpi-value" id="kMO">–</div><div class="kpi-sub" id="kMOSub"></div></div></div>
  </div>
  <div class="col-6 col-xl-3">
    <button type="button" class="kpi w-100 text-start" id="kRevisarBtn" title="Ver los precios que no se han actualizado en más de 6 meses">
      <span class="kpi-icon tono-danger" id="kRevisarIcono"><i class="bi bi-clock-history"></i></span>
      <div class="kpi-body"><div class="kpi-label">Precios por revisar</div><div class="kpi-value" id="kRevisar">–</div><div class="kpi-sub" id="kRevisarSub">Sin actualizar en más de 6 meses</div></div>
    </button>
  </div>
  <div class="col-6 col-xl-3">
    <div class="kpi"><span class="kpi-icon tono-teal"><i class="bi bi-percent"></i></span>
      <div class="kpi-body"><div class="kpi-label">Capturados sin IVA</div><div class="kpi-value" id="kIva">–</div><div class="kpi-sub">El sistema les suma el IVA configurado</div></div></div>
  </div>
</div>

<div class="card">
  <div class="px-3 pt-2">
    <ul class="nav nav-tabs nav-tabs-scroll" id="tabsTipo">
      <li class="nav-item"><button class="nav-link active" data-tipo="MAT" type="button"><i class="bi bi-bricks me-1"></i>Materiales <span class="cuenta" data-cuenta="MAT">–</span></button></li>
      <li class="nav-item"><button class="nav-link" data-tipo="EQ" type="button"><i class="bi bi-truck me-1"></i>Equipo <span class="cuenta" data-cuenta="EQ">–</span></button></li>
      <li class="nav-item"><button class="nav-link" data-tipo="MO" type="button"><i class="bi bi-person-gear me-1"></i>Mano de obra <span class="cuenta" data-cuenta="MO">–</span></button></li>
      <li class="nav-item"><button class="nav-link" data-tipo="REVISAR" type="button"><i class="bi bi-clock-history me-1"></i>Por revisar <span class="cuenta" data-cuenta="REVISAR">–</span></button></li>
    </ul>
  </div>
  <div class="filtros">
    <div class="buscador"><i class="bi bi-search"></i><input type="text" id="buscar" class="form-control" placeholder="Buscar por clave o descripción…"></div>
    <div class="ms-auto d-flex gap-2">
      <a href="cuadrillas" class="btn btn-outline-secondary d-none" id="btnCuadrillas"><i class="bi bi-person-gear"></i> Ir a Cuadrillas</a>
      <button class="btn btn-primary" onclick="nuevoInsumo()"><i class="bi bi-plus-lg"></i> Nuevo insumo</button>
    </div>
  </div>
  <div class="px-3 pt-3" id="zonaChips"><div class="chips" id="chips"></div></div>
  <div class="table-responsive mt-2">
  <table class="table mb-0">
    <thead><tr><th>Clave</th><th>Descripción</th><th id="thCategoria">Categoría</th><th>Unidad</th><th class="num">Precio</th><th>Actualizado</th><th></th></tr></thead>
    <tbody id="tabla"><tr><td colspan="7" class="vacio-tabla">Cargando…</td></tr></tbody>
  </table>
  </div>
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 px-3 py-2 border-top small text-muted">
    <span id="resumenLista"></span>
    <button class="btn btn-sm btn-outline-secondary d-none" id="btnMas">Mostrar más</button>
  </div>
</div>
<p class="text-muted small mt-2">Los precios marcados con <span class="badge bg-warning-subtle text-warning-emphasis">+IVA</span> se cotizaron sin IVA y el sistema les suma el % configurado. La mano de obra se calcula en <a href="cuadrillas">Cuadrillas</a>.</p>

<div class="modal fade" id="modalInsumo" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="formInsumo">
        <div class="modal-header">
          <h5 class="modal-title">Insumo</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="id">
          <div class="row g-2">
            <div class="col-5"><label class="form-label">Clave *</label><input class="form-control" name="clave" required></div>
            <div class="col-4"><label class="form-label">Tipo</label>
              <select class="form-select" name="tipo"><option value="MAT">Material</option><option value="EQ">Equipo</option></select></div>
            <div class="col-3"><label class="form-label">Unidad *</label><input class="form-control" name="unidad" required></div>
            <div class="col-12"><label class="form-label">Descripción *</label><input class="form-control" name="descripcion" required></div>
            <div class="col-12"><label class="form-label">Categoría</label><input class="form-control" name="categoria" list="listaCategorias"></div>
            <div class="col-6"><label class="form-label">Precio *</label><input type="number" step="0.01" min="0" class="form-control" name="precio_captura" required></div>
            <div class="col-6 d-flex align-items-end">
              <div class="form-check mb-2"><input class="form-check-input" type="checkbox" name="mas_iva" id="masIva">
                <label class="form-check-label" for="masIva">El precio es sin IVA</label></div>
            </div>
          </div>
          <datalist id="listaCategorias"></datalist>
        </div>
        <div class="modal-footer justify-content-between">
          <button type="button" class="btn btn-outline-danger" id="btnBaja">Dar de baja</button>
          <button type="submit" class="btn btn-primary">Guardar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
const POR_PAGINA = 50;
const DIAS_REVISAR = 180;
let modal;
let insumos = [];
let tipo = 'MAT';
let categoria = '';
let mostrar = POR_PAGINA;

document.addEventListener('DOMContentLoaded', async () => {
    modal = new bootstrap.Modal(document.getElementById('modalInsumo'));
    const params = new URLSearchParams(location.search);
    if (['MAT', 'EQ', 'MO'].includes(params.get('tipo'))) tipo = params.get('tipo');
    document.querySelectorAll('#tabsTipo [data-tipo]').forEach((b) => b.addEventListener('click', () => cambiarTipo(b.dataset.tipo)));
    document.getElementById('kRevisarBtn').addEventListener('click', () => cambiarTipo('REVISAR'));
    document.getElementById('buscar').addEventListener('input', debounce(() => { mostrar = POR_PAGINA; pintar(); }, 200));
    document.getElementById('btnMas').addEventListener('click', () => { mostrar += POR_PAGINA; pintar(); });
    document.getElementById('formInsumo').addEventListener('submit', guardar);
    document.getElementById('btnBaja').addEventListener('click', darBaja);
    await cargar();
    cambiarTipo(tipo);
});

/** Se trae el catálogo completo una vez; pestañas, categorías y búsqueda filtran en el navegador. */
async function cargar() {
    insumos = await api('api/insumos?limit=2000');
    const hace = Date.now() - DIAS_REVISAR * 86400000;
    insumos.forEach((i) => { i.revisar = !i.cuadrilla_id && new Date(String(i.updated_at).replace(' ', 'T')).getTime() < hace; });

    const cuenta = (f) => insumos.filter(f).length;
    const n = { MAT: cuenta((i) => i.tipo === 'MAT'), EQ: cuenta((i) => i.tipo === 'EQ'), MO: cuenta((i) => i.tipo === 'MO'), REVISAR: cuenta((i) => i.revisar) };
    Object.entries(n).forEach(([k, v]) => { document.querySelector(`[data-cuenta="${k}"]`).textContent = v; });

    document.getElementById('kTotal').textContent = insumos.length;
    document.getElementById('kTotalSub').textContent = `${n.MAT} materiales · ${n.EQ} equipos`;
    const mo = insumos.filter((i) => i.tipo === 'MO');
    document.getElementById('kMO').textContent = n.MO;
    document.getElementById('kMOSub').textContent = mo.length ? `Jornada de ${money(Math.min(...mo.map((i) => +i.precio)))} a ${money(Math.max(...mo.map((i) => +i.precio)))}` : 'Sin cuadrillas';
    document.getElementById('kRevisar').textContent = n.REVISAR;
    document.getElementById('kRevisarIcono').className = 'kpi-icon ' + (n.REVISAR ? 'tono-danger' : 'tono-success');
    document.getElementById('kIva').textContent = cuenta((i) => Number(i.mas_iva));

    const cats = [...new Set(insumos.map((i) => i.categoria).filter(Boolean))].sort();
    document.getElementById('listaCategorias').innerHTML = cats.map((c) => `<option value="${esc(c)}">`).join('');
}

function cambiarTipo(t) {
    tipo = t;
    categoria = '';
    mostrar = POR_PAGINA;
    document.querySelectorAll('#tabsTipo [data-tipo]').forEach((b) => b.classList.toggle('active', b.dataset.tipo === t));
    document.getElementById('btnCuadrillas').classList.toggle('d-none', t !== 'MO');
    pintarChips();
    pintar();
}

function delTipo() {
    return insumos.filter((i) => (tipo === 'REVISAR' ? i.revisar : i.tipo === tipo));
}

/** Una píldora por categoría del tipo elegido, con cuántos insumos tiene. */
function pintarChips() {
    const conteo = {};
    delTipo().forEach((i) => { const c = i.categoria || 'Sin categoría'; conteo[c] = (conteo[c] || 0) + 1; });
    const cats = Object.entries(conteo).sort((a, b) => b[1] - a[1]);
    document.getElementById('zonaChips').classList.toggle('d-none', cats.length < 2);
    const chips = document.getElementById('chips');
    chips.innerHTML = '';
    [['', 'Todas', delTipo().length], ...cats.map(([c, n]) => [c, titulo(c), n])].forEach(([valor, texto, n]) => {
        const b = document.createElement('button');
        b.type = 'button';
        b.className = 'chip' + (valor === categoria ? ' active' : '');
        b.innerHTML = `${esc(texto)} <span class="cuenta">${n}</span>`;
        b.addEventListener('click', () => { categoria = valor; mostrar = POR_PAGINA; pintarChips(); pintar(); });
        chips.appendChild(b);
    });
}

/** "INSTALACIONES HIDRAULICAS" → "Instalaciones hidraulicas" para que las píldoras no griten. */
function titulo(s) {
    return s.charAt(0) + s.slice(1).toLowerCase();
}

function pintar() {
    const q = document.getElementById('buscar').value.trim().toLowerCase();
    const lista = delTipo().filter((i) => (!categoria || (i.categoria || 'Sin categoría') === categoria)
        && (!q || i.clave.toLowerCase().includes(q) || i.descripcion.toLowerCase().includes(q)));
    document.getElementById('thCategoria').classList.toggle('d-none', !!categoria);

    const tb = document.getElementById('tabla');
    tb.innerHTML = lista.length ? '' : '<tr><td colspan="7" class="vacio-tabla"><i class="bi bi-search"></i>Sin resultados</td></tr>';
    lista.slice(0, mostrar).forEach((i) => {
        const tr = document.createElement('tr');
        const iva = Number(i.mas_iva) ? ' <span class="badge bg-warning-subtle text-warning-emphasis" title="Capturado sin IVA: ' + money(i.precio_captura) + '">+IVA</span>' : '';
        const actualizado = i.cuadrilla_id ? '<span class="text-muted small">Según cuadrilla</span>'
            : `<span class="small ${i.revisar ? 'text-danger fw-semibold' : 'text-muted'}">${i.revisar ? '<i class="bi bi-clock-history"></i> ' : ''}${fecha(i.updated_at)}</span>`;
        tr.innerHTML = `<td class="text-nowrap fw-semibold">${esc(i.clave)}</td><td>${esc(i.descripcion)}</td>
            <td class="small text-muted${categoria ? ' d-none' : ''}">${esc(i.categoria ? titulo(i.categoria) : '')}</td>
            <td>${esc(i.unidad)}</td><td class="num fw-semibold">${money(i.precio)}${iva}</td><td>${actualizado}</td><td class="text-end"></td>`;
        if (i.cuadrilla_id) {
            tr.lastElementChild.innerHTML = '<a class="btn btn-sm btn-outline-secondary" href="cuadrillas" title="Se calcula en Cuadrillas"><i class="bi bi-person-gear"></i></a>';
        } else {
            const btn = document.createElement('button');
            btn.className = 'btn btn-sm btn-outline-secondary';
            btn.title = 'Editar';
            btn.innerHTML = '<i class="bi bi-pencil"></i>';
            btn.addEventListener('click', () => editar(i));
            tr.lastElementChild.appendChild(btn);
        }
        tb.appendChild(tr);
    });
    document.getElementById('resumenLista').textContent = lista.length ? `Mostrando ${Math.min(mostrar, lista.length)} de ${lista.length}` : '';
    document.getElementById('btnMas').classList.toggle('d-none', lista.length <= mostrar);
}

function nuevoInsumo() {
    const f = document.getElementById('formInsumo');
    f.reset();
    f.id.value = '';
    if (tipo === 'EQ') f.tipo.value = 'EQ';
    if (categoria) f.categoria.value = categoria;
    document.getElementById('btnBaja').classList.add('d-none');
    modal.show();
}

function editar(i) {
    fillForm(document.getElementById('formInsumo'), i);
    document.getElementById('btnBaja').classList.remove('d-none');
    modal.show();
}

async function recargar() {
    await cargar();
    pintarChips();
    pintar();
}

async function guardar(e) {
    e.preventDefault();
    const d = formData(e.target);
    try {
        await api(d.id ? `api/insumos/${d.id}` : 'api/insumos', d.id ? 'PUT' : 'POST', d);
        modal.hide();
        recargar();
    } catch (err) {
        alert(err.message);
    }
}

async function darBaja() {
    const id = document.getElementById('formInsumo').id.value;
    if (!confirm('¿Dar de baja este insumo? Las cotizaciones que ya lo usan no cambian.')) return;
    try {
        await api(`api/insumos/${id}`, 'DELETE');
        modal.hide();
        recargar();
    } catch (err) {
        alert(err.message);
    }
}
</script>
