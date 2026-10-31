<form id="formConfig" class="card">
  <div class="px-3 pt-2">
    <ul class="nav nav-tabs nav-tabs-scroll" role="tablist">
      <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#cfgEmpresa" type="button"><i class="bi bi-building me-1"></i>Empresa</button></li>
      <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#cfgCotizaciones" type="button"><i class="bi bi-file-earmark-spreadsheet me-1"></i>Cotizaciones</button></li>
      <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#cfgNomina" type="button"><i class="bi bi-person-gear me-1"></i>Nómina e insumos</button></li>
      <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#cfgBase" type="button" id="navBase"><i class="bi bi-database me-1"></i>Base de datos <span class="cuenta d-none" id="cuentaPendientes"></span></button></li>
    </ul>
  </div>
  <div class="tab-content p-3 p-lg-4">
    <div class="tab-pane fade show active" id="cfgEmpresa">
      <p class="text-muted small mb-3">Aparecen en el encabezado del presupuesto y de las estimaciones impresas.</p>
      <div class="row g-3" style="max-width:900px">
        <div class="col-md-8"><label class="form-label">Razón social</label><input class="form-control" name="empresa_nombre"></div>
        <div class="col-md-4"><label class="form-label">RFC</label><input class="form-control" name="empresa_rfc"></div>
        <div class="col-md-8"><label class="form-label">Dirección</label><input class="form-control" name="empresa_direccion"></div>
        <div class="col-md-4"><label class="form-label">Teléfono</label><input class="form-control" name="empresa_telefono"></div>
        <div class="col-md-6"><label class="form-label">Correo</label><input class="form-control" name="empresa_email"></div>
        <div class="col-md-6"><label class="form-label">Prefijo de folio</label><input class="form-control" name="folio_prefijo"><div class="form-text">Ej. KON → KON-26-001</div></div>
        <div class="col-md-6"><label class="form-label">Responsable que firma</label><input class="form-control" name="responsable"></div>
        <div class="col-md-6"><label class="form-label">Cargo</label><input class="form-control" name="cargo"></div>
      </div>
    </div>

    <div class="tab-pane fade" id="cfgCotizaciones">
      <div class="row g-4">
        <div class="col-xl-5">
          <h6>Valores por defecto de cada cotización nueva</h6>
          <div class="row g-2">
            <div class="col-4"><label class="form-label">Indirectos %</label><input class="form-control pct" name="pct_indirectos"></div>
            <div class="col-4"><label class="form-label">Utilidad %</label><input class="form-control pct" name="pct_utilidad"></div>
            <div class="col-4"><label class="form-label">Herramienta %</label><input class="form-control pct" name="pct_herramienta"></div>
            <div class="col-4"><label class="form-label">IVA %</label><input class="form-control pct" name="pct_iva"></div>
            <div class="col-4"><label class="form-label">Anticipo %</label><input class="form-control pct" name="pct_anticipo"></div>
            <div class="col-4"><label class="form-label">Fondo de garantía %</label><input class="form-control pct" name="pct_retencion"></div>
            <div class="col-4"><label class="form-label">Estimaciones</label><input class="form-control" name="num_estimaciones" type="number" min="1"></div>
            <div class="col-4"><label class="form-label">Vigencia (días)</label><input class="form-control" name="vigencia_dias" type="number" min="0"></div>
          </div>
          <div class="form-text mt-2">La herramienta menor se calcula sobre materiales + mano de obra. Cambiar estos valores no altera cotizaciones existentes.</div>
        </div>
        <div class="col-xl-7">
          <h6>Textos por defecto del presupuesto</h6>
          <div class="mb-2"><label class="form-label">Alcances</label><textarea class="form-control" name="alcances" rows="2"></textarea></div>
          <div class="mb-2"><label class="form-label">Exclusiones</label><textarea class="form-control" name="exclusiones" rows="2"></textarea></div>
          <div><label class="form-label">Notas</label><textarea class="form-control" name="notas" rows="2"></textarea></div>
        </div>
      </div>
    </div>

    <div class="tab-pane fade" id="cfgNomina">
      <p class="text-muted small mb-3">Al guardar se recalculan las cuadrillas y los insumos marcados “sin IVA”. Los análisis de cotizaciones conservan su precio hasta que pulses “Actualizar precios”.</p>
      <div class="row g-3" style="max-width:900px">
        <div class="col-md-6"><label class="form-label">IVA a insumos cotizados sin IVA %</label><input class="form-control pct" name="pct_iva_insumos"></div>
        <div class="col-md-6"><label class="form-label">Cuotas patronales a incluir %</label><input class="form-control pct" name="pct_cuotas"></div>
        <div class="col-6 col-md-3"><label class="form-label">IMSS %</label><input class="form-control pct" name="tasa_imss"></div>
        <div class="col-6 col-md-3"><label class="form-label">INFONAVIT %</label><input class="form-control pct" name="tasa_infonavit"></div>
        <div class="col-6 col-md-3"><label class="form-label">AFORE %</label><input class="form-control pct" name="tasa_afore"></div>
        <div class="col-6 col-md-3"><label class="form-label">ISN %</label><input class="form-control pct" name="tasa_isn"></div>
        <div class="col-4"><label class="form-label">Días de aguinaldo</label><input class="form-control" name="dias_gratificacion" type="number" step="0.5"></div>
        <div class="col-4"><label class="form-label">Días prima vac.</label><input class="form-control" name="dias_prima_vacacional" type="number" step="0.5"></div>
        <div class="col-4"><label class="form-label">Días por semana</label><input class="form-control" name="dias_semana" type="number" step="0.5"></div>
      </div>
    </div>

    <div class="tab-pane fade" id="cfgBase">
      <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-2">
        <p class="text-muted small mb-0">Al subir una versión nueva del sistema, sus cambios a la base se aplican solos en la siguiente visita. Si alguno falla, aparece aquí con el error.</p>
        <button type="button" class="btn btn-sm btn-outline-primary d-none" id="btnMigrar"><i class="bi bi-arrow-repeat"></i> Aplicar pendientes</button>
      </div>
      <div class="table-responsive border rounded-3"><table class="table table-sm mb-0">
        <thead><tr><th>Migración</th><th>Estado</th></tr></thead>
        <tbody id="tablaMigraciones"></tbody>
      </table></div>

      <div class="kpi mt-3 d-none" id="cardDemo">
        <span class="kpi-icon tono-warning"><i class="bi bi-cone-striped"></i></span>
        <div class="kpi-body flex-grow-1">
          <div class="kpi-label">Datos de prueba</div>
          <div class="kpi-value" style="font-size:1.05rem" id="demoResumen"></div>
          <div class="kpi-sub">Usuarios <strong>admin@</strong>, <strong>cotizador@</strong> e <strong>ingeniero@demo.konstruktor.mx</strong> con la contraseña <strong>Demo2026!</strong>
            Bórralos cuando termines de probar: el repositorio es público y cualquiera podría conocer esa contraseña.</div>
        </div>
        <button type="button" class="btn btn-sm btn-outline-danger align-self-center text-nowrap" id="btnBorrarDemo"><i class="bi bi-trash"></i> Borrar datos de prueba</button>
      </div>
    </div>
  </div>
  <div class="d-flex justify-content-end align-items-center gap-3 px-3 py-2 border-top" id="pieGuardar" style="background:var(--slate-50);border-radius:0 0 .85rem .85rem">
    <span id="guardado" class="text-success small d-none"><i class="bi bi-check-circle"></i> Guardado</span>
    <button class="btn btn-primary">Guardar configuración</button>
  </div>
