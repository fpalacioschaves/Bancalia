<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Bancalia - Bienvenido</title>
  <style>
    body { font-family: Arial, sans-serif; background: #f4f6fa; margin: 0; }
    .container { max-width: 400px; margin: 60px auto; background: #fff; padding: 32px; border-radius: 8px; box-shadow: 0 2px 8px #0001; }
    h1 { text-align: center; color: #2a3d6c; }
    .logo { display: block; margin: 0 auto 24px; width: 80px; }
    form { margin-top: 24px; }
    input[type="text"], input[type="password"] { width: 100%; padding: 10px; margin: 8px 0; border: 1px solid #ccc; border-radius: 4px; }
    button { width: 100%; padding: 10px; background: #2a3d6c; color: #fff; border: none; border-radius: 4px; font-size: 16px; cursor: pointer; }
    .registro { text-align: center; margin-top: 18px; }
    .registro a { color: #2a3d6c; text-decoration: underline; }
  </style>
</head>
<body>
  <div class="container">
    <img src="/Bancalia/public/img/logo.png" alt="Bancalia" class="logo" />
    <h1>Bienvenido a Bancalia</h1>
    <form action="/Bancalia/login.php" method="post">
      <input type="text" name="usuario" placeholder="Usuario" required>
      <input type="password" name="clave" placeholder="Contraseña" required>
      <button type="submit">Iniciar sesión</button>
    </form>
    <div class="registro">
      ¿No tienes cuenta? <a href="/Bancalia/registro.php">Regístrate aquí</a>
    </div>
  </div>
  <script>
document.getElementById('loginForm').addEventListener('submit', async function(ev) {
  ev.preventDefault();
  const form = ev.target;
  const data = new FormData(form);
  const res = await fetch(form.action, {
    method: 'POST',
    body: data,
    headers: { 'Accept': 'application/json' }
  });
  const text = await res.text();
  let json = {};
  try { json = JSON.parse(text); } catch {}
  if (json.success) {
    window.location.href = json.redirect || 'panel.php';
  } else {
    document.getElementById('loginError').textContent = json.error || 'Usuario o contraseña incorrectos.';
  }
});
</script>
</body>
</html>