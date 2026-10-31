<?php $esAdmin = ($user['rol_nombre'] ?? '') === 'Admin'; ?>
<div class="d-flex flex-wrap gap-2 justify-content-between align-items-start mb-3">
  <div>
    <div class="fs-5 fw-semibold" id="hObra"></div>
    <div class="text-muted small" id="hSub"></div>
  </div>
  <div class="d-flex flex-wrap gap-2">
    <button class="btn btn-outline-secondary" id="btnPrecios" title="Copia a los análisis los precios vigentes del catálogo de insumos"><i class="bi bi-arrow-repeat"></i> Actualizar precios</button>
    <a class="btn btn-primary" target="_blank" href="cotizaciones/<?= (int) $cotizacionId ?>/presupuesto"><i class="bi bi-printer"></i> Presupuesto</a>
    <?php if ($esAdmin): ?>
    <button class="btn btn-outline-danger" id="btnEliminarCot" title="Eliminar cotización"><i class="bi bi-trash"></i></button>
    <?php endif; ?>
  </div>
</div>

<div class="row g-3 mb-3">
  <div class="col-sm-6 col-xl-3"><div class="stat-card"><div class="stat-label">Subtotal</div><div class="stat-value fs-4" id="tSubtotal">–</div></div></div>
  <div class="col-sm-6 col-xl-3"><div class="stat-card"><div class="stat-label" id="tIvaLbl">IVA</div><div class="stat-value fs-4" id="tIva">–</div></div></div>
  <div class="col-sm-6 col-xl-3"><div class="stat-card"><div class="stat-label">Total</div><div class="stat-value fs-4" id="tTotal">–</div></div></div>
  <div class="col-sm-6 col-xl-3"><div class="stat-card"><div class="stat-label">Conceptos sin análisis</div><div class="stat-value fs-4" id="tSinAnalisis">–</div></div></div>
</div>

<ul class="nav nav-tabs nav-tabs-scroll mb-3" role="tablist">
  <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabCatalogo" type="button">Catálogo y P.U.</button></li>
  <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabExplosion" type="button">Explosión de insumos</button></li>
  <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabPrograma" type="button">Programa de obra</button></li>
  <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabDatos" type="button">Datos generales</button></li>
  <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabEstimaciones" type="button" id="navEstimaciones">Estimaciones</button></li>
  <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabAvance" type="button" id="navAvance">Avance</button></li>
  <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabControl" type="button" id="navControl">Control de obra</button></li>
  <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabFlujo" type="button" id="navFlujo">Compras y flujo</button></li>
</ul>
<div id="avisoBloqueo" class="alert alert-info py-2 d-none"><i class="bi bi-lock"></i> Cotización <strong>aceptada</strong>: el catálogo, los análisis y los porcentajes quedaron fijos como contrato. Lo ejecutado se registra en <em>Avance</em>, lo cobrado en <em>Estimaciones</em> y los gastos en <em>Control de obra</em>.</div>

