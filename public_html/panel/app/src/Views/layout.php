<?php
$currentUser = $user ?? null;
$uri = substr(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), strlen(BASE)) ?: '/';
$isActive = fn(string ...$paths) => in_array(true, array_map(
    fn($p) => $p === '/' ? $uri === '/' : str_starts_with($uri, $p), $paths
), true) ? 'active' : '';
$esAdmin = ($currentUser['rol_nombre'] ?? '') === 'Admin';
$esIngeniero = ($currentUser['rol_nombre'] ?? '') === 'Ingeniero';
$h = fn($v) => htmlspecialchars((string) $v);
$iniciales = implode('', array_map(fn($p) => mb_strtoupper(mb_substr($p, 0, 1)), array_slice(preg_split('/\s+/', trim($currentUser['nombre'] ?? 'K')), 0, 2)));
// Migas: Inicio › (las que mande el controlador, etiqueta => enlace) › página actual
$migas = $esIngeniero ? ($migas ?? []) : ['Inicio' => './'] + ($migas ?? []);
if (($titulo ?? '') === 'Inicio') {
    $migas = [];
}
// Mismos enlaces en la barra lateral (escritorio) y en el menú desplegable (celular).
ob_start();
?>
      <?php if ($esIngeniero): ?>
      <div class="nav-group-label">Obra</div>
      <a href="cotizaciones" class="<?= $isActive('/cotizaciones') ?>"><i class="bi bi-cone-striped"></i> Obras</a>
      <?php else: ?>
      <div class="nav-group-label">Operación</div>
      <a href="./" class="<?= $isActive('/') ?>"><i class="bi bi-grid-1x2"></i> Inicio</a>
      <a href="cotizaciones" class="<?= $isActive('/cotizaciones', '/estimaciones') ?>"><i class="bi bi-file-earmark-spreadsheet"></i> Cotizaciones y obras</a>
      <div class="nav-group-label">Catálogos</div>
      <a href="clientes" class="<?= $isActive('/clientes') ?>"><i class="bi bi-people"></i> Clientes</a>
      <a href="insumos" class="<?= $isActive('/insumos') ?>"><i class="bi bi-box-seam"></i> Insumos</a>
      <a href="cuadrillas" class="<?= $isActive('/cuadrillas') ?>"><i class="bi bi-person-gear"></i> Cuadrillas</a>
      <?php if ($esAdmin): ?>
      <div class="nav-group-label">Administración</div>
      <a href="usuarios" class="<?= $isActive('/usuarios') ?>"><i class="bi bi-person-lock"></i> Usuarios</a>
      <a href="configuracion" class="<?= $isActive('/configuracion') ?>"><i class="bi bi-gear"></i> Configuración</a>
      <?php endif; ?>
      <?php endif; ?>
<?php
$navLinks = ob_get_clean();
ob_start();
?>
    <div class="sidebar-foot">
      <strong><span class="punto-vivo"></span> Sistema en línea</strong>
      <div class="mt-1">Konstruktor · Tecnología en la construcción</div>
    </div>
<?php
$sidebarFoot = ob_get_clean();
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<base href="<?= BASE ?>/">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= $h($titulo ?? 'Konstruktor') ?> · Konstruktor</title>
<link rel="icon" type="image/png" href="assets/img/logo.png">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fontsource-variable/inter@5.1.0/index.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link rel="stylesheet" href="<?= recurso('assets/css/app.css') ?>">
</head>
<body>
<aside class="sidebar d-none d-lg-flex">
  <div class="sidebar-brand"><a href="<?= $esIngeniero ? 'cotizaciones' : './' ?>"><img src="assets/img/logo.png" alt="Konstruktor"></a></div>
  <nav class="sidebar-nav">
<?= $navLinks ?>
  </nav>
<?= $sidebarFoot ?>
</aside>
<div class="offcanvas offcanvas-start sidebar-offcanvas" tabindex="-1" id="menuMovil">
  <div class="offcanvas-body">
    <div class="sidebar-brand d-flex align-items-center gap-2">
      <a href="<?= $esIngeniero ? 'cotizaciones' : './' ?>" class="flex-grow-1"><img src="assets/img/logo.png" alt="Konstruktor"></a>
      <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Cerrar"></button>
    </div>
    <nav class="sidebar-nav">
<?= $navLinks ?>
    </nav>
