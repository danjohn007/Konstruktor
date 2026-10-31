<?php
$h = fn($v) => htmlspecialchars((string) ($v ?? ''));
$m = fn($n) => '$' . number_format((float) $n, 2);
$q = fn($n) => number_format((float) $n, 2);
$f = fn($iso) => $iso ? date('d/m/Y', strtotime($iso)) : '';
$c = $est['cotizacion'];
$ubicacion = trim(implode(', ', array_filter([$c['direccion'], $c['municipio']])));
$pct = fn($x) => round((float) $x * 100, 2) . '%';
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<link rel="icon" type="image/png" href="assets/img/logo.png">
<base href="<?= BASE ?>/">
<title>Estimación <?= (int) $est['numero'] ?> · <?= $h($c['folio']) ?></title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<style>
    :root { --azul: #2f75b5; --azul-claro: #ddebf7; }
    body { background: #e9edf3; font-size: 11px; color: #1f2937; }
    .hoja { background: #fff; max-width: 11in; margin: 1.5rem auto; padding: .5in; box-shadow: 0 2px 10px rgba(0,0,0,.08); }
    .empresa { color: var(--azul); font-weight: 700; font-size: 1.4rem; }
    .barra { background: var(--azul); color: #fff; font-weight: 700; text-align: center; padding: .3rem; font-size: 1.05rem; }
    .datos td { padding: .1rem .5rem .1rem 0; }
    .datos .lbl { font-weight: 700; white-space: nowrap; }
    table.est { width: 100%; border-collapse: collapse; }
    table.est thead th { background: var(--azul); color: #fff; padding: .25rem .35rem; font-size: 10px; text-align: center; }
    table.est td { padding: .2rem .35rem; border-bottom: 1px solid #e5e7eb; vertical-align: top; }
    table.est tr.partida td { background: var(--azul-claro); font-weight: 700; }
    .num { text-align: right; white-space: nowrap; font-variant-numeric: tabular-nums; }
    .resumen td { padding: .15rem .5rem; }
    .resumen .total td { background: var(--azul); color: #fff; font-weight: 700; }
    .firmas { display: flex; justify-content: space-around; margin-top: 3rem; }
    .firmas div { border-top: 1px solid #111; width: 230px; text-align: center; padding-top: .3rem; }
    .acciones { max-width: 11in; margin: 1rem auto 0; }
    @media print {
        @page { size: letter landscape; margin: .4in; }
        body { background: #fff; }
        .hoja { box-shadow: none; margin: 0; padding: 0; max-width: none; }
        .acciones { display: none; }
        .barra, table.est thead th, table.est tr.partida td, .resumen .total td { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        table.est tr { page-break-inside: avoid; }
    }
</style>
</head>
<body>
<div class="acciones d-flex justify-content-between">
  <a href="estimaciones/<?= (int) $est['id'] ?>" class="btn btn-sm btn-outline-secondary">← Volver a la estimación</a>
  <button class="btn btn-sm btn-primary" onclick="window.print()">Imprimir / Guardar PDF</button>
</div>
<div class="hoja">
  <div class="d-flex justify-content-between align-items-start mb-2">
    <div>
      <div class="empresa"><?= $h($cfg['empresa_nombre']) ?></div>
      <?php if ($cfg['empresa_rfc']): ?><div>RFC: <?= $h($cfg['empresa_rfc']) ?></div><?php endif; ?>
    </div>
    <img src="assets/img/logo.png" alt="" style="height:52px">
  </div>
  <div class="barra mb-2">ESTIMACIÓN No. <?= (int) $est['numero'] ?></div>

  <div class="d-flex justify-content-between mb-2">
    <table class="datos">
      <tr><td class="lbl">Contrato:</td><td><?= $h($c['folio']) ?></td></tr>
      <tr><td class="lbl">Obra:</td><td><?= $h($c['obra']) ?></td></tr>
      <tr><td class="lbl">Ubicación:</td><td><?= $h($ubicacion) ?></td></tr>
      <tr><td class="lbl">Cliente:</td><td><?= $h($c['cliente_nombre']) ?> <?= $c['cliente_rfc'] ? '· RFC ' . $h($c['cliente_rfc']) : '' ?></td></tr>
    </table>
    <table class="datos">
      <tr><td class="lbl text-end">Fecha:</td><td><?= $f($est['fecha']) ?></td></tr>
      <tr><td class="lbl text-end">Periodo:</td><td><?= $est['periodo_inicio'] ? $f($est['periodo_inicio']) . ' al ' . $f($est['periodo_fin']) : '' ?></td></tr>
      <tr><td class="lbl text-end">Importe contratado:</td><td><?= $m($est['contrato']) ?> + IVA</td></tr>
    </table>
  </div>

  <table class="est mb-2">
    <thead><tr><th>CLAVE</th><th>CONCEPTO</th><th>U.</th><th>P.U.</th><th>CONTRATADO</th><th>ANTERIOR</th><th>ESTA ESTIMACIÓN</th><th>ACUMULADO</th><th>POR EJECUTAR</th><th>IMPORTE</th></tr></thead>
    <tbody>
    <?php foreach ($est['partidas'] as $p): ?>
      <tr class="partida"><td><?= $h($p['clave']) ?></td><td colspan="9"><?= $h($p['nombre']) ?></td></tr>
      <?php foreach ($p['conceptos'] as $k): ?>
      <tr>
        <td><?= $h($k['clave']) ?></td><td><?= $h($k['descripcion']) ?></td><td class="text-center"><?= $h($k['unidad']) ?></td>
        <td class="num"><?= $m($k['pu']) ?></td><td class="num"><?= $q($k['contratado']) ?></td><td class="num"><?= $q($k['anterior']) ?></td>
        <td class="num fw-bold"><?= $q($k['esta']) ?></td><td class="num"><?= $q($k['acumulado']) ?></td><td class="num"><?= $q($k['por_ejecutar']) ?></td>
        <td class="num"><?= $m($k['importe']) ?></td>
      </tr>
      <?php endforeach; ?>
    <?php endforeach; ?>
    </tbody>
  </table>

  <div class="d-flex justify-content-between align-items-start">
    <table class="datos small">
      <tr><td class="lbl">Estimado anterior:</td><td class="num"><?= $m($est['anteriores']['importe']) ?></td></tr>
      <tr><td class="lbl">Esta estimación:</td><td class="num"><?= $m($est['importe']) ?></td></tr>
      <tr><td class="lbl">Estimado acumulado:</td><td class="num"><?= $m($est['anteriores']['importe'] + $est['importe']) ?></td></tr>
      <tr><td class="lbl">Anticipo amortizado acumulado:</td><td class="num"><?= $m($est['anteriores']['amortizacion'] + $est['amortizacion']) ?> de <?= $m($est['anticipo']) ?></td></tr>
      <?php if ($est['notas']): ?><tr><td class="lbl">Notas:</td><td><?= $h($est['notas']) ?></td></tr><?php endif; ?>
    </table>
    <table class="resumen">
      <tr><td class="fw-bold text-end">IMPORTE</td><td class="num" style="width:120px"><?= $m($est['importe']) ?></td></tr>
      <tr><td class="fw-bold text-end">AMORTIZACIÓN ANTICIPO (<?= $pct($c['pct_anticipo']) ?>)</td><td class="num">−<?= $m($est['amortizacion']) ?></td></tr>
      <tr><td class="fw-bold text-end">SUBTOTAL</td><td class="num"><?= $m($est['subtotal']) ?></td></tr>
      <tr><td class="fw-bold text-end">IVA <?= $pct($c['pct_iva']) ?></td><td class="num"><?= $m($est['iva']) ?></td></tr>
      <?php if ((float) $est['retencion'] > 0): ?>
      <tr><td class="fw-bold text-end">FONDO DE GARANTÍA (<?= $pct($c['pct_retencion']) ?>)</td><td class="num">−<?= $m($est['retencion']) ?></td></tr>
      <?php endif; ?>
      <tr class="total"><td class="text-end">NETO A PAGAR</td><td class="num"><?= $m($est['neto']) ?></td></tr>
    </table>
  </div>

  <div class="firmas">
    <div>Elaboró<br><strong><?= $h($cfg['responsable']) ?></strong><br><?= $h($cfg['empresa_nombre']) ?></div>
    <div>Revisó / Autorizó<br><strong>&nbsp;</strong><br><?= $h($c['cliente_nombre']) ?></div>
  </div>
</div>
</body>
</html>
