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
<link rel="stylesheet" href="<?= recurso('assets/css/app.css') ?>">
</head>
<body>
<div class="login">
  <!-- Panel de marca (escritorio): maqueta de una obra en curso -->
  <!-- Panel de marca (escritorio): una ciudad que se construye piso por piso, como gráfica de barras de avance -->
  <aside class="login-marca">
    <div class="login-fondo" aria-hidden="true">
      <div class="brillo b1"></div>
      <div class="brillo b2"></div>
      <div class="reticula"></div>
    </div>

    <div class="login-logo"><img src="assets/img/logo.png" alt="Konstruktor"></div>

    <div class="login-maqueta" aria-hidden="true">
      <div class="escena">
        <?php
        // Cada edificio es una semana de obra: sus pisos aparecen uno a uno, como barras que crecen.
        $suelo = 316;
        $alto = 16;
        $edificios = [4, 6, 5, 9, 8, 11, 13];
        $puntos = [];
        ?>
        <svg viewBox="0 0 560 350" class="escena-svg">
          <defs>
            <linearGradient id="gPiso" x1="0" y1="0" x2="0" y2="1">
              <stop offset="0" stop-color="#3d83c2" stop-opacity=".75"/>
              <stop offset="1" stop-color="#2f75b5" stop-opacity=".45"/>
            </linearGradient>
          </defs>

          <!-- Suelo -->
          <line x1="16" y1="<?= $suelo ?>" x2="544" y2="<?= $suelo ?>" class="suelo"/>

          <?php foreach ($edificios as $i => $pisos):
              $x = 40 + $i * 64;
              $puntos[] = ($x + 25) . ',' . ($suelo - $pisos * $alto - 10);
              $actual = $i === count($edificios) - 1;
          ?>
          <g class="edificio<?= $actual ? ' actual' : '' ?>">
            <?php for ($f = 0; $f < $pisos; $f++):
                $y = $suelo - ($f + 1) * $alto + 2;
            ?>
            <g class="piso" style="--d: <?= number_format($i * 0.35 + $f * 0.17, 2) ?>s">
              <rect x="<?= $x ?>" y="<?= $y ?>" width="50" height="14" rx="1.5" class="losa"/>
              <?php foreach ([7, 21, 35] as $k => $dx): ?>
              <rect x="<?= $x + $dx ?>" y="<?= $y + 4 ?>" width="8" height="6" rx="1" class="ventana<?= ($i * 7 + $f * 3 + $k) % 6 === 0 ? ' encendida' : '' ?>"/>
              <?php endforeach; ?>
            </g>
            <?php endfor; ?>
          </g>
          <text x="<?= $x + 25 ?>" y="<?= $suelo + 20 ?>" class="semana">S<?= $i + 1 ?></text>
          <?php endforeach; ?>

          <!-- Lo programado: la curva que deben alcanzar los pisos -->
          <polyline points="<?= implode(' ', $puntos) ?>" class="programado"/>
          <?php foreach ($puntos as $pt): [$px, $py] = explode(',', $pt); ?>
          <circle cx="<?= $px ?>" cy="<?= $py ?>" r="3" class="punto"/>
          <?php endforeach; ?>

          <!-- Grúa torre junto al edificio de esta semana -->
          <g class="grua">
            <path d="M496 <?= $suelo ?> V58 M508 <?= $suelo ?> V58" class="mastil"/>
            <path d="<?php for ($y = $suelo; $y > 70; $y -= 16) echo 'M496 ' . $y . ' L508 ' . ($y - 16) . ' '; ?>" class="celosia"/>
            <rect x="490" y="48" width="24" height="12" rx="2" class="cabina"/>
            <path d="M372 58 H546 M372 66 H508" class="pluma"/>
            <path d="<?php for ($x = 372; $x < 496; $x += 12) echo 'M' . $x . ' 66 L' . ($x + 6) . ' 58 L' . ($x + 12) . ' 66 '; ?>" class="celosia"/>
            <path d="M502 48 L452 58 M502 48 L540 58" class="tirante"/>
            <rect x="528" y="58" width="16" height="12" rx="1.5" class="contrapeso"/>
            <circle cx="502" cy="44" r="2.5" class="baliza"/>
            <g class="carro">
              <rect x="440" y="66" width="12" height="5" rx="1" class="cabina"/>
              <line x1="446" y1="71" x2="446" y2="96" class="cable"/>
              <path d="M442 96 h8 l-4 5 z" class="cabina"/>
              <rect x="430" y="101" width="32" height="5" rx="1" class="viga"/>
            </g>
          </g>
        </svg>
        <div class="escena-leyenda"><span><i class="cuadro"></i> Pisos ejecutados por semana</span><span><i class="linea"></i> Programado</span></div>

        <div class="flotante f1">
          <span class="kpi-icon tono-success"><i class="bi bi-graph-up-arrow"></i></span>
          <div><b>Avance real 64%</b><small>+3 pts arriba del programa</small></div>
        </div>
        <div class="flotante f3">
          <span class="kpi-icon" style="background:#fff3dc;color:#b7791f"><i class="bi bi-cone-striped"></i></span>
          <div><b>Semana 9 de 16</b><small>Losa de azotea · 18 m² hoy</small></div>
        </div>
      </div>
    </div>

    <div class="login-propuesta">
      <span class="sello"><i class="bi bi-building" style="color:#f5c26b"></i> Control de obra</span>
      <h1>Del presupuesto a la entrega, <span>piso por piso.</span></h1>
      <ul>
        <li><i class="bi bi-rulers"></i> Precios unitarios, generadores y explosión de insumos.</li>
        <li><i class="bi bi-cone-striped"></i> Avance diario con fotos desde la obra.</li>
        <li><i class="bi bi-bar-chart-line"></i> Estimaciones, gastos y flujo de efectivo al día.</li>
      </ul>
    </div>
  </aside>

  <!-- Formulario -->
  <main class="login-form-lado">
    <div class="login-form-centro">
      <div class="login-form">
        <div class="login-movil">
          <img src="assets/img/logo.png" alt="Konstruktor">
          <p>Del presupuesto a la entrega, <span>piso por piso.</span></p>
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