<div class="tab-content">
  <!-- Catálogo -->
  <div class="tab-pane fade show active" id="tabCatalogo">
    <div class="mb-2 text-end"><button class="btn btn-sm btn-primary" id="btnNuevaPartida" onclick="abrirPartida()"><i class="bi bi-plus-lg"></i> Partida</button></div>
    <div class="card"><div class="table-responsive">
      <table class="table table-sm table-cotizacion mb-0 align-middle">
        <thead><tr><th style="width:80px">Clave</th><th>Concepto</th><th>U.</th><th class="num">Cantidad</th><th class="num">P.U.</th><th class="num">Importe</th><th style="width:120px"></th></tr></thead>
        <tbody id="tablaCatalogo"></tbody>
      </table>
    </div></div>
    <p class="text-muted small mt-2">Haz clic en un concepto para capturar su análisis de precio unitario y sus números generadores.</p>
  </div>

  <!-- Explosión -->
  <div class="tab-pane fade" id="tabExplosion">
    <div class="card"><div class="table-responsive">
      <table class="table table-sm mb-0">
        <thead><tr><th>Clave</th><th>Descripción</th><th>Tipo</th><th>U.</th><th class="num">Cantidad total</th><th class="num">Precio</th><th class="num">Importe</th></tr></thead>
        <tbody id="tablaExplosion"></tbody>
      </table>
    </div></div>
    <p class="text-muted small mt-2">Costo directo de insumos (sin herramienta menor, indirectos ni utilidad). Sirve para el programa de compras.</p>
  </div>

  <!-- Programa -->
  <div class="tab-pane fade" id="tabPrograma">
    <div class="card p-3">
      <div class="gantt" id="gantt"></div>
      <p class="text-muted small mt-2 mb-0">La duración de cada concepto es cantidad ÷ rendimiento (días). La fecha de inicio de cada concepto se edita en el concepto; si no tiene, usa el inicio de obra.</p>
    </div>
  </div>

  <!-- Datos generales -->
  <div class="tab-pane fade" id="tabDatos">
    <form id="formDatos" class="card p-3">
      <div class="row g-2">
        <div class="col-md-6"><label class="form-label">Obra *</label><input class="form-control" name="obra" required></div>
        <div class="col-md-4"><label class="form-label">Cliente</label><select class="form-select" name="cliente_id"><option value="">— Sin cliente —</option></select></div>
        <div class="col-md-2"><label class="form-label">Estado</label>
          <select class="form-select" name="estado"><option>borrador</option><option>enviada</option><option>aceptada</option><option>rechazada</option></select></div>
        <div class="col-md-6"><label class="form-label">Dirección</label><input class="form-control" name="direccion"></div>
        <div class="col-md-3"><label class="form-label">Municipio</label><input class="form-control" name="municipio"></div>
        <div class="col-md-3"><label class="form-label">Inicio de obra</label><input type="date" class="form-control" name="fecha_inicio"></div>
        <div class="col-md-3"><label class="form-label">Fecha de cotización *</label><input type="date" class="form-control" name="fecha_cotizacion" required></div>
        <div class="col-md-3"><label class="form-label">Vigencia (días)</label><input type="number" min="0" class="form-control" name="vigencia_dias"></div>
        <div class="col-md-3"><label class="form-label">Anticipo %</label><input class="form-control pct" name="pct_anticipo"></div>
        <div class="col-md-3"><label class="form-label">Estimaciones</label><input type="number" min="1" class="form-control" name="num_estimaciones"></div>
        <div class="col-md-3"><label class="form-label">Indirectos %</label><input class="form-control pct" name="pct_indirectos"></div>
        <div class="col-md-3"><label class="form-label">Utilidad %</label><input class="form-control pct" name="pct_utilidad"></div>
        <div class="col-md-3"><label class="form-label">Herramienta menor %</label><input class="form-control pct" name="pct_herramienta"></div>
        <div class="col-md-3"><label class="form-label">IVA %</label><input class="form-control pct" name="pct_iva"></div>
        <div class="col-md-3"><label class="form-label">Fondo de garantía %</label><input class="form-control pct" name="pct_retencion" title="Se retiene del importe de cada estimación"></div>
        <div class="col-12"><label class="form-label">Alcances</label><textarea class="form-control" name="alcances" rows="2"></textarea></div>
        <div class="col-12"><label class="form-label">Exclusiones</label><textarea class="form-control" name="exclusiones" rows="2"></textarea></div>
        <div class="col-12"><label class="form-label">Notas</label><textarea class="form-control" name="notas" rows="2"></textarea></div>
      </div>
      <div class="mt-3 text-end"><span id="datosGuardados" class="text-success me-3 d-none"><i class="bi bi-check-circle"></i> Guardado</span><button class="btn btn-primary">Guardar datos</button></div>
    </form>
  </div>

  <!-- Estimaciones -->
  <div class="tab-pane fade" id="tabEstimaciones">
    <div class="row g-3 mb-3">
      <div class="col-sm-6 col-xl-3"><div class="stat-card"><div class="stat-label">Contrato (sin IVA)</div><div class="stat-value fs-5" id="eContrato">–</div></div></div>
      <div class="col-sm-6 col-xl-3"><div class="stat-card"><div class="stat-label">Estimado acumulado</div><div class="stat-value fs-5" id="eEstimado">–</div></div></div>
      <div class="col-sm-6 col-xl-3"><div class="stat-card"><div class="stat-label">Por estimar</div><div class="stat-value fs-5" id="ePorEstimar">–</div></div></div>
      <div class="col-sm-6 col-xl-3"><div class="stat-card"><div class="stat-label">Anticipo amortizado</div><div class="stat-value fs-5" id="eAmortizado">–</div></div></div>
    </div>
    <div class="d-flex justify-content-between align-items-center mb-2">
      <span class="text-muted small" id="eAviso"></span>
      <button class="btn btn-sm btn-primary" id="btnNuevaEstimacion"><i class="bi bi-plus-lg"></i> Nueva estimación</button>
    </div>
    <div class="card"><div class="table-responsive">
      <table class="table table-sm mb-0">
        <thead><tr><th>No.</th><th>Fecha</th><th>Periodo</th><th>Estado</th><th class="num">Importe</th><th class="num">Amortización</th><th class="num">IVA</th><th class="num">Retención</th><th class="num">Neto a pagar</th></tr></thead>
        <tbody id="tablaEstimaciones"></tbody>
      </table>
    </div></div>
  </div>

  <!-- Avance (hoja AVANCE del Excel) -->
  <div class="tab-pane fade" id="tabAvance">
    <div class="row g-3 mb-3">
      <div class="col-sm-6 col-xl-3"><div class="stat-card"><div class="stat-label">Avance físico / programado a hoy</div><div class="stat-value fs-5" id="aAvance">–</div><div class="small text-muted" id="aAvanceSub"></div></div></div>
      <div class="col-sm-6 col-xl-3"><div class="stat-card"><div class="stat-label">Importe ejecutado</div><div class="stat-value fs-5" id="aEjecutado">–</div><div class="small text-muted" id="aEjecutadoSub"></div></div></div>
      <div class="col-sm-6 col-xl-3"><div class="stat-card"><div class="stat-label">Costo real en conceptos</div><div class="stat-value fs-5" id="aCosto">–</div></div></div>
      <div class="col-sm-6 col-xl-3"><div class="stat-card"><div class="stat-label">Gastos generales (sin concepto)</div><div class="stat-value fs-5" id="aGenerales">–</div><div class="small text-muted" id="aGeneralesSub"></div></div></div>
    </div>
    <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-2">
      <h6 class="mb-0">Avance por concepto</h6>
      <span class="text-muted small" id="aAviso"></span>
      <button class="btn btn-sm btn-primary" id="btnNuevoAvance"><i class="bi bi-plus-lg"></i> Registrar avance</button>
    </div>
    <div class="card mb-3"><div class="table-responsive">
      <table class="table table-sm table-cotizacion mb-0 align-middle">
        <thead><tr><th>Clave</th><th>Concepto</th><th>U.</th><th class="num">Cantidad</th><th class="num">Ejecutado</th><th style="width:16%">Avance</th>
          <th class="num">Costo real</th><th class="num">Diferencia</th>
          <th class="num" title="Costo real por unidad más la parte proporcional de los gastos generales (como en el Excel)">P.U. real <i class="bi bi-info-circle"></i></th></tr></thead>
        <tbody id="tablaAvance"></tbody>
      </table>
    </div></div>
    <h6>Resumen por semana</h6>
    <div class="card mb-3"><div class="table-responsive">
      <table class="table table-sm mb-0">
        <thead><tr><th>Semana</th><th class="num">Programado</th><th class="num">Ejecutado</th><th class="num">Gastado</th><th class="num">Cobrado</th><th class="num">Programado acum.</th><th class="num">Ejecutado acum.</th></tr></thead>
        <tbody id="tablaAvanceSemanas"></tbody>
      </table>
    </div></div>
    <h6>Registros de avance</h6>
    <div class="card"><div class="table-responsive">
      <table class="table table-sm mb-0 align-middle">
        <thead><tr><th>Fecha</th><th>Concepto</th><th class="num">Cantidad</th><th class="num">Importe</th><th>Notas</th><th>Registró</th><th></th></tr></thead>
        <tbody id="tablaRegistros"></tbody>
      </table>
    </div></div>
    <p class="text-muted small mt-2">El importe ejecutado es la cantidad ejecutada por el P.U. contratado. La diferencia es el importe del concepto menos su costo real (los gastos ligados al concepto en <em>Control de obra</em>).</p>
  </div>

  <!-- Control de obra -->
  <div class="tab-pane fade" id="tabControl">
    <div class="row g-3 mb-3">
      <div class="col-sm-6 col-xl-3"><div class="stat-card"><div class="stat-label">Avance estimado / programado a hoy</div><div class="stat-value fs-5" id="kAvance">–</div><div class="small text-muted" id="kAvanceSub"></div></div></div>
      <div class="col-sm-6 col-xl-3"><div class="stat-card"><div class="stat-label">Cobrado</div><div class="stat-value fs-5" id="kCobrado">–</div></div></div>
      <div class="col-sm-6 col-xl-3"><div class="stat-card"><div class="stat-label">Gastado</div><div class="stat-value fs-5" id="kGastado">–</div><div class="small text-muted" id="kGastadoSub"></div></div></div>
      <div class="col-sm-6 col-xl-3"><div class="stat-card"><div class="stat-label">Saldo de caja</div><div class="stat-value fs-5" id="kSaldo">–</div><div class="small text-muted" id="kSaldoSub"></div></div></div>
    </div>
    <div class="row g-3 mb-3">
      <div class="col-lg-6"><div class="card p-3 h-100"><h6>Gasto vs. presupuesto por tipo</h6><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>Tipo</th><th class="num">Presupuesto</th><th class="num">Gastado</th><th style="width:30%">Consumido</th></tr></thead><tbody id="tablaPorTipo"></tbody></table></div></div></div>
      <div class="col-lg-6"><div class="card p-3 h-100"><h6>Gasto vs. presupuesto por partida</h6><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>Partida</th><th class="num">Presupuesto</th><th class="num">Gastado</th><th style="width:30%">Consumido</th></tr></thead><tbody id="tablaPorPartida"></tbody></table></div></div></div>
    </div>
    <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-2">
      <h6 class="mb-0">Movimientos</h6>
      <div class="d-flex gap-2">
        <button class="btn btn-sm btn-outline-success" onclick="abrirMovimiento(null, 'ingreso')"><i class="bi bi-plus-lg"></i> Ingreso</button>
        <button class="btn btn-sm btn-outline-danger" onclick="abrirMovimiento(null, 'egreso')"><i class="bi bi-plus-lg"></i> Egreso</button>
      </div>
    </div>
    <div class="card"><div class="table-responsive">
      <table class="table table-sm mb-0">
        <thead><tr><th>Fecha</th><th>Tipo</th><th>Categoría</th><th>Descripción</th><th>Proveedor / RFC</th><th>Partida / concepto</th><th class="num">Monto</th></tr></thead>
        <tbody id="tablaMovimientos"></tbody>
      </table>
    </div></div>
    <p class="text-muted small mt-2">Captura los montos como se pagaron. El presupuesto de costo es el costo directo más los indirectos de cada concepto; lo que queda del contrato es la utilidad presupuestada (<span id="kUtilidad"></span>). Los retiros de utilidad y los préstamos cuentan en el saldo de caja, pero no como gasto ni como cobro de la obra.</p>
  </div>

  <!-- Compras y flujo (hojas MATERIAL y MANO DE OBRA del Excel) -->
  <div class="tab-pane fade" id="tabFlujo">
    <div id="flujoVacio" class="alert alert-info d-none">Agrega conceptos con su análisis para ver el programa de compras y el flujo de efectivo.</div>
    <div id="flujoContenido">
      <div class="row g-3 mb-3">
        <div class="col-sm-6 col-xl-3"><div class="stat-card"><div class="stat-label">Anticipo</div><div class="stat-value fs-5" id="fAnticipo">–</div></div></div>
        <div class="col-sm-6 col-xl-3"><div class="stat-card"><div class="stat-label">Estimaciones</div><div class="stat-value fs-5" id="fEstimaciones">–</div></div></div>
        <div class="col-sm-6 col-xl-3"><div class="stat-card"><div class="stat-label">Costo programado</div><div class="stat-value fs-5" id="fCosto">–</div><div class="small text-muted" id="fCostoSub"></div></div></div>
        <div class="col-sm-6 col-xl-3"><div class="stat-card"><div class="stat-label">Saldo mínimo programado</div><div class="stat-value fs-5" id="fSaldoMin">–</div><div class="small text-muted" id="fSaldoMinSub"></div></div></div>
      </div>
      <h6>Flujo de efectivo</h6>
      <div class="card mb-3"><div class="table-responsive">
        <table class="table table-sm mb-0 tabla-semanas">
          <thead>
            <tr><th rowspan="2">Semana</th><th colspan="7" class="grupo">Programado</th><th colspan="3" class="grupo">Real</th></tr>
            <tr><th class="num">Ingresos</th><th class="num">Materiales</th><th class="num">Mano de obra</th><th class="num">Equipo</th><th class="num">Indirectos</th><th class="num">Egresos</th><th class="num">Saldo</th>
              <th class="num">Ingresos</th><th class="num">Egresos</th><th class="num">Saldo</th></tr>
          </thead>
          <tbody id="tablaFlujo"></tbody>
        </table>
      </div></div>
      <h6>Programa de compra de material</h6>
      <p class="text-muted small mb-2">Cantidades necesarias por semana según el programa de obra; conviene comprarlas antes de iniciar cada semana.</p>
      <div class="card mb-3"><div class="table-responsive">
        <table class="table table-sm mb-0 tabla-semanas" id="tablaCompras"></table>
      </div></div>
      <h6>Mano de obra por semana</h6>
      <div class="card mb-3"><div class="table-responsive">
        <table class="table table-sm mb-0 tabla-semanas" id="tablaManoObra"></table>
      </div></div>
      <p class="text-muted small">Importes sin IVA. El anticipo entra la semana de inicio y las estimaciones se reparten en partes iguales a lo largo del programa.</p>
    </div>
  </div>
</div>

