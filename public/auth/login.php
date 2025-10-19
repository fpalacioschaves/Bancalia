<?php
// /public/auth/login.php
declare(strict_types=1);
require_once __DIR__ . '/../../config.php';

// Si ya hay sesión, redirige según rol
if ($u = current_user()) {
  $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
  $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https://' : 'http://';
  $dest   = ($u['role'] ?? '') === 'admin'
          ? PUBLIC_URL . '/dashboard.php'
          : PUBLIC_URL . '/mi-perfil.php';
  header('Location: ' . $scheme . $host . $dest);
  exit;
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    $email = (string)($_POST['email'] ?? '');
    $pass  = (string)($_POST['password'] ?? '');
    login_user($email, $pass);

    // Decide destino por rol
    $u = current_user() ?? [];
    $dest = ($u['role'] ?? '') === 'admin'
          ? PUBLIC_URL . '/dashboard.php'
          : PUBLIC_URL . '/mi-perfil.php';

    // Asegura que la cookie se escriba antes del redirect
    session_write_close();

    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https://' : 'http://';
    header('Location: ' . $scheme . $host . $dest);
    exit;
  } catch (Throwable $e) {
    $errors[] = $e->getMessage();
    flash('error', $e->getMessage());
  }
}

$flashError = flash('error'); $flashOk = flash('success');
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Entrar · Bancalia</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 text-slate-900">
  <main class="mx-auto max-w-md px-4 py-10">
    <div class="mb-6 flex items-center justify-center gap-2">
      <span class="inline-flex h-10 w-10 items-center justify-center rounded-lg bg-slate-900 text-white text-lg font-bold">B</span>
      <div class="text-xl font-semibold">Bancalia</div>
    </div>

    <?php if ($flashError || $errors): ?>
      <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 p-3 text-sm text-rose-800">
        <?php if ($flashError): ?><div>• <?= h($flashError) ?></div><?php endif; ?>
        <?php foreach ($errors as $e): ?><div>• <?= h($e) ?></div><?php endforeach; ?>
      </div>
    <?php endif; ?>
    <?php if ($flashOk): ?>
      <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800"><?= h($flashOk) ?></div>
    <?php endif; ?>

    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
      <form method="post" class="space-y-4" autocomplete="off">
        <div>
          <label for="email" class="mb-1 block text-sm font-medium text-slate-700">Email</label>
          <input id="email" name="email" type="email" required autocomplete="username"
                 class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-slate-400">
        </div>
        <div>
          <label for="password" class="mb-1 block text-sm font-medium text-slate-700">Contraseña</label>
          <input id="password" name="password" type="password" required autocomplete="current-password"
                 class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-slate-400">
        </div>
        <div class="flex items-center justify-between">
          <a href="<?= h(PUBLIC_URL) ?>/auth/register.php" class="text-sm text-slate-600 hover:underline">Crear cuenta</a>
          <button class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">Entrar</button>
        </div>
      </form>
    </div>
  </main>
</body>
</html>
