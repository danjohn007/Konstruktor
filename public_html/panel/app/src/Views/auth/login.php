<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<base href="<?= BASE ?>/">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Iniciar sesión · Konstruktor</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="assets/css/app.css">
</head>
<body class="login-body d-flex align-items-center justify-content-center">
  <div class="login-card card p-4">
    <img src="assets/img/logo.png" alt="Konstruktor" class="mb-2 mx-auto" style="max-width:220px">
    <p class="text-muted text-center mb-4">Cotización de obra por precios unitarios</p>
    <div id="loginError" class="alert alert-danger d-none"></div>
    <form id="loginForm">
      <div class="mb-3">
        <label class="form-label">Correo electrónico</label>
        <input type="email" class="form-control" name="email" required autofocus>
      </div>
      <div class="mb-3">
        <label class="form-label">Contraseña</label>
        <input type="password" class="form-control" name="password" required>
      </div>
      <button class="btn btn-primary w-100" type="submit">Ingresar</button>
    </form>
  </div>
<script>
document.getElementById('loginForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const fd = new FormData(e.target);
    const errBox = document.getElementById('loginError');
    errBox.classList.add('d-none');
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
    }
});
</script>
</body>
</html>