<!-- Modal avance -->
<div class="modal fade" id="modalAvance" tabindex="-1">
  <div class="modal-dialog modal-fullscreen-sm-down">
    <div class="modal-content">
      <form id="formAvance">
        <div class="modal-header"><h5 class="modal-title" id="avTitulo">Registrar avance</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <input type="hidden" name="id">
          <div class="row g-2">
            <div class="col-12"><label class="form-label">Concepto *</label><select class="form-select" name="concepto_id" required></select><div class="form-text" id="avEjecutado"></div></div>
            <div class="col-6"><label class="form-label">Fecha *</label><input type="date" class="form-control" name="fecha" required></div>
            <div class="col-6"><label class="form-label">Cantidad ejecutada *</label>
              <div class="input-group"><input type="number" step="any" min="0.0001" class="form-control" name="cantidad" required><span class="input-group-text" id="avUnidad"></span></div></div>
            <div class="col-12"><label class="form-label">Notas</label><input class="form-control" name="notas" maxlength="255" placeholder="Ej. muro eje 3 de A a C"></div>
            <div class="col-12"><label class="form-label">Foto</label>
              <div id="avFotoActual" class="small mb-1 d-none"><a target="_blank" id="lnkFotoAvance"><i class="bi bi-image"></i> Ver foto actual</a> (subir otra la reemplaza)</div>
              <input type="file" class="form-control" id="avArchivo" accept="image/*" capture="environment"></div>
          </div>
        </div>
        <div class="modal-footer justify-content-between">
          <button type="button" class="btn btn-outline-danger" id="btnEliminarAvance">Eliminar</button>
          <button class="btn btn-primary">Guardar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal movimiento -->
<div class="modal fade" id="modalMovimiento" tabindex="-1">
  <div class="modal-dialog modal-fullscreen-sm-down">
    <div class="modal-content">
      <form id="formMovimiento">
        <div class="modal-header"><h5 class="modal-title" id="movTitulo">Movimiento</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <input type="hidden" name="id"><input type="hidden" name="tipo">
          <div class="row g-2">
            <div class="col-6"><label class="form-label">Fecha *</label><input type="date" class="form-control" name="fecha" required></div>
            <div class="col-6"><label class="form-label">Monto *</label><input type="number" step="0.01" min="0.01" class="form-control" name="monto" required></div>
            <div class="col-6"><label class="form-label">Categoría</label><select class="form-select" name="categoria"></select></div>
            <div class="col-6 solo-egreso"><label class="form-label">Partida</label><select class="form-select" name="partida_id"><option value="">— General —</option></select></div>
            <div class="col-12 solo-egreso"><label class="form-label">Concepto del catálogo</label><select class="form-select" name="concepto_id"><option value="">— Gasto general (sin concepto) —</option></select></div>
            <div class="col-12"><label class="form-label">Descripción *</label><input class="form-control" name="descripcion" required></div>
            <div class="col-8"><label class="form-label">Proveedor / pagador</label><input class="form-control" name="proveedor"></div>
            <div class="col-4"><label class="form-label">RFC</label><input class="form-control" name="rfc" maxlength="13"></div>
            <div class="col-6"><label class="form-label">Forma de pago</label><select class="form-select" name="forma_pago"><option value="">—</option><option>Transferencia</option><option>Cheque</option><option>Efectivo</option><option>Tarjeta</option></select></div>
            <div class="col-6"><label class="form-label">Referencia / factura</label><input class="form-control" name="referencia"></div>
            <div class="col-12"><label class="form-label">Comprobante (foto o PDF)</label>
              <div id="movComprobante" class="small mb-1 d-none"><a target="_blank" id="lnkComprobante"><i class="bi bi-paperclip"></i> Ver comprobante</a> ·
                <button type="button" class="btn btn-link btn-sm p-0 text-danger align-baseline" id="btnQuitarComprobante">Quitar</button></div>
              <input type="file" class="form-control" id="movArchivo" accept="image/*,application/pdf"></div>
          </div>
        </div>
        <div class="modal-footer justify-content-between">
          <button type="button" class="btn btn-outline-danger" id="btnEliminarMov">Eliminar</button>
          <button class="btn btn-primary">Guardar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal partida -->
<div class="modal fade" id="modalPartida" tabindex="-1">
  <div class="modal-dialog modal-sm">
    <div class="modal-content">
      <form id="formPartida">
        <div class="modal-header"><h5 class="modal-title">Partida</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <input type="hidden" name="id">
          <div class="mb-2"><label class="form-label">Clave *</label><input class="form-control" name="clave" required placeholder="1"></div>
          <div class="mb-2"><label class="form-label">Nombre *</label><input class="form-control" name="nombre" required placeholder="PRELIMINARES"></div>
        </div>
        <div class="modal-footer"><button class="btn btn-primary">Guardar</button></div>
      </form>
    </div>
  </div>
</div>

<!-- Modal concepto -->
<div class="modal fade" id="modalConcepto" tabindex="-1">
  <div class="modal-dialog modal-xl modal-dialog-scrollable modal-fullscreen-sm-down">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title" id="conceptoTitulo">Concepto</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <form id="formConcepto" class="row g-2 mb-3">
          <input type="hidden" name="id"><input type="hidden" name="partida_id">
          <div class="col-md-2"><label class="form-label">Clave *</label><input class="form-control" name="clave" required></div>
          <div class="col-md-2"><label class="form-label">Unidad *</label><input class="form-control" name="unidad" required></div>
          <div class="col-md-2"><label class="form-label">Rendimiento</label><input type="number" step="any" min="0.0001" class="form-control" name="rendimiento" title="Unidades que hace la cuadrilla por jornada"></div>
          <div class="col-md-2"><label class="form-label">Cantidad</label><input type="number" step="any" min="0" class="form-control" name="cantidad"></div>
          <div class="col-md-2"><label class="form-label">Inicio</label><input type="date" class="form-control" name="fecha_inicio"></div>
          <div class="col-md-2 d-flex align-items-end"><div class="form-check mb-2"><input class="form-check-input" type="checkbox" name="usa_volumenes" id="usaVol"><label class="form-check-label small" for="usaVol">Cantidad por generadores</label></div></div>
          <div class="col-12"><label class="form-label">Descripción *</label><textarea class="form-control" name="descripcion" rows="2" required></textarea></div>
          <div class="col-12 d-flex justify-content-between">
            <button type="button" class="btn btn-sm btn-outline-danger" id="btnEliminarConcepto"><i class="bi bi-trash"></i> Eliminar concepto</button>
            <button class="btn btn-sm btn-primary">Guardar concepto</button>
          </div>
        </form>

        <div id="seccionDetalle">
          <div id="seccionVolumenes" class="mb-4">
            <h6>Números generadores</h6>
            <div class="table-responsive"><table class="table table-sm align-middle">
              <thead><tr><th>Localización</th><th>Largo</th><th>Ancho</th><th>Alto</th><th>Piezas</th><th class="num">Resultado</th><th></th></tr></thead>
              <tbody id="tablaVolumenes"></tbody>
              <tfoot><tr><td colspan="5" class="text-end fw-semibold">Cantidad del concepto</td><td class="num fw-semibold" id="volTotal"></td><td></td></tr></tfoot>
            </table></div>
            <button class="btn btn-sm btn-outline-primary" onclick="agregarVolumen()"><i class="bi bi-plus"></i> Renglón</button>
          </div>

          <h6>Análisis de precio unitario <small class="text-muted fw-normal">(consumo por unidad de concepto)</small></h6>
          <div class="row g-3">
            <div class="col-lg-8">
              <div class="table-responsive"><table class="table table-sm align-middle">
                <thead><tr><th>Insumo</th><th>Tipo</th><th>U.</th><th>Cantidad</th><th>Precio</th><th class="num">Importe</th><th></th></tr></thead>
                <tbody id="tablaAnalisis"></tbody>
              </table></div>
              <div class="position-relative" style="max-width:520px">
                <input class="form-control form-control-sm" id="buscarInsumo" placeholder="+ Agregar insumo: escribe clave o descripción..." autocomplete="off">
                <div class="list-group position-absolute w-100 shadow-sm" id="resultadosInsumo" style="z-index:10;max-height:260px;overflow:auto"></div>
              </div>
              <div class="form-text">La mano de obra usa por defecto 1 ÷ rendimiento jornadas por unidad; desmarca “1/R” para capturar otra cantidad.</div>
            </div>
            <div class="col-lg-4">
              <div class="card p-3 bg-light">
                <dl class="row desglose mb-0" id="resumenPU"></dl>
              </div>
            </div>
          </div>
        </div>
        <div id="avisoNuevo" class="alert alert-info d-none">Guarda el concepto para capturar su análisis y sus números generadores.</div>
      </div>
    </div>
  </div>
</div>

<script>
const COT_ID = <?= (int) $cotizacionId ?>;
let cot = null;
let conceptoActual = null;
let modalPartida, modalConcepto, modalMovimiento, modalAvance;
let control = null;
let avance = null;
const NOMBRE_CATEGORIA = {
    MAT: 'Materiales', MO: 'Mano de obra', EQ: 'Equipo y herramienta', SUBCONTRATO: 'Subcontratos',
    INDIRECTO: 'Indirectos', OTRO: 'Otros', ANTICIPO: 'Anticipo', ESTIMACION: 'Estimación',
    UTILIDAD: 'Retiro de utilidad', PRESTAMO: 'Préstamo',
};

