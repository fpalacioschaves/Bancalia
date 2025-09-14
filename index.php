<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Bancalia - Bienvenido</title>
  <!-- Tailwind CSS -->
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- Tus estilos -->
  <link rel="stylesheet" href="/Bancalia/assets/css/app.css">
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">

  <div class="container max-w-md bg-white p-8 rounded-xl shadow-md">
    <div class="flex flex-col items-center mb-6">
      <img src="/Bancalia/assets/images/logo.png" alt="Bancalia"/>
      <h1 class="text-2xl font-bold text-blue-600">Bienvenido a Bancalia</h1>
    </div>

    <form id="loginForm" action="/Bancalia/login.php" method="post" class="space-y-4">
      <input type="text" name="usuario" placeholder="Usuario" required
        class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
      <input type="password" name="clave" placeholder="Contraseña" required
        class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
      <button type="submit"
        class="w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 transition">
        Iniciar sesión
      </button>
    </form>

    <p id="loginError" class="text-red-500 text-sm mt-3"></p>

    <div class="registro mt-6 text-center text-gray-600">
      ¿No tienes cuenta? 
      <a href="/Bancalia/registro.php" class="text-blue-600 hover:underline">Regístrate aquí</a>
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
        document.getElementById('loginError').textContent =
          json.error || 'Usuario o contraseña incorrectos.';
      }
    });
  </script>
</body>
</html>