<?= $sidebarFoot ?>
  </div>
</div>

<div class="main-content">
  <header class="topbar">
    <button class="btn-icono d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#menuMovil" aria-label="Menú"><i class="bi bi-list"></i></button>
    <form class="topbar-search" action="cotizaciones" method="get">
      <i class="bi bi-search"></i>
      <input type="text" name="q" placeholder="<?= $esIngeniero ? 'Buscar obra…' : 'Buscar cotización, obra o cliente…' ?>">
    </form>
    <div class="ms-auto dropdown">
      <button class="user-menu" type="button" data-bs-toggle="dropdown" aria-expanded="false">
        <span class="avatar"><?= $h($iniciales) ?></span>
        <span class="d-none d-sm-block">
          <span class="user-name"><?= $h($currentUser['nombre'] ?? '') ?></span>
          <span class="user-role"><?= $h($currentUser['rol_nombre'] ?? '') ?></span>
        </span>
        <i class="bi bi-chevron-down small text-muted d-none d-sm-block"></i>
      </button>
      <div class="dropdown-menu dropdown-menu-end" style="min-width:230px">
        <div class="dropdown-header"><?= $h($currentUser['email'] ?? '') ?></div>
        <button class="dropdown-item" type="button" data-bs-toggle="modal" data-bs-target="#modalMiPassword"><i class="bi bi-key"></i> Cambiar mi contraseña</button>
        <form method="post" action="logout" class="m-0">
          <button class="dropdown-item text-danger"><i class="bi bi-box-arrow-right"></i> Cerrar sesión</button>
        </form>
      </div>
    </div>
  </header>

  <main class="app-main">
    <div class="page-header">
      <div class="min-w-0">
        <?php if ($migas): ?>
        <nav class="crumbs">
          <?php foreach ($migas as $etiqueta => $enlace): ?>
          <a href="<?= $h($enlace) ?>"><?= $h($etiqueta) ?></a><i class="bi bi-chevron-right"></i>
          <?php endforeach; ?>
          <span><?= $h($titulo ?? '') ?></span>
        </nav>
        <?php endif; ?>
        <?php if (empty($sinTitulo)): // las pantallas de detalle ponen su propio encabezado (nombre de la obra y acciones) ?>
        <h1 class="page-title"><?= $h($titulo ?? '') ?></h1>
        <?php if (!empty($subtitulo)): ?><p class="page-subtitle"><?= $h($subtitulo) ?></p><?php endif; ?>
        <?php endif; ?>
      </div>
    </div>
    <?php require $contentFile; ?>
  </main>
</div>

<div class="modal fade" id="modalMiPassword" tabindex="-1">
  <div class="modal-dialog modal-sm">
    <div class="modal-content">
      <form id="formMiPassword">
        <div class="modal-header"><h5 class="modal-title">Cambiar mi contraseña</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <div class="mb-2"><label class="form-label">Contraseña actual</label><input type="password" class="form-control" name="actual" required autocomplete="current-password"></div>
          <div class="mb-2"><label class="form-label">Nueva contraseña</label><input type="password" class="form-control" name="nueva" required minlength="8" autocomplete="new-password"></div>
          <div class="mb-2"><label class="form-label">Repite la nueva</label><input type="password" class="form-control" name="repite" required minlength="8" autocomplete="new-password"></div>
        </div>
        <div class="modal-footer"><button class="btn btn-primary">Cambiar</button></div>
      </form>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php if (!empty($graficas)): ?>
<script src="https://cdn.jsdelivr.net/npm/echarts@5.5.1/dist/echarts.min.js"></script>
<?php endif; ?>
<script src="<?= recurso('assets/js/app.js') ?>"></script>
<script>
document.getElementById('formMiPassword').addEventListener('submit', async (e) => {
    e.preventDefault();
    const d = formData(e.target);
    if (d.nueva !== d.repite) {
        alert('Las contraseñas nuevas no coinciden');
        return;
    }
    try {
        await api('api/mi-password', 'PUT', { actual: d.actual, nueva: d.nueva });
        bootstrap.Modal.getInstance(document.getElementById('modalMiPassword')).hide();
        e.target.reset();
        alert('Contraseña actualizada');
    } catch (err) {
        alert(err.message);
    }
});
</script>
<?php if (isset($scripts)) echo $scripts; ?>
</body>
</html>