document.addEventListener('DOMContentLoaded', async () => {
    modalPartida = new bootstrap.Modal(document.getElementById('modalPartida'));
    modalConcepto = new bootstrap.Modal(document.getElementById('modalConcepto'));
    modalMovimiento = new bootstrap.Modal(document.getElementById('modalMovimiento'));
    document.getElementById('formMovimiento').addEventListener('submit', guardarMovimiento);
    document.getElementById('btnEliminarMov').addEventListener('click', eliminarMovimiento);
    document.getElementById('btnNuevaEstimacion').addEventListener('click', nuevaEstimacion);
    document.getElementById('navEstimaciones').addEventListener('shown.bs.tab', cargarEstimaciones);
    document.getElementById('navControl').addEventListener('shown.bs.tab', cargarControl);
    document.getElementById('navAvance').addEventListener('shown.bs.tab', cargarAvance);
    document.getElementById('navFlujo').addEventListener('shown.bs.tab', cargarFlujo);
    modalAvance = new bootstrap.Modal(document.getElementById('modalAvance'));
    document.getElementById('formAvance').addEventListener('submit', guardarAvance);
    document.getElementById('formAvance').concepto_id.addEventListener('change', infoConceptoAvance);
    document.getElementById('btnNuevoAvance').addEventListener('click', () => abrirAvance(null));
    document.getElementById('btnEliminarAvance').addEventListener('click', eliminarAvance);
    document.querySelector('#formMovimiento [name=concepto_id]').addEventListener('change', sincronizarPartida);
    document.querySelector('#formMovimiento [name=categoria]').addEventListener('change', camposDeCosto);
    document.getElementById('btnQuitarComprobante').addEventListener('click', quitarComprobante);
    document.getElementById('formPartida').addEventListener('submit', guardarPartida);
    document.getElementById('formConcepto').addEventListener('submit', guardarConcepto);
    document.getElementById('formDatos').addEventListener('submit', guardarDatos);
    document.getElementById('btnEliminarConcepto').addEventListener('click', eliminarConcepto);
    document.getElementById('btnPrecios').addEventListener('click', actualizarPrecios);
    document.getElementById('btnEliminarCot')?.addEventListener('click', eliminarCotizacion);
    document.getElementById('usaVol').addEventListener('change', (e) => { document.querySelector('#formConcepto [name=cantidad]').disabled = e.target.checked; });
    const buscar = document.getElementById('buscarInsumo');
    buscar.addEventListener('input', debounce(buscarInsumos, 250));
    buscar.addEventListener('blur', () => setTimeout(() => { document.getElementById('resultadosInsumo').innerHTML = ''; }, 200));

    const sel = document.querySelector('#formDatos [name=cliente_id]');
    (await api('api/clientes')).forEach((c) => sel.insertAdjacentHTML('beforeend', `<option value="${c.id}">${esc(c.nombre)}</option>`));
    await recargar();
    llenarDatos();
});

async function recargar() {
    cot = await api('api/cotizaciones/' + COT_ID);
    renderEncabezado();
    renderCatalogo();
    renderExplosion();
    renderPrograma();
    renderBloqueo();
    if (conceptoActual) {
        const c = buscarConcepto(conceptoActual);
        if (c) renderConceptoDetalle(c);
    }
}

function bloqueada() {
    return cot.estado === 'aceptada';
}

/** Con la cotización aceptada todo lo que modifica el catálogo queda en solo lectura. */
function renderBloqueo() {
    const b = bloqueada();
    document.getElementById('avisoBloqueo').classList.toggle('d-none', !b);
    document.getElementById('btnNuevaPartida').classList.toggle('d-none', b);
    document.getElementById('btnPrecios').classList.toggle('d-none', b);
    document.querySelectorAll('#formDatos .pct').forEach((el) => { el.disabled = b; });
}

function todosLosConceptos() {
    return cot.partidas.flatMap((p) => p.conceptos);
}

function buscarConcepto(id) {
    return todosLosConceptos().find((c) => Number(c.id) === Number(id));
}

function renderEncabezado() {
    const t = cot.totales;
    document.getElementById('hObra').textContent = cot.obra;
    document.getElementById('hSub').innerHTML = `${esc(cot.folio)} · ${esc(cot.cliente_nombre || 'Sin cliente')} · <span class="badge estado-${esc(cot.estado)}">${esc(cot.estado)}</span>`;
    document.getElementById('tSubtotal').textContent = money(t.subtotal);
    document.getElementById('tIvaLbl').textContent = 'IVA ' + pct(cot.pct_iva);
    document.getElementById('tIva').textContent = money(t.iva);
    document.getElementById('tTotal').textContent = money(t.total);
    const sa = document.getElementById('tSinAnalisis');
    sa.textContent = `${t.sin_analisis} de ${t.conceptos}`;
    sa.classList.toggle('text-danger', t.sin_analisis > 0);
}

function renderCatalogo() {
    const tb = document.getElementById('tablaCatalogo');
    tb.innerHTML = '';
    if (!cot.partidas.length) {
        tb.innerHTML = '<tr><td colspan="7" class="text-muted">Agrega una partida (por ejemplo “1 PRELIMINARES”) y después sus conceptos.</td></tr>';
        return;
    }
    cot.partidas.forEach((p) => {
        const tr = document.createElement('tr');
        tr.className = 'partida';
        tr.innerHTML = `<td>${esc(p.clave)}</td><td colspan="4">${esc(p.nombre)}</td><td class="num">${money(p.importe)}</td>
            <td class="text-end text-nowrap">${bloqueada() ? '' : `
              <button class="btn btn-sm btn-link p-1" title="Agregar concepto" data-a="add"><i class="bi bi-plus-circle"></i></button>
              <button class="btn btn-sm btn-link p-1" title="Editar partida" data-a="edit"><i class="bi bi-pencil"></i></button>
              <button class="btn btn-sm btn-link p-1 text-danger" title="Eliminar partida" data-a="del"><i class="bi bi-trash"></i></button>`}</td>`;
        tr.querySelector('[data-a=add]')?.addEventListener('click', () => abrirConcepto(null, p));
        tr.querySelector('[data-a=edit]')?.addEventListener('click', () => abrirPartida(p));
        tr.querySelector('[data-a=del]')?.addEventListener('click', () => eliminarPartida(p));
        tb.appendChild(tr);

        p.conceptos.forEach((c) => {
            const a = c.analisis;
            const r = document.createElement('tr');
            r.className = 'concepto';
            r.innerHTML = `<td>${esc(c.clave)}</td><td class="desc">${esc(c.descripcion)}${a.sin_analisis ? ' <span class="sin-analisis small">· sin análisis</span>' : ''}</td>
                <td>${esc(c.unidad)}</td><td class="num">${numero(a.cantidad)}</td><td class="num">${money(a.pu)}</td><td class="num">${money(a.importe)}</td><td></td>`;
            r.addEventListener('click', () => abrirConcepto(c, p));
            tb.appendChild(r);
        });
    });
}

function renderExplosion() {
    const tb = document.getElementById('tablaExplosion');
    tb.innerHTML = '';
    if (!cot.explosion.length) {
        tb.innerHTML = '<tr><td colspan="7" class="text-muted">Sin insumos en los análisis</td></tr>';
        return;
    }
    const nombres = { MAT: 'Materiales', MO: 'Mano de obra', EQ: 'Equipo' };
    let total = 0;
    ['MAT', 'MO', 'EQ'].forEach((tipo) => {
        const filas = cot.explosion.filter((e) => e.tipo === tipo);
        if (!filas.length) return;
        const sub = filas.reduce((s, e) => s + e.importe, 0);
        total += sub;
        filas.forEach((e) => {
            tb.insertAdjacentHTML('beforeend', `<tr><td class="text-nowrap">${esc(e.clave)}</td><td>${esc(e.descripcion)}</td><td><span class="badge tipo-${tipo}">${tipo}</span></td>
                <td>${esc(e.unidad)}</td><td class="num">${numero(e.cantidad, 4)}</td><td class="num">${money(e.precio)}</td><td class="num">${money(e.importe)}</td></tr>`);
        });
        tb.insertAdjacentHTML('beforeend', `<tr class="table-light"><td colspan="6" class="text-end fw-semibold">Total ${nombres[tipo]}</td><td class="num fw-semibold">${money(sub)}</td></tr>`);
    });
    tb.insertAdjacentHTML('beforeend', `<tr><td colspan="6" class="text-end fw-bold">Total insumos</td><td class="num fw-bold">${money(total)}</td></tr>`);
}

function addDays(iso, n) {
    const d = new Date(iso + 'T00:00:00');
    d.setDate(d.getDate() + n);
    return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
}

function renderPrograma() {
    const cont = document.getElementById('gantt');
    const conceptos = todosLosConceptos();
    if (!conceptos.length) {
        cont.innerHTML = '<p class="text-muted mb-0">Sin conceptos</p>';
        return;
    }
    // Arranca el lunes de la semana del primer concepto.
    const ini = new Date(cot.totales.inicio + 'T00:00:00');
    const lunes = addDays(cot.totales.inicio, -((ini.getDay() + 6) % 7));
    const finIdx = Math.round((new Date(cot.totales.termino + 'T00:00:00') - new Date(lunes + 'T00:00:00')) / 86400000);
    const semanas = Math.min(26, Math.floor(finIdx / 7) + 1);
    const dias = semanas * 7;
    const letras = ['L', 'M', 'M', 'J', 'V', 'S', 'D'];
    const flujo = new Array(semanas).fill(0);

    let h = '<table><thead><tr><th class="etiqueta" rowspan="2">Concepto</th><th rowspan="2" class="px-2">Días</th>';
    for (let s = 0; s < semanas; s++) h += `<th class="semana" colspan="7">${fecha(addDays(lunes, s * 7)).slice(0, 5)}</th>`;
    h += '</tr><tr>';
    for (let d = 0; d < dias; d++) h += `<th class="text-center text-muted fw-normal" style="font-size:.65rem">${letras[d % 7]}</th>`;
    h += '</tr></thead><tbody>';
    conceptos.forEach((c) => {
        const desde = Math.round((new Date(c.inicio + 'T00:00:00') - new Date(lunes + 'T00:00:00')) / 86400000);
        const hasta = Math.round((new Date(c.termino + 'T00:00:00') - new Date(lunes + 'T00:00:00')) / 86400000);
        const porDia = c.analisis.importe / (hasta - desde + 1);
        h += `<tr><td class="etiqueta" title="${esc(c.descripcion)}">${esc(c.clave)} ${esc(String(c.descripcion).slice(0, 38))}</td><td class="num px-2">${numero(c.analisis.dias)}</td>`;
        for (let d = 0; d < dias; d++) {
            const activo = d >= desde && d <= hasta;
            if (activo) flujo[Math.floor(d / 7)] += porDia;
            h += `<td class="dia${activo ? ' activo' : ''}${d % 7 >= 5 ? ' finde' : ''}"></td>`;
        }
        h += '</tr>';
    });
    h += '<tr><td class="etiqueta fw-semibold">Importe programado por semana</td><td></td>';
    flujo.forEach((f) => { h += `<td colspan="7" class="num px-1 small">${f ? money(f) : ''}</td>`; });
    h += '</tr></tbody></table>';
    if (Math.floor(finIdx / 7) + 1 > 26) h += '<p class="small text-muted mt-2">Se muestran las primeras 26 semanas.</p>';
    cont.innerHTML = h;
}

