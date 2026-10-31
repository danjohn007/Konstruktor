<?php
$currentUser = $user ?? null;
$uri = substr(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), strlen(BASE)) ?: '/';
$isActive = fn(string $path) => $path === '/' ? ($uri === '/' ? 'active' : '') : (str_starts_with($uri, $path) ? 'active' : '');
$breadcrumb = ($titulo ?? '') === 'Inicio' ? 'Inicio' : 'Inicio / ' . ($titulo ?? '');
$esAdmin = ($currentUser['rol_nombre'] ?? '') === 'Admin';
// Mismos enlaces en la barra lateral (escritorio) y en el menú desplegable (celular).
ob_start();
?>
      <a href="./" class="<?= $isActive('/') ?>"><i class="bi bi-house-door"></i> Inicio</a>
      <a href="cotizaciones" class="<?= $isActive('/cotizaciones') ?>"><i class="bi bi-file-earmark-spreadsheet"></i> Cotizaciones</a>
      <a href="clientes" class="<?= $isActive('/clientes') ?>"><i class="bi bi-people"></i> Clientes</a>
      <a href="insumos" class="<?= $isActive('/insumos') ?>"><i class="bi bi-box-seam"></i> Insumos</a>
      <a href="cuadrillas" class="<?= $isActive('/cuadrillas') ?>"><i class="bi bi-person-gear"></i> Cuadrillas</a>
      <?php if ($esAdmin): ?>
      <a href="usuarios" class="<?= $isActive('/usuarios') ?>"><i class="bi bi-person-lock"></i> Usuarios</a>
      <a href="configuracion" class="<?= $isActive('/configuracion') ?>"><i class="bi bi-gear"></i> Configuración</a>
      <?php endif; ?>
<?php
$navLinks = ob_get_clean();
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<base href="<?= BASE ?>/">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($titulo ?? 'Konstruktor') ?> · Konstruktor</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link rel="stylesheet" href="assets/css/app.css">
</head>
<body>
<div class="d-flex">
  <aside class="sidebar d-none d-lg-block">
    <div class="sidebar-brand">
      <img src="assets/img/logo.png" alt="Konstruktor">
    </div>
    <nav class="sidebar-nav">
<?= $navLinks ?>
    </nav>
  </aside>
  <div class="offcanvas offcanvas-start sidebar-offcanvas" tabindex="-1" id="menuMovil">
    <div class="offcanvas-header sidebar-brand">
      <img src="assets/img/logo.png" alt="Konstruktor">
      <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Cerrar"></button>
    </div>
    <nav class="sidebar-nav">
<?= $navLinks ?>
    </nav>
  </div>
  <div class="main-content flex-grow-1">
    <header class="topbar d-flex align-items-center justify-content-between">
      <button class="btn btn-outline-secondary btn-sm d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#menuMovil" title="Menú"><i class="bi bi-list fs-5"></i></button>
      <form class="topbar-search" action="cotizaciones" method="get">
        <i class="bi bi-search"></i>
        <input type="text" name="q" placeholder="Buscar cotización, obra o cliente...">
      </form>
      <div class="d-flex align-items-center gap-2 gap-sm-3">
        <div class="user-chip">
          <div class="avatar"><i class="bi bi-person-fill"></i></div>
          <div class="d-none d-sm-block">
            <div class="user-name"><?= htmlspecialchars($currentUser['nombre'] ?? '') ?></div>
            <div class="user-role"><?= htmlspecialchars($currentUser['rol_nombre'] ?? '') ?></div>
          </div>
        </div>
        <button class="btn btn-sm btn-outline-secondary" title="Cambiar mi contraseña" data-bs-toggle="modal" data-bs-target="#modalMiPassword"><i class="bi bi-key"></i></button>
        <form method="post" action="logout" class="m-0">
          <button class="btn btn-sm btn-outline-secondary" title="Cerrar sesión"><i class="bi bi-box-arrow-right"></i></button>
        </form>
      </div>
    </header>
    <main class="p-3 p-lg-4">
      <div class="content-header">
        <div class="breadcrumb-trail"><?= htmlspecialchars($breadcrumb) ?></div>
        <div class="page-title"><?= htmlspecialchars($titulo ?? '') ?></div>
      </div>
      <?php require $contentFile; ?>
    </main>
  </div>
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
<script src="assets/js/app.js"></script>
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
