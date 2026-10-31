<div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
  <div class="d-flex flex-wrap gap-2">
    <div class="input-group" style="max-width:300px">
      <span class="input-group-text"><i class="bi bi-search"></i></span>
      <input type="text" id="buscar" class="form-control" placeholder="Clave o descripción...">
    </div>
    <select id="filtroTipo" class="form-select" style="max-width:170px">
      <option value="">Todos los tipos</option>
      <option value="MAT">Materiales</option>
      <option value="MO">Mano de obra</option>
      <option value="EQ">Equipo</option>
    </select>
    <select id="filtroCategoria" class="form-select" style="max-width:280px"><option value="">Todas las categorías</option></select>
  </div>
  <button class="btn btn-primary" onclick="nuevoInsumo()"><i class="bi bi-plus-lg"></i> Nuevo insumo</button>
</div>

<div class="card">
  <div class="table-responsive">
  <table class="table table-sm mb-0">
    <thead><tr><th>Clave</th><th>Descripción</th><th>Tipo</th><th>Categoría</th><th>Unidad</th><th class="num">Precio</th><th></th></tr></thead>
    <tbody id="tabla"><tr><td colspan="7" class="text-muted">Cargando…</td></tr></tbody>
  </table>
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
let modal;

document.addEventListener('DOMContentLoaded', async () => {
    modal = new bootstrap.Modal(document.getElementById('modalInsumo'));
    const cats = await api('api/insumos/categorias');
    const sel = document.getElementById('filtroCategoria');
    const dl = document.getElementById('listaCategorias');
    cats.forEach((c) => {
        sel.insertAdjacentHTML('beforeend', `<option>${esc(c)}</option>`);
        dl.insertAdjacentHTML('beforeend', `<option value="${esc(c)}">`);
    });
    const params = new URLSearchParams(location.search);
    if (params.get('tipo')) document.getElementById('filtroTipo').value = params.get('tipo');
    cargar();
    document.getElementById('buscar').addEventListener('input', debounce(cargar, 300));
    document.getElementById('filtroTipo').addEventListener('change', cargar);
    sel.addEventListener('change', cargar);
    document.getElementById('formInsumo').addEventListener('submit', guardar);
    document.getElementById('btnBaja').addEventListener('click', darBaja);
});

async function cargar() {
    const qs = new URLSearchParams({
        q: document.getElementById('buscar').value,
        tipo: document.getElementById('filtroTipo').value,
        categoria: document.getElementById('filtroCategoria').value,
    });
    const data = await api('api/insumos?' + qs);
    const tb = document.getElementById('tabla');
    tb.innerHTML = data.length ? '' : '<tr><td colspan="7" class="text-muted">Sin resultados</td></tr>';
    data.forEach((i) => {
        const tr = document.createElement('tr');
        const iva = Number(i.mas_iva) ? ' <span class="badge bg-warning-subtle text-warning-emphasis" title="Capturado sin IVA: ' + money(i.precio_captura) + '">+IVA</span>' : '';
        tr.innerHTML = `<td class="text-nowrap">${esc(i.clave)}</td><td>${esc(i.descripcion)}</td>
            <td><span class="badge tipo-${i.tipo}">${i.tipo}</span></td><td class="small text-muted">${esc(i.categoria)}</td>
            <td>${esc(i.unidad)}</td><td class="num">${money(i.precio)}${iva}</td><td class="text-end"></td>`;
        if (i.cuadrilla_id) {
            tr.lastElementChild.innerHTML = '<a class="btn btn-sm btn-outline-secondary" href="cuadrillas" title="Se calcula en Cuadrillas"><i class="bi bi-person-gear"></i></a>';
        } else {
            const btn = document.createElement('button');
            btn.className = 'btn btn-sm btn-outline-secondary';
            btn.innerHTML = '<i class="bi bi-pencil"></i>';
            btn.addEventListener('click', () => editar(i));
            tr.lastElementChild.appendChild(btn);
        }
        tb.appendChild(tr);
    });
}

function nuevoInsumo() {
    const f = document.getElementById('formInsumo');
    f.reset();
    f.id.value = '';
    document.getElementById('btnBaja').classList.add('d-none');
    modal.show();
}

function editar(i) {
    fillForm(document.getElementById('formInsumo'), i);
    document.getElementById('btnBaja').classList.remove('d-none');
    modal.show();
}

async function guardar(e) {
    e.preventDefault();
    const d = formData(e.target);
    try {
        await api(d.id ? `api/insumos/${d.id}` : 'api/insumos', d.id ? 'PUT' : 'POST', d);
        modal.hide();
        cargar();
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
        cargar();
    } catch (err) {
        alert(err.message);
    }
}
</script>