function llenarDatos() {
    const f = document.getElementById('formDatos');
    fillForm(f, cot);
    f.cliente_id.value = cot.cliente_id || '';
    f.querySelectorAll('.pct').forEach((el) => { el.type = 'number'; el.step = 'any'; el.value = +(Number(cot[el.name]) * 100).toFixed(4); });
}

async function guardarDatos(e) {
    e.preventDefault();
    const f = e.target;
    const d = formData(f);
    f.querySelectorAll('.pct').forEach((el) => { d[el.name] = Number(el.value) / 100; });
    try {
        await api('api/cotizaciones/' + COT_ID, 'PUT', d);
        if (d.estado !== cot.estado) {
            // El modo de solo lectura depende del estado: se recarga la página completa.
            window.location.reload();
            return;
        }
        await recargar();
        document.getElementById('datosGuardados').classList.remove('d-none');
        setTimeout(() => document.getElementById('datosGuardados').classList.add('d-none'), 2500);
    } catch (err) {
        alert(err.message);
    }
}

// ---------------------------------------------------------------- partidas

function abrirPartida(p = null) {
    const f = document.getElementById('formPartida');
    f.reset();
    f.id.value = '';
    if (p) fillForm(f, p);
    else f.clave.value = String(cot.partidas.length + 1);
    modalPartida.show();
}

async function guardarPartida(e) {
    e.preventDefault();
    const d = formData(e.target);
    try {
        await api(d.id ? 'api/partidas/' + d.id : `api/cotizaciones/${COT_ID}/partidas`, d.id ? 'PUT' : 'POST', d);
        modalPartida.hide();
        await recargar();
    } catch (err) {
        alert(err.message);
    }
}

async function eliminarPartida(p) {
    if (!confirm(`¿Eliminar la partida ${p.clave} ${p.nombre} y sus ${p.conceptos.length} conceptos?`)) return;
    await api('api/partidas/' + p.id, 'DELETE');
    await recargar();
}

// --------------------------------------------------------------- conceptos

function abrirConcepto(c, p) {
    const f = document.getElementById('formConcepto');
    f.reset();
    f.id.value = '';
    f.partida_id.value = p.id;
    conceptoActual = c ? c.id : null;
    if (c) {
        fillForm(f, c);
        renderConceptoDetalle(c);
    } else {
        f.clave.value = `${p.clave}.${String(p.conceptos.length + 1).padStart(2, '0')}`;
        f.rendimiento.value = 1;
        f.cantidad.value = 0;
        document.getElementById('conceptoTitulo').textContent = `Nuevo concepto · partida ${p.clave} ${p.nombre}`;
    }
    f.cantidad.disabled = f.usa_volumenes.checked;
    document.getElementById('seccionDetalle').classList.toggle('d-none', !c);
    document.getElementById('avisoNuevo').classList.toggle('d-none', !!c);
    document.getElementById('btnEliminarConcepto').classList.toggle('d-none', !c);
    modalConcepto.show();
}

function renderConceptoDetalle(c) {
    const a = c.analisis;
    document.getElementById('conceptoTitulo').textContent = `${c.clave} · ${c.descripcion}`;

    // Números generadores
    document.getElementById('seccionVolumenes').classList.toggle('d-none', !Number(c.usa_volumenes));
    const tv = document.getElementById('tablaVolumenes');
    tv.innerHTML = c.volumenes.length ? '' : '<tr><td colspan="7" class="text-muted small">Sin renglones</td></tr>';
    c.volumenes.forEach((v) => {
        const tr = document.createElement('tr');
        const inp = (k, num = true) => `<input class="form-control form-control-sm${num ? ' input-num' : ''}" data-k="${k}" ${num ? 'type="number" step="any"' : ''} value="${esc(v[k] ?? '')}">`;
        tr.innerHTML = `<td>${inp('localizacion', false)}</td><td>${inp('largo')}</td><td>${inp('ancho')}</td><td>${inp('alto')}</td><td>${inp('piezas')}</td>
            <td class="num">${numero(v.resultado, 4)}</td><td><button class="btn btn-sm btn-link text-danger"><i class="bi bi-x-lg"></i></button></td>`;
        tr.querySelectorAll('input').forEach((i) => i.addEventListener('change', () => guardarVolumen(v.id, tr)));
        tr.querySelector('button').addEventListener('click', () => eliminarVolumen(v.id));
        tv.appendChild(tr);
    });
    document.getElementById('volTotal').textContent = numero(a.cantidad);
    if (Number(c.usa_volumenes)) document.querySelector('#formConcepto [name=cantidad]').value = a.cantidad;

    // Análisis
    const ta = document.getElementById('tablaAnalisis');
    ta.innerHTML = a.lineas.length ? '' : '<tr><td colspan="7" class="text-muted small">Agrega materiales, mano de obra y equipo con el buscador de abajo.</td></tr>';
    a.lineas.forEach((l) => {
        const tr = document.createElement('tr');
        const porRend = Number(l.por_rendimiento) === 1;
        const cambioPrecio = Number(l.precio) !== Number(l.precio_catalogo)
            ? ` <i class="bi bi-exclamation-circle text-warning" title="El catálogo tiene ${money(l.precio_catalogo)}"></i>` : '';
        tr.innerHTML = `<td><div class="small fw-semibold">${esc(l.clave)}</div><div class="small text-muted">${esc(l.descripcion)}</div></td>
            <td><span class="badge tipo-${l.tipo}">${l.tipo}</span></td><td>${esc(l.unidad)}</td>
            <td class="text-nowrap"><input class="form-control form-control-sm input-num d-inline-block" type="number" step="any" min="0" data-k="cantidad"
                    value="${porRend ? +l.cantidad_efectiva.toFixed(6) : esc(l.cantidad)}" ${porRend ? 'disabled' : ''}>
                ${l.tipo === 'MO' ? `<div class="form-check form-check-inline ms-1 me-0"><input class="form-check-input" type="checkbox" data-k="por_rendimiento" ${porRend ? 'checked' : ''} title="Cantidad = 1 / rendimiento"><label class="form-check-label small">1/R</label></div>` : ''}</td>
            <td class="text-nowrap"><input class="form-control form-control-sm input-num d-inline-block" type="number" step="0.01" min="0" data-k="precio" value="${esc(l.precio)}">${cambioPrecio}</td>
            <td class="num">${money(l.importe)}</td>
            <td><button class="btn btn-sm btn-link text-danger" title="Quitar"><i class="bi bi-x-lg"></i></button></td>`;
        tr.querySelectorAll('input').forEach((i) => i.addEventListener('change', () => guardarLinea(l, tr)));
        tr.querySelector('button').addEventListener('click', () => quitarLinea(l.id));
        ta.appendChild(tr);
    });

    const fila = (k, v, cls = '') => `<dt class="col-7 ${cls}">${k}</dt><dd class="col-5 ${cls}">${v}</dd>`;
    document.getElementById('resumenPU').innerHTML =
        fila('Materiales', money(a.materiales)) +
        fila('Mano de obra', money(a.mano_obra)) +
        fila('Equipo', money(a.equipo)) +
        fila(`Herramienta menor (${pct(cot.pct_herramienta)})`, money(a.herramienta)) +
        fila('Costo directo', money(a.costo_directo), 'fw-semibold text-dark') +
        fila(`Indirectos (${pct(cot.pct_indirectos)})`, money(a.indirectos)) +
        fila(`Utilidad (${pct(cot.pct_utilidad)})`, money(a.utilidad)) +
        fila('Precio unitario', money(a.pu), 'fs-5 fw-bold text-dark border-top pt-2') +
        fila(`Importe (${numero(a.cantidad)} ${esc(c.unidad)})`, money(a.importe), 'fw-semibold text-dark') +
        fila('Duración', numero(a.dias) + ' días');

    if (bloqueada()) {
        document.querySelectorAll('#modalConcepto .modal-body input, #modalConcepto .modal-body textarea').forEach((el) => { el.disabled = true; });
        document.querySelectorAll('#modalConcepto .modal-body button').forEach((el) => el.classList.add('d-none'));
    }
}

