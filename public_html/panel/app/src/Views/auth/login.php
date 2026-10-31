<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<base href="<?= BASE ?>/">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Iniciar sesión · Konstruktor</title>
<link rel="icon" type="image/png" href="assets/img/logo.png">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fontsource-variable/inter@5.1.0/index.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link rel="stylesheet" href="assets/css/app.css">
</head>
<body>
<div class="login">
  <!-- Panel de marca (escritorio): maqueta de una obra en curso -->
  <aside class="login-marca">
    <div class="login-fondo" aria-hidden="true">
      <div class="brillo b1"></div>
      <div class="brillo b2"></div>
      <div class="reticula"></div>
    </div>

    <div class="login-logo"><img src="assets/img/logo.png" alt="Konstruktor"></div>

    <div class="login-maqueta" aria-hidden="true">
      <div class="maqueta">
        <div class="maqueta-panel">
          <div class="d-flex justify-content-between align-items-center">
            <span class="eti">KON-26-014 · Semana 9 de 16</span>
            <span class="badge estado-aceptada">En obra</span>
          </div>
          <div class="obra">Casa habitación 2 niveles</div>
          <div class="maqueta-kpis">
            <div><small>Avance real</small><b>64%</b><em>+3 pts vs programa</em></div>
            <div><small>Contratado</small><b>$1.28 M</b><em style="color:var(--slate-500)">Precio fijo</em></div>
            <div><small>Cobrado</small><b>$742 k</b><em style="color:var(--slate-500)">4 estimaciones</em></div>
          </div>
          <div class="maqueta-barras">
            <div class="fila"><span>Ejecutado por semana</span><b>$96,400</b></div>
            <div class="barras">
              <?php foreach ([30, 44, 38, 56, 50, 64, 60, 72, 86] as $alto): ?><span style="height:<?= $alto ?>%"></span><?php endforeach; ?>
            </div>
          </div>
        </div>

        <div class="flotante f1">
          <span class="kpi-icon tono-success" style="background:var(--success-600);color:#fff"><i class="bi bi-check-lg"></i></span>
          <div><b>Estimación #4 autorizada</b><small>Pagas solo lo construido</small></div>
        </div>
        <div class="flotante f2">
          <span class="kpi-icon tono-brand"><i class="bi bi-camera"></i></span>
          <div><b>Avance con foto</b><small>1.01 Muros · 18 m² hoy</small></div>
        </div>
        <div class="flotante f3">
          <span class="kpi-icon tono-warning"><i class="bi bi-receipt"></i></span>
          <div><b>Gasto · $5,250</b><small>35 varillas 3/8" · ticket adjunto</small></div>
        </div>
      </div>
    </div>

    <div class="login-propuesta">
      <span class="sello"><i class="bi bi-shield-check" style="color:var(--brand-300)"></i> Acceso seguro</span>
      <h1>Presupuesta, programa y controla tu obra. <span>Todo en un solo lugar.</span></h1>
      <ul>
        <li><i class="bi bi-calculator"></i> Precios unitarios, generadores y explosión de insumos.</li>
        <li><i class="bi bi-camera"></i> Avance diario con fotos desde la obra.</li>
        <li><i class="bi bi-graph-up-arrow"></i> Estimaciones, gastos y flujo de efectivo al día.</li>
      </ul>
    </div>
  </aside>

  <!-- Formulario -->
  <main class="login-form-lado">
    <div class="login-form-centro">
      <div class="login-form">
        <div class="login-movil">
          <img src="assets/img/logo.png" alt="Konstruktor">
          <p>Presupuesta, programa y controla tu obra. <span>Todo en un solo lugar.</span></p>
        </div>

        <div class="saludo">Bienvenido</div>
        <h2>Inicia sesión</h2>
        <p class="intro">Entra a tus cotizaciones, obras y control de gastos.</p>

        <div id="loginError" class="alert alert-danger d-none mt-3"></div>
        <form id="loginForm" class="mt-4">
          <div class="mb-3">
            <label for="email">Correo electrónico</label>
            <div class="campo-icono">
              <i class="bi bi-envelope"></i>
              <input id="email" type="email" class="form-control" name="email" required autofocus autocomplete="username" placeholder="tu@correo.com">
            </div>
          </div>
          <div class="mb-3">
            <label for="password">Contraseña</label>
            <div class="campo-icono">
              <i class="bi bi-lock"></i>
              <input id="password" type="password" class="form-control" name="password" required autocomplete="current-password" placeholder="••••••••" style="padding-right:3rem">
              <button type="button" class="ver-clave" id="verClave" aria-label="Mostrar contraseña"><i class="bi bi-eye"></i></button>
            </div>
          </div>
          <div class="d-flex justify-content-end mb-3">
            <button type="button" class="btn btn-link btn-sm p-0 fw-semibold" data-bs-toggle="collapse" data-bs-target="#olvido">¿Olvidaste tu contraseña?</button>
          </div>
          <div class="collapse" id="olvido">
            <div class="alert alert-info d-flex gap-2"><i class="bi bi-info-circle"></i><span>Pide al administrador que la restablezca en <strong>Usuarios</strong>.</span></div>
          </div>
          <button class="btn btn-primary w-100 btn-entrar d-flex align-items-center justify-content-center gap-2" type="submit" id="btnEntrar">
            <span>Entrar</span><i class="bi bi-arrow-right"></i>
          </button>
        </form>
      </div>
    </div>
    <footer class="login-pie"><i class="bi bi-lock"></i> Conexión cifrada · © <?= date('Y') ?> Konstruktor</footer>
  </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('verClave').addEventListener('click', (e) => {
    const input = document.getElementById('password');
    const ver = input.type === 'password';
    input.type = ver ? 'text' : 'password';
    e.currentTarget.innerHTML = `<i class="bi bi-eye${ver ? '-slash' : ''}"></i>`;
    e.currentTarget.setAttribute('aria-label', ver ? 'Ocultar contraseña' : 'Mostrar contraseña');
});

document.getElementById('loginForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const fd = new FormData(e.target);
    const errBox = document.getElementById('loginError');
    const btn = document.getElementById('btnEntrar');
    errBox.classList.add('d-none');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Verificando…';
    try {
        const res = await fetch('api/auth/login', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email: fd.get('email'), password: fd.get('password') }),
        });
        const data = await res.json();
        if (!res.ok) {
            errBox.textContent = data.error || 'Error al iniciar sesión';
            errBox.classList.remove('d-none');
            return;
        }
        window.location.href = './';
    } catch (err) {
        errBox.textContent = 'Error de conexión con el servidor';
        errBox.classList.remove('d-none');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<span>Entrar</span><i class="bi bi-arrow-right"></i>';
    }
});
</script>
</body>
</html>
