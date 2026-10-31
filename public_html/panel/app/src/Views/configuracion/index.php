<form id="formConfig">
<div class="row g-3">
  <div class="col-lg-6">
    <div class="card p-3 h-100">
      <h6>Empresa</h6>
      <div class="row g-2">
        <div class="col-12"><label class="form-label">Razón social</label><input class="form-control" name="empresa_nombre"></div>
        <div class="col-6"><label class="form-label">RFC</label><input class="form-control" name="empresa_rfc"></div>
        <div class="col-6"><label class="form-label">Teléfono</label><input class="form-control" name="empresa_telefono"></div>
        <div class="col-12"><label class="form-label">Dirección</label><input class="form-control" name="empresa_direccion"></div>
        <div class="col-12"><label class="form-label">Correo</label><input class="form-control" name="empresa_email"></div>
        <div class="col-6"><label class="form-label">Responsable que firma</label><input class="form-control" name="responsable"></div>
        <div class="col-6"><label class="form-label">Cargo</label><input class="form-control" name="cargo"></div>
        <div class="col-6"><label class="form-label">Prefijo de folio</label><input class="form-control" name="folio_prefijo"><div class="form-text">Ej. KON → KON-26-001</div></div>
      </div>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="card p-3 mb-3">
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
      <div class="form-text">La herramienta menor se calcula sobre materiales + mano de obra. Cambiar estos valores no altera cotizaciones existentes.</div>
    </div>
    <div class="card p-3">
      <h6>Precios de insumos y factor de salario</h6>
      <div class="row g-2">
        <div class="col-6"><label class="form-label">IVA a insumos cotizados sin IVA %</label><input class="form-control pct" name="pct_iva_insumos"></div>
        <div class="col-6"><label class="form-label">Cuotas patronales a incluir %</label><input class="form-control pct" name="pct_cuotas"></div>
        <div class="col-3"><label class="form-label">IMSS %</label><input class="form-control pct" name="tasa_imss"></div>
        <div class="col-3"><label class="form-label">INFONAVIT %</label><input class="form-control pct" name="tasa_infonavit"></div>
        <div class="col-3"><label class="form-label">AFORE %</label><input class="form-control pct" name="tasa_afore"></div>
        <div class="col-3"><label class="form-label">ISN %</label><input class="form-control pct" name="tasa_isn"></div>
        <div class="col-4"><label class="form-label">Días de aguinaldo</label><input class="form-control" name="dias_gratificacion" type="number" step="0.5"></div>
        <div class="col-4"><label class="form-label">Días prima vac.</label><input class="form-control" name="dias_prima_vacacional" type="number" step="0.5"></div>
        <div class="col-4"><label class="form-label">Días por semana</label><input class="form-control" name="dias_semana" type="number" step="0.5"></div>
      </div>
      <div class="form-text">Al guardar se recalculan las cuadrillas y los insumos marcados “sin IVA”. Los análisis de cotizaciones conservan su precio hasta que pulses “Actualizar precios”.</div>
    </div>
  </div>
  <div class="col-12">
    <div class="card p-3">
      <h6>Textos por defecto del presupuesto</h6>
      <div class="mb-2"><label class="form-label">Alcances</label><textarea class="form-control" name="alcances" rows="2"></textarea></div>
      <div class="mb-2"><label class="form-label">Exclusiones</label><textarea class="form-control" name="exclusiones" rows="2"></textarea></div>
      <div class="mb-2"><label class="form-label">Notas</label><textarea class="form-control" name="notas" rows="2"></textarea></div>
    </div>
  </div>
</div>
<div class="mt-3 text-end"><span id="guardado" class="text-success me-3 d-none"><i class="bi bi-check-circle"></i> Guardado</span><button class="btn btn-primary">Guardar configuración</button></div>
</form>

<div class="card p-3 mt-3">
  <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-2">
    <h6 class="mb-0">Base de datos</h6>
    <button class="btn btn-sm btn-outline-primary d-none" id="btnMigrar"><i class="bi bi-arrow-repeat"></i> Aplicar pendientes</button>
  </div>
  <div class="table-responsive"><table class="table table-sm mb-0">
    <thead><tr><th>Migración</th><th>Estado</th></tr></thead>
    <tbody id="tablaMigraciones"></tbody>
  </table></div>
  <div class="form-text">Al subir una versión nueva del sistema, sus cambios a la base se aplican solos en la siguiente visita. Si alguno falla, aparece aquí con el error.</div>
</div>

<script>
// Los porcentajes se muestran como 12 y se guardan como 0.12.
document.addEventListener('DOMContentLoaded', async () => {
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
    document.getElementById('btnMigrar').addEventListener('click', async () => {
        try {
            const r = await api('api/configuracion/migraciones', 'POST', {});
            mostrarMigraciones(r.migraciones);
            if (r.log.length) alert(r.log.join('\n'));
        } catch (err) {
            alert(err.message);
        }
    });
});

function mostrarMigraciones(lista) {
    document.getElementById('tablaMigraciones').innerHTML = lista.map((m) => {
        const estado = m.aplicada ? `<span class="text-success"><i class="bi bi-check-circle"></i> Aplicada ${fecha(m.aplicada)}</span>`
            : m.error ? `<span class="text-danger"><i class="bi bi-x-circle"></i> Error: ${esc(m.error)}</span>`
            : '<span class="text-warning-emphasis">Pendiente</span>';
        return `<tr><td class="small">${esc(m.archivo)}</td><td class="small">${estado}</td></tr>`;
    }).join('') || '<tr><td colspan="2" class="text-muted">Sin migraciones</td></tr>';
    document.getElementById('btnMigrar').classList.toggle('d-none', lista.every((m) => m.aplicada));
}
</script>