async function guardarConcepto(e) {
    e.preventDefault();
    const d = formData(e.target);
    try {
        if (d.id) {
            await api('api/conceptos/' + d.id, 'PUT', d);
        } else {
            const r = await api(`api/partidas/${d.partida_id}/conceptos`, 'POST', d);
            conceptoActual = r.id;
            e.target.id.value = r.id;
            document.getElementById('seccionDetalle').classList.remove('d-none');
            document.getElementById('avisoNuevo').classList.add('d-none');
            document.getElementById('btnEliminarConcepto').classList.remove('d-none');
        }
        await recargar();
    } catch (err) {
        alert(err.message);
    }
}

async function eliminarConcepto() {
    const id = document.getElementById('formConcepto').id.value;
    if (!confirm('¿Eliminar este concepto con su análisis y generadores?')) return;
    await api('api/conceptos/' + id, 'DELETE');
    conceptoActual = null;
    modalConcepto.hide();
    await recargar();
}

// ---------------------------------------------------------------- análisis

async function buscarInsumos() {
    const q = document.getElementById('buscarInsumo').value.trim();
    const box = document.getElementById('resultadosInsumo');
    if (q.length < 2) {
        box.innerHTML = '';
        return;
    }
    const data = await api('api/insumos?limit=25&q=' + encodeURIComponent(q));
    box.innerHTML = data.length ? '' : '<div class="list-group-item small text-muted">Sin resultados</div>';
    data.forEach((i) => {
        const b = document.createElement('button');
        b.type = 'button';
        b.className = 'list-group-item list-group-item-action small d-flex justify-content-between gap-2';
        b.innerHTML = `<span><span class="badge tipo-${i.tipo} me-1">${i.tipo}</span><strong>${esc(i.clave)}</strong> ${esc(i.descripcion)}</span><span class="text-nowrap">${money(i.precio)} / ${esc(i.unidad)}</span>`;
        b.addEventListener('mousedown', (ev) => { ev.preventDefault(); agregarInsumo(i); });
        box.appendChild(b);
    });
}

async function agregarInsumo(i) {
    document.getElementById('buscarInsumo').value = '';
    document.getElementById('resultadosInsumo').innerHTML = '';
    try {
        await api(`api/conceptos/${conceptoActual}/insumos`, 'POST', { insumo_id: i.id });
        await recargar();
    } catch (err) {
        alert(err.message);
    }
}

async function guardarLinea(l, tr) {
    const val = (k) => tr.querySelector(`[data-k=${k}]`);
    const porRend = val('por_rendimiento')?.checked ?? false;
    try {
        await api('api/concepto-insumos/' + l.id, 'PUT', {
            // Al desmarcar 1/R se conserva la cantidad que daba el rendimiento.
            cantidad: porRend ? l.cantidad : (val('cantidad').disabled ? l.cantidad_efectiva : val('cantidad').value),
            precio: val('precio').value,
            por_rendimiento: porRend,
        });
        await recargar();
    } catch (err) {
        alert(err.message);
    }
}

async function quitarLinea(id) {
    await api('api/concepto-insumos/' + id, 'DELETE');
    await recargar();
}

// --------------------------------------------------------------- volúmenes

async function agregarVolumen() {
    await api(`api/conceptos/${conceptoActual}/volumenes`, 'POST', { localizacion: '' });
    await recargar();
}

async function guardarVolumen(id, tr) {
    const d = {};
    tr.querySelectorAll('input').forEach((i) => { d[i.dataset.k] = i.value; });
    await api('api/volumenes/' + id, 'PUT', d);
    await recargar();
}

async function eliminarVolumen(id) {
    await api('api/volumenes/' + id, 'DELETE');
    await recargar();
}

// ------------------------------------------------------------- cotización

// ------------------------------------------------------------ estimaciones

async function cargarEstimaciones() {
    const d = await api(`api/cotizaciones/${COT_ID}/estimaciones`);
    document.getElementById('eContrato').textContent = money(d.contrato);
    document.getElementById('eEstimado').textContent = money(d.estimado);
    document.getElementById('ePorEstimar').textContent = money(d.por_estimar);
    document.getElementById('eAmortizado').textContent = `${money(d.amortizado)} de ${money(d.anticipo)}`;
    const aceptada = d.estado_cotizacion === 'aceptada';
    document.getElementById('btnNuevaEstimacion').classList.toggle('d-none', !aceptada);
    document.getElementById('eAviso').textContent = aceptada ? '' : 'Para estimar, marca la cotización como “aceptada” en Datos generales.';
    const tb = document.getElementById('tablaEstimaciones');
    tb.innerHTML = d.estimaciones.length ? '' : '<tr><td colspan="9" class="text-muted">Sin estimaciones</td></tr>';
    d.estimaciones.forEach((e) => {
        const tr = document.createElement('tr');
        tr.style.cursor = 'pointer';
        const periodo = e.periodo_inicio ? `${fecha(e.periodo_inicio)} – ${fecha(e.periodo_fin)}` : '';
        tr.innerHTML = `<td>${e.numero}</td><td>${fecha(e.fecha)}</td><td>${periodo}</td><td><span class="badge estado-${e.estado}">${e.estado}</span></td>
            <td class="num">${money(e.importe)}</td><td class="num">${money(e.amortizacion)}</td><td class="num">${money(e.iva)}</td>
            <td class="num">${money(e.retencion)}</td><td class="num fw-semibold">${money(e.neto)}</td>`;
        tr.addEventListener('click', () => { window.location.href = 'estimaciones/' + e.id; });
        tb.appendChild(tr);
    });
}

async function nuevaEstimacion() {
    try {
        const r = await api(`api/cotizaciones/${COT_ID}/estimaciones`, 'POST', { fecha: hoyISO() });
        window.location.href = 'estimaciones/' + r.id;
    } catch (err) {
        alert(err.message);
    }
}

// ---------------------------------------------------------- control de obra

function barra(gastado, presupuesto) {
    if (!presupuesto) return gastado ? '<span class="small text-danger">sin presupuesto</span>' : '';
    const p = gastado / presupuesto;
    const color = p > 1 ? 'bg-danger' : (p > 0.85 ? 'bg-warning' : 'bg-success');
    return `<div class="progress" style="height:14px" title="${pct(p)}"><div class="progress-bar ${color}" style="width:${Math.min(100, p * 100)}%">${Math.round(p * 100)}%</div></div>`;
}

async function cargarControl() {
    control = await api(`api/cotizaciones/${COT_ID}/control`);
    const k = control;
    document.getElementById('kAvance').textContent = `${pct(k.pct_estimado)} / ${pct(k.pct_programado)}`;
    document.getElementById('kAvanceSub').textContent = k.pct_estimado + 0.0001 < k.pct_programado ? 'Va atrasado respecto al programa' : 'Al corriente con el programa';
    document.getElementById('kCobrado').textContent = money(k.cobrado);
    document.getElementById('kGastado').textContent = money(k.gastado);
    document.getElementById('kGastadoSub').textContent = `de ${money(k.costo_presupuestado)} de costo presupuestado`;
    const saldo = document.getElementById('kSaldo');
    saldo.textContent = money(k.saldo);
    saldo.classList.toggle('text-danger', k.saldo < 0);
    document.getElementById('kSaldoSub').textContent = [
        k.utilidad_retirada ? `Utilidad retirada ${money(k.utilidad_retirada)}` : '',
        k.prestamos_por_recuperar ? `Préstamos por recuperar ${money(k.prestamos_por_recuperar)}` : '',
    ].filter(Boolean).join(' · ');
    document.getElementById('kUtilidad').textContent = money(k.utilidad_presupuestada);

    document.getElementById('tablaPorTipo').innerHTML = k.por_tipo.filter((t) => t.presupuesto || t.gastado)
        .map((t) => `<tr><td>${NOMBRE_CATEGORIA[t.categoria]}</td><td class="num">${money(t.presupuesto)}</td><td class="num">${money(t.gastado)}</td><td>${barra(t.gastado, t.presupuesto)}</td></tr>`).join('')
        || '<tr><td colspan="4" class="text-muted">Sin datos</td></tr>';
    document.getElementById('tablaPorPartida').innerHTML = k.por_partida
        .map((p) => `<tr><td>${esc(p.clave)} ${esc(p.nombre)}</td><td class="num">${money(p.presupuesto)}</td><td class="num">${money(p.gastado)}</td><td>${barra(p.gastado, p.presupuesto)}</td></tr>`).join('')
        + (k.sin_partida ? `<tr><td class="text-muted">Gastos generales (sin partida)</td><td></td><td class="num">${money(k.sin_partida)}</td><td></td></tr>` : '');

    const sel = document.querySelector('#formMovimiento [name=partida_id]');
    sel.length = 1;
    k.partidas.forEach((p) => sel.insertAdjacentHTML('beforeend', `<option value="${p.id}">${esc(p.clave)} ${esc(p.nombre)}</option>`));
    const selC = document.querySelector('#formMovimiento [name=concepto_id]');
    selC.length = 1;
    k.conceptos.forEach((c) => selC.insertAdjacentHTML('beforeend', `<option value="${c.id}">${esc(c.clave)} ${esc(String(c.descripcion).slice(0, 60))}</option>`));

    const tb = document.getElementById('tablaMovimientos');
    tb.innerHTML = k.movimientos.length ? '' : '<tr><td colspan="7" class="text-muted">Sin movimientos</td></tr>';
    k.movimientos.forEach((m) => {
        const tr = document.createElement('tr');
        tr.style.cursor = 'pointer';
        const ingreso = m.tipo === 'ingreso';
        tr.innerHTML = `<td>${fecha(m.fecha)}</td><td><span class="badge ${ingreso ? 'bg-success-subtle text-success-emphasis' : 'bg-danger-subtle text-danger-emphasis'}">${m.tipo}</span></td>
            <td class="small">${NOMBRE_CATEGORIA[m.categoria] || esc(m.categoria)}</td>
            <td>${esc(m.descripcion)}${m.comprobante ? ` <a href="archivos/movimientos/${m.id}" target="_blank" title="Ver comprobante" data-clip><i class="bi bi-paperclip"></i></a>` : ''}</td>
            <td class="small">${esc(m.proveedor)}${m.rfc ? '<br><span class="text-muted">' + esc(m.rfc) + '</span>' : ''}</td>
            <td class="small">${m.partida_clave ? esc(m.partida_clave) + ' ' + esc(m.partida_nombre) : ''}${m.concepto_clave ? `<div class="text-muted">${esc(m.concepto_clave)} ${esc(String(m.concepto_descripcion).slice(0, 40))}</div>` : ''}</td>
            <td class="num ${ingreso ? 'text-success' : ''}">${ingreso ? '' : '−'}${money(m.monto)}</td>`;
        tr.addEventListener('click', () => abrirMovimiento(m));
        tr.querySelector('[data-clip]')?.addEventListener('click', (ev) => ev.stopPropagation());
        tb.appendChild(tr);
    });
}