</form>

<script>
// Los porcentajes se muestran como 12 y se guardan como 0.12.
document.addEventListener('DOMContentLoaded', async () => {
    // La pestaña de base de datos no tiene campos: ahí no se muestra "Guardar".
    document.querySelectorAll('#formConfig [data-bs-toggle="tab"]').forEach((b) => b.addEventListener('shown.bs.tab', () => {
        document.getElementById('pieGuardar').classList.toggle('d-none', b.id === 'navBase');
    }));
    const f = document.getElementById('formConfig');
    const c = await api('api/configuracion');
    fillForm(f, c);
    f.querySelectorAll('.pct').forEach((el) => { el.value = +(Number(el.value) * 100).toFixed(4); el.type = 'number'; el.step = 'any'; });
    f.addEventListener('submit', async (e) => {
        e.preventDefault();
        const d = formData(f);
        f.querySelectorAll('.pct').forEach((el) => { d[el.name] = Number(el.value) / 100; });
        try {
            await api('api/configuracion', 'PUT', d);
            document.getElementById('guardado').classList.remove('d-none');
            setTimeout(() => document.getElementById('guardado').classList.add('d-none'), 2500);
        } catch (err) {
            alert(err.message);
        }
    });

    mostrarMigraciones(await api('api/configuracion/migraciones'));
    cargarDemo();
    document.getElementById('btnBorrarDemo').addEventListener('click', borrarDemo);
    document.getElementById('btnMigrar').addEventListener('click', async () => {
        try {
            const r = await api('api/configuracion/migraciones', 'POST', {});
            mostrarMigraciones(r.migraciones);
            cargarDemo();
            if (r.log.length) alert(r.log.join('\n'));
        } catch (err) {
            alert(err.message);
        }
    });
});

async function cargarDemo() {
    mostrarDemo(await api('api/configuracion/datos-prueba'));
}

function mostrarDemo(d) {
    const hay = d.usuarios + d.clientes + d.cotizaciones > 0;
    document.getElementById('cardDemo').classList.toggle('d-none', !hay);
    document.getElementById('demoResumen').textContent = `${d.usuarios} usuarios · ${d.clientes} clientes · ${d.cotizaciones} cotizaciones`;
}

async function borrarDemo() {
    if (!confirm('¿Borrar los usuarios, clientes y cotizaciones de prueba (con su avance, gastos y estimaciones)? No se puede deshacer.')) return;
    try {
        mostrarDemo(await api('api/configuracion/datos-prueba', 'DELETE'));
    } catch (err) {
        alert(err.message);
    }
}

function mostrarMigraciones(lista) {
    document.getElementById('tablaMigraciones').innerHTML = lista.map((m) => {
        const estado = m.aplicada ? `<span class="text-success"><i class="bi bi-check-circle"></i> Aplicada ${fecha(m.aplicada)}</span>`
            : m.error ? `<span class="text-danger"><i class="bi bi-x-circle"></i> Error: ${esc(m.error)}</span>`
            : '<span class="text-warning-emphasis">Pendiente</span>';
        return `<tr><td class="small">${esc(m.archivo)}</td><td class="small">${estado}</td></tr>`;
    }).join('') || '<tr><td colspan="2" class="text-muted">Sin migraciones</td></tr>';
    const pendientes = lista.filter((m) => !m.aplicada).length;
    document.getElementById('btnMigrar').classList.toggle('d-none', !pendientes);
    const cuenta = document.getElementById('cuentaPendientes');
    cuenta.textContent = pendientes;
    cuenta.classList.toggle('d-none', !pendientes);
}
</script>
