<?php
$h = fn($v) => htmlspecialchars((string) ($v ?? ''));
$m = fn($n) => '$' . number_format((float) $n, 2);
$f = fn($iso) => $iso ? date('d/m/Y', strtotime($iso)) : '';
$t = $cot['totales'];
$plazo = $t['inicio'] && $cot['fecha_inicio'] ? (int) ((strtotime($t['termino']) - strtotime($t['inicio'])) / 86400) + 1 : null;
$ubicacion = trim(implode(', ', array_filter([$cot['direccion'], $cot['municipio']])));
$formaPago = 'Anticipo del ' . round($cot['pct_anticipo'] * 100, 2) . '% a la firma del contrato; el saldo en '
    . (int) $cot['num_estimaciones'] . ' estimación(es) conforme al avance de obra.';
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<link rel="icon" type="image/png" href="assets/img/logo.png">
<base href="<?= BASE ?>/">
<title>Presupuesto <?= $h($cot['folio']) ?></title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<style>
    :root { --azul: #2f75b5; --azul-claro: #ddebf7; }
    body { background: #e9edf3; font-size: 12px; color: #1f2937; }
    .hoja { background: #fff; max-width: 8.5in; margin: 1.5rem auto; padding: .6in .55in; box-shadow: 0 2px 10px rgba(0,0,0,.08); }
    .empresa { color: var(--azul); font-weight: 700; font-size: 1.5rem; }
    .barra { background: var(--azul); color: #fff; font-weight: 700; text-align: center; padding: .3rem; letter-spacing: .03em; }
    .barra.titulo { font-size: 1.15rem; }
    .datos td { padding: .1rem .4rem .1rem 0; vertical-align: top; }
    .datos .lbl { font-weight: 700; white-space: nowrap; }
    table.partidas { width: 100%; border-collapse: collapse; }
    table.partidas thead th { background: var(--azul); color: #fff; padding: .3rem .4rem; font-size: 11px; text-align: center; }
    table.partidas td { padding: .25rem .4rem; border-bottom: 1px solid #e5e7eb; vertical-align: top; }
    table.partidas tr.partida td { background: var(--azul-claro); font-weight: 700; }
    .num { text-align: right; white-space: nowrap; font-variant-numeric: tabular-nums; }
    .totales td { padding: .15rem .4rem; }
    .totales .total td { background: var(--azul); color: #fff; font-weight: 700; }
    .firma { border-top: 1px solid #111; width: 260px; margin: 3.2rem auto 0; text-align: center; padding-top: .3rem; }
    .acciones { max-width: 8.5in; margin: 1rem auto 0; }
    @media print {
        @page { size: letter; margin: .45in; }
        body { background: #fff; }
        .hoja { box-shadow: none; margin: 0; padding: 0; max-width: none; }
        .acciones { display: none; }
        .barra, table.partidas thead th, table.partidas tr.partida td, .totales .total td { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        table.partidas tr { page-break-inside: avoid; }
    }
</style>
</head>
<body>
<div class="acciones d-flex justify-content-between">
  <a href="cotizaciones/<?= (int) $cot['id'] ?>" class="btn btn-sm btn-outline-secondary">← Volver a la cotización</a>
  <button class="btn btn-sm btn-primary" onclick="window.print()">Imprimir / Guardar PDF</button>
</div>
<?php if ($t['sin_analisis'] > 0): ?>
<div class="acciones"><div class="alert alert-warning py-2 mt-2 mb-0">Atención: <?= (int) $t['sin_analisis'] ?> concepto(s) no tienen análisis y su precio unitario sale en $0. Este aviso no se imprime.</div></div>
<?php endif; ?>

<div class="hoja">
  <div class="d-flex justify-content-between align-items-start mb-3">
    <div>
      <div class="empresa"><?= $h($cfg['empresa_nombre']) ?></div>
      <?php if ($cfg['empresa_rfc']): ?><div>RFC: <?= $h($cfg['empresa_rfc']) ?></div><?php endif; ?>
      <div><?= $h(implode(' · ', array_filter([$cfg['empresa_direccion'], $cfg['empresa_telefono'] ? 'Tel. ' . $cfg['empresa_telefono'] : '', $cfg['empresa_email']]))) ?></div>
    </div>
    <img src="assets/img/logo.png" alt="" style="height:60px">
  </div>

  <div class="barra titulo mb-3">PRESUPUESTO</div>

  <div class="d-flex justify-content-between mb-3">
    <table class="datos">
      <tr><td class="lbl">No.:</td><td><?= $h($cot['folio']) ?></td></tr>
      <tr><td class="lbl">Cliente:</td><td><?= $h($cot['cliente_nombre']) ?></td></tr>
      <tr><td class="lbl">Atención:</td><td><?= $h($cot['cliente_contacto']) ?></td></tr>
      <tr><td class="lbl">Obra:</td><td><?= $h($cot['obra']) ?></td></tr>
      <tr><td class="lbl">Ubicación:</td><td><?= $h($ubicacion) ?></td></tr>
      <tr><td class="lbl">Plazo:</td><td><?= $plazo ? $plazo . ' días naturales' : 'Por definir' ?></td></tr>
    </table>
    <table class="datos">
      <tr><td class="lbl text-end">Fecha:</td><td><?= $f($cot['fecha_cotizacion']) ?></td></tr>
      <tr><td class="lbl text-end">Válido hasta:</td><td><?= $f($cot['valida_hasta']) ?></td></tr>
      <tr><td class="lbl text-end">RFC:</td><td><?= $h($cot['cliente_rfc']) ?></td></tr>
    </table>
  </div>

  <table class="partidas mb-2">
    <thead><tr><th style="width:60px">CLAVE</th><th>CONCEPTO</th><th style="width:45px">U.</th><th style="width:75px">CANTIDAD</th><th style="width:90px">P.U.</th><th style="width:105px">IMPORTE</th></tr></thead>
    <tbody>
    <?php foreach ($cot['partidas'] as $p): ?>
      <tr class="partida"><td><?= $h($p['clave']) ?></td><td colspan="4"><?= $h($p['nombre']) ?></td><td class="num"><?= $m($p['importe']) ?></td></tr>
      <?php foreach ($p['conceptos'] as $c): ?>
      <tr>
        <td><?= $h($c['clave']) ?></td>
        <td><?= nl2br($h($c['descripcion'])) ?></td>
        <td class="text-center"><?= $h($c['unidad']) ?></td>
        <td class="num"><?= number_format($c['analisis']['cantidad'], 2) ?></td>
        <td class="num"><?= $m($c['analisis']['pu']) ?></td>
        <td class="num"><?= $m($c['analisis']['importe']) ?></td>
      </tr>
      <?php endforeach; ?>
    <?php endforeach; ?>
    </tbody>
  </table>

  <div class="d-flex justify-content-between align-items-end mb-3">
    <em>Precios en moneda nacional.</em>
    <table class="totales">
      <tr><td class="fw-bold text-end">SUBTOTAL</td><td class="num" style="width:120px"><?= $m($t['subtotal']) ?></td></tr>
      <tr><td class="fw-bold text-end">IVA <?= round($cot['pct_iva'] * 100, 2) ?>%</td><td class="num"><?= $m($t['iva']) ?></td></tr>
      <tr class="total"><td class="text-end">TOTAL</td><td class="num"><?= $m($t['total']) ?></td></tr>
    </table>
  </div>

  <div class="barra mb-2">CONDICIONES</div>
  <table class="datos mb-2">
    <tr><td class="lbl">Forma de pago:</td><td><?= $h($formaPago) ?></td></tr>
    <?php if ($cot['alcances']): ?><tr><td class="lbl">Alcances:</td><td><?= nl2br($h($cot['alcances'])) ?></td></tr><?php endif; ?>
    <?php if ($cot['exclusiones']): ?><tr><td class="lbl">Exclusiones:</td><td><?= nl2br($h($cot['exclusiones'])) ?></td></tr><?php endif; ?>
    <?php if ($cot['notas']): ?><tr><td class="lbl">Notas:</td><td><?= nl2br($h($cot['notas'])) ?></td></tr><?php endif; ?>
  </table>

  <div class="firma">
    <div class="fw-bold"><?= $h($cfg['responsable']) ?></div>
    <div><?= $h($cfg['cargo']) ?></div>
    <div><?= $h($cfg['empresa_nombre']) ?></div>
  </div>
</div>
</body>
</html>