const CATEGORIAS = {
    ingreso: ['ANTICIPO', 'ESTIMACION', 'PRESTAMO', 'OTRO'],
    egreso: ['MAT', 'MO', 'EQ', 'SUBCONTRATO', 'INDIRECTO', 'OTRO', 'UTILIDAD', 'PRESTAMO'],
};

function abrirMovimiento(m, tipo) {
    if (m && m.estimacion_id) {
        alert(`Este ingreso es el cobro de la estimación ${m.estimacion_numero}; se administra desde la estimación.`);
        return;
    }
    const f = document.getElementById('formMovimiento');
    f.reset();
    f.id.value = '';
    tipo = m ? m.tipo : tipo;
    f.tipo.value = tipo;
    // "Estimación" la crea el cobro de una estimación; solo aparece al editar un ingreso que ya la tiene (p. ej. importado).
    f.categoria.innerHTML = CATEGORIAS[tipo].filter((c) => c !== 'ESTIMACION' || m?.categoria === 'ESTIMACION')
        .map((c) => `<option value="${c}">${NOMBRE_CATEGORIA[c]}</option>`).join('');
    document.getElementById('movTitulo').textContent = (m ? 'Editar ' : 'Nuevo ') + tipo;
    document.getElementById('btnEliminarMov').classList.toggle('d-none', !m);
    if (m) fillForm(f, m);
    else f.fecha.value = hoyISO();
    camposDeCosto();
    sincronizarPartida();
    document.getElementById('movArchivo').value = '';
    document.getElementById('movComprobante').classList.toggle('d-none', !(m && m.comprobante));
    if (m) document.getElementById('lnkComprobante').href = 'archivos/movimientos/' + m.id;
    modalMovimiento.show();
}

/** Un gasto ligado a un concepto va a la partida de ese concepto. */
// Partida y concepto solo aplican a egresos que son costo (no a retiros de utilidad ni préstamos).
function camposDeCosto() {
    const f = document.getElementById('formMovimiento');
    const esCosto = f.tipo.value === 'egreso' && !['UTILIDAD', 'PRESTAMO'].includes(f.categoria.value);
    f.querySelectorAll('.solo-egreso').forEach((el) => el.classList.toggle('d-none', !esCosto));
}

function sincronizarPartida() {
    const f = document.getElementById('formMovimiento');
    const c = (control?.conceptos || []).find((x) => Number(x.id) === Number(f.concepto_id.value));
    f.partida_id.disabled = !!c;
    if (c) f.partida_id.value = c.partida_id;
}

async function quitarComprobante() {
    if (!confirm('¿Quitar el comprobante de este movimiento?')) return;
    try {
        await api(`api/movimientos/${document.getElementById('formMovimiento').id.value}/comprobante`, 'DELETE');
        document.getElementById('movComprobante').classList.add('d-none');
        cargarControl();
    } catch (err) {
        alert(err.message);
    }
}

async function guardarMovimiento(e) {
    e.preventDefault();
    const d = formData(e.target);
    const archivo = document.getElementById('movArchivo').files[0];
    try {
        let id = d.id;
        if (id) {
            await api('api/movimientos/' + id, 'PUT', d);
        } else {
            id = (await api(`api/cotizaciones/${COT_ID}/movimientos`, 'POST', d)).id;
            e.target.id.value = id; // si falla la subida, reintentar no duplica el movimiento
        }
        if (archivo) await apiUpload(`api/movimientos/${id}/comprobante`, archivo);
        modalMovimiento.hide();
        cargarControl();
    } catch (err) {
        alert(err.message);
    }
}

async function eliminarMovimiento() {
    if (!confirm('¿Eliminar este movimiento?')) return;
    try {
        await api('api/movimientos/' + document.getElementById('formMovimiento').id.value, 'DELETE');
        modalMovimiento.hide();
        cargarControl();
    } catch (err) {
        alert(err.message);
    }
}

// ------------------------------------------------------------------ avance

function rangoSemana(s) {
    return `${fecha(s.inicio).slice(0, 5)}–${fecha(s.fin).slice(0, 5)}`;
}

function barraAvance(c) {
    const color = c.atrasado ? 'bg-danger' : (c.pct >= 1 ? 'bg-success' : 'bg-primary');
    return `<div class="progress progress-avance" title="Ejecutado ${pct(c.pct)} · programado a hoy ${pct(c.pct_programado)}">
        <div class="progress-bar ${color}" style="width:${Math.min(100, c.pct * 100)}%"></div>
        <span class="marca-programado" style="left:${Math.min(100, c.pct_programado * 100)}%"></span></div>
        <div class="small text-muted">${pct(c.pct)} · prog. ${pct(c.pct_programado)}</div>`;
}

async function cargarAvance() {
    avance = await api(`api/cotizaciones/${COT_ID}/avance`);
    const a = avance;
    const atrasado = a.pct_ejecutado + 0.0001 < a.pct_programado;
    document.getElementById('aAvance').textContent = `${pct(a.pct_ejecutado)} / ${pct(a.pct_programado)}`;
    const sub = document.getElementById('aAvanceSub');
    sub.textContent = atrasado ? 'Va atrasado respecto al programa' : 'Al corriente con el programa';
    sub.classList.toggle('text-danger', atrasado);
    document.getElementById('aEjecutado').textContent = money(a.ejecutado);
    document.getElementById('aEjecutadoSub').textContent = `de ${money(a.contrato)} contratado`;
    document.getElementById('aCosto').textContent = money(a.costo_real_conceptos);
    document.getElementById('aGenerales').textContent = money(a.gastos_generales);
    document.getElementById('aGeneralesSub').textContent = `${pct(a.pct_generales)} del gasto total`;
    document.getElementById('btnNuevoAvance').classList.toggle('d-none', !bloqueada());
    document.getElementById('aAviso').textContent = bloqueada() ? '' : 'Para registrar avance, marca la cotización como aceptada.';

    // Conceptos agrupados por partida, en el orden del catálogo
    const porId = Object.fromEntries(a.conceptos.map((c) => [c.id, c]));
    const tb = document.getElementById('tablaAvance');
    tb.innerHTML = a.conceptos.length ? '' : '<tr><td colspan="9" class="text-muted">Sin conceptos</td></tr>';
    cot.partidas.forEach((p) => {
        tb.insertAdjacentHTML('beforeend', `<tr class="partida"><td>${esc(p.clave)}</td><td colspan="8">${esc(p.nombre)}</td></tr>`);
        p.conceptos.forEach((pc) => {
            const c = porId[pc.id];
            if (!c) return;
            const tr = document.createElement('tr');
            tr.className = 'concepto';
            tr.innerHTML = `<td>${esc(c.clave)}</td>
                <td class="desc">${esc(c.descripcion)}${c.excedente ? ' <span class="badge bg-warning-subtle text-warning-emphasis">excedente</span>' : ''}
                  ${c.ultimo_avance ? `<div class="small text-muted">Último avance ${fecha(c.ultimo_avance)}</div>` : ''}</td>
                <td>${esc(c.unidad)}</td><td class="num">${numero(c.cantidad)}</td><td class="num">${numero(c.ejecutado)}</td>
                <td>${barraAvance(c)}</td>
                <td class="num">${c.costo_real ? money(c.costo_real) : ''}</td>
                <td class="num ${c.diferencia < 0 ? 'text-danger' : ''}">${c.costo_real ? money(c.diferencia) : ''}</td>
                <td class="num">${c.costo_real && c.pu_real !== null ? money(c.pu_real) : ''}</td>`;
            tr.addEventListener('click', () => abrirAvance(null, c.id));
            tb.appendChild(tr);
        });
    });

    const hoy = hoyISO();
    document.getElementById('tablaAvanceSemanas').innerHTML = a.semanas.map((s) => `<tr class="${s.inicio <= hoy && hoy <= s.fin ? 'semana-actual' : ''}">
        <td class="text-nowrap">Semana ${s.numero} <span class="text-muted small">${rangoSemana(s)}</span></td>
        <td class="num">${money(s.programado)}</td><td class="num">${money(s.ejecutado)}</td><td class="num">${money(s.gastado)}</td><td class="num">${money(s.cobrado)}</td>
        <td class="num">${money(s.programado_acum)}</td><td class="num ${s.ejecutado_acum + 0.01 < s.programado_acum && s.inicio <= hoy ? 'text-danger' : ''}">${money(s.ejecutado_acum)}</td></tr>`).join('')
        || '<tr><td colspan="7" class="text-muted">Sin programa</td></tr>';

    const tr = document.getElementById('tablaRegistros');
    tr.innerHTML = a.registros.length ? '' : '<tr><td colspan="7" class="text-muted">Sin registros de avance</td></tr>';
    a.registros.forEach((r) => {
        const fila = document.createElement('tr');
        fila.className = 'fila-click';
        const pu = porId[r.concepto_id]?.pu || 0;
        fila.innerHTML = `<td class="text-nowrap">${fecha(r.fecha)}</td><td>${esc(r.concepto_clave)} <span class="small text-muted">${esc(String(r.concepto_descripcion).slice(0, 50))}</span></td>
            <td class="num">${numero(r.cantidad)} ${esc(r.unidad)}</td><td class="num">${money(r.cantidad * pu)}</td>
            <td class="small">${esc(r.notas)}</td><td class="small">${esc(r.usuario)}</td>
            <td>${r.foto ? `<a href="archivos/avances/${r.id}" target="_blank" title="Ver foto" data-foto><i class="bi bi-image"></i></a>` : ''}</td>`;
        fila.addEventListener('click', () => abrirAvance(r));
        fila.querySelector('[data-foto]')?.addEventListener('click', (ev) => ev.stopPropagation());
        tr.appendChild(fila);
    });
}

function abrirAvance(r, conceptoId = null) {
    if (!r && !bloqueada()) {
        alert('Para registrar avance, marca la cotización como aceptada en Datos generales.');
        return;
    }
    const f = document.getElementById('formAvance');
    f.reset();
    f.id.value = '';
    f.concepto_id.innerHTML = avance.conceptos.map((c) => `<option value="${c.id}">${esc(c.clave)} ${esc(String(c.descripcion).slice(0, 60))} (${esc(c.unidad)})</option>`).join('');
    if (r) {
        fillForm(f, r);
        f.cantidad.value = Number(r.cantidad);
    } else {
        f.fecha.value = hoyISO();
        if (conceptoId) f.concepto_id.value = conceptoId;
    }
    f.fecha.max = hoyISO();
    document.getElementById('avArchivo').value = '';
    document.getElementById('avFotoActual').classList.toggle('d-none', !(r && r.foto));
    if (r) document.getElementById('lnkFotoAvance').href = 'archivos/avances/' + r.id;
    document.getElementById('avTitulo').textContent = r ? 'Editar avance' : 'Registrar avance';
    document.getElementById('btnEliminarAvance').classList.toggle('d-none', !r);
    infoConceptoAvance();
    modalAvance.show();
}

function infoConceptoAvance() {
    const sel = document.getElementById('formAvance').concepto_id;
    const c = avance.conceptos.find((x) => x.id === Number(sel.value));
    document.getElementById('avEjecutado').textContent = c
        ? `Ejecutado ${numero(c.ejecutado)} de ${numero(c.cantidad)} ${c.unidad} · programado a hoy ${numero(c.programado)}` : '';
    document.getElementById('avUnidad').textContent = c ? c.unidad : '';
}

async function guardarAvance(e) {
    e.preventDefault();
    const d = formData(e.target);
    const archivo = document.getElementById('avArchivo').files[0];
    try {
        let id = d.id;
        if (id) {
            await api('api/avances/' + id, 'PUT', d);
        } else {
            id = (await api(`api/cotizaciones/${COT_ID}/avances`, 'POST', d)).id;
            e.target.id.value = id; // si falla la foto, reintentar no duplica el avance
        }
        if (archivo) await apiUpload(`api/avances/${id}/foto`, archivo);
        modalAvance.hide();
        cargarAvance();
    } catch (err) {
        alert(err.message);
    }
}

async function eliminarAvance() {
    if (!confirm('¿Eliminar este registro de avance?')) return;
    try {
        await api('api/avances/' + document.getElementById('formAvance').id.value, 'DELETE');
        modalAvance.hide();
        cargarAvance();
    } catch (err) {
        alert(err.message);
    }
}

// ------------------------------------------------------- compras y flujo

/** Tabla de cantidades por semana (programa de compra de material o de mano de obra). */
function tablaPorSemana(id, filas, semanas, titulo, vacio) {
    const tabla = document.getElementById(id);
    if (!filas.length) {
        tabla.innerHTML = `<tbody><tr><td class="text-muted">${vacio}</td></tr></tbody>`;
        return;
    }
    let h = `<thead><tr><th class="fija">${titulo}</th><th>U.</th><th class="num">Total</th>`;
    semanas.forEach((s) => { h += `<th class="num text-nowrap">S${s.numero} <span class="fw-normal">${fecha(s.inicio).slice(0, 5)}</span></th>`; });
    h += '<th class="num">Importe</th></tr></thead><tbody>';
    filas.forEach((m) => {
        h += `<tr><td class="fija"><div class="small fw-semibold">${esc(m.clave)}</div><div class="small text-muted text-truncate">${esc(m.descripcion)}</div></td>
            <td>${esc(m.unidad)}</td><td class="num fw-semibold">${numero(m.cantidad)}</td>`;
        m.semanas.forEach((q) => { h += q ? `<td class="num">${numero(q)}</td>` : '<td class="num vacio">·</td>'; });
        h += `<td class="num">${money(m.importe)}</td></tr>`;
    });
    tabla.innerHTML = h + '</tbody>';
}

async function cargarFlujo() {
    const f = await api(`api/cotizaciones/${COT_ID}/flujo`);
    const vacio = !f.totales;
    document.getElementById('flujoVacio').classList.toggle('d-none', !vacio);
    document.getElementById('flujoContenido').classList.toggle('d-none', vacio);
    if (vacio) return;

    const t = f.totales;
    const costo = t.materiales + t.mano_obra + t.equipo + t.indirectos;
    document.getElementById('fAnticipo').textContent = money(t.anticipo);
    document.getElementById('fEstimaciones').textContent = `${t.num_estimaciones} × ${money(t.por_estimacion)}`;
    document.getElementById('fCosto').textContent = money(costo);
    document.getElementById('fCostoSub').textContent = `de ${money(t.venta)} de venta`;
    const sm = document.getElementById('fSaldoMin');
    sm.textContent = money(t.saldo_minimo);
    sm.classList.toggle('text-danger', t.saldo_minimo < 0);
    document.getElementById('fSaldoMinSub').textContent = t.saldo_minimo < 0
        ? 'Financiamiento necesario' : 'El anticipo y las estimaciones cubren los egresos';

    const hoy = hoyISO();
    const rojo = (n) => (n < 0 ? 'text-danger' : '');
    const suma = (k) => f.semanas.reduce((s, w) => s + w[k], 0);
    const ultima = f.semanas[f.semanas.length - 1];
    document.getElementById('tablaFlujo').innerHTML = f.semanas.map((s) => `<tr class="${s.inicio <= hoy && hoy <= s.fin ? 'semana-actual' : ''}">
        <td class="text-nowrap">S${s.numero} <span class="text-muted small">${rangoSemana(s)}</span></td>
        <td class="num">${money(s.ingresos)}</td><td class="num">${money(s.materiales)}</td><td class="num">${money(s.mano_obra)}</td>
        <td class="num">${money(s.equipo)}</td><td class="num">${money(s.indirectos)}</td><td class="num">${money(s.egresos)}</td>
        <td class="num fw-semibold ${rojo(s.saldo)}">${money(s.saldo)}</td>
        <td class="num">${money(s.real_ingresos)}</td><td class="num">${money(s.real_egresos)}</td><td class="num fw-semibold ${rojo(s.real_saldo)}">${money(s.real_saldo)}</td></tr>`).join('')
        + `<tr class="table-light fw-semibold"><td>Total</td>
        ${['ingresos', 'materiales', 'mano_obra', 'equipo', 'indirectos', 'egresos'].map((k) => `<td class="num">${money(suma(k))}</td>`).join('')}
        <td class="num ${rojo(ultima.saldo)}">${money(ultima.saldo)}</td>
        <td class="num">${money(suma('real_ingresos'))}</td><td class="num">${money(suma('real_egresos'))}</td><td class="num ${rojo(ultima.real_saldo)}">${money(ultima.real_saldo)}</td></tr>`;

    tablaPorSemana('tablaCompras', f.materiales, f.semanas, 'Material', 'Los análisis no tienen materiales');
    tablaPorSemana('tablaManoObra', f.mano_obra, f.semanas, 'Cuadrilla (jornadas)', 'Los análisis no tienen mano de obra');
}

async function actualizarPrecios() {
    if (!confirm('¿Copiar a todos los análisis los precios vigentes del catálogo de insumos? Esto cambia el presupuesto.')) return;
    const r = await api(`api/cotizaciones/${COT_ID}/actualizar-precios`, 'POST', {});
    await recargar();
    alert(`Precios actualizados en ${r.actualizados} renglones de análisis.`);
}

async function eliminarCotizacion() {
    if (!confirm(`¿Eliminar la cotización ${cot.folio}? No se puede deshacer.`)) return;
    await api('api/cotizaciones/' + COT_ID, 'DELETE');
    window.location.href = 'cotizaciones';
}
</script>
