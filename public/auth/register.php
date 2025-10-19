<?php
// /public/auth/register.php
declare(strict_types=1);
require_once __DIR__ . '/../../config.php';

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $nombre = trim((string)($_POST['nombre'] ?? ''));
  $email  = trim((string)($_POST['email'] ?? ''));
  $pass   = (string)($_POST['password'] ?? '');

  try {
    $uid = register_user($nombre, $email, $pass);
    // Auto-login tras registro
    login_user($email, $pass);
    flash('success','Bienvenido. Completa tu perfil.');
    header('Location: ' . PUBLIC_URL . '/mi-perfil.php'); exit;
  } catch (Throwable $e) {
    $errors[] = $e->getMessage();
    flash('error', $e->getMessage());
  }
}

$flashError = flash('error'); $flashOk = flash('success');
?>
<!doctype html><html lang="es"><head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Registro · Bancalia</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head><body class="bg-slate-50 text-slate-900">
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
      <form method="post" class="space-y-4">
        <div>
          <label for="nombre" class="mb-1 block text-sm font-medium text-slate-700">Nombre</label>
          <input id="nombre" name="nombre" type="text" required
                 class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-slate-400">
        </div>
        <div>
          <label for="email" class="mb-1 block text-sm font-medium text-slate-700">Email</label>
          <input id="email" name="email" type="email" required autocomplete="username"
                 class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-slate-400">
        </div>
        <div>
          <label for="password" class="mb-1 block text-sm font-medium text-slate-700">Contraseña</label>
          <input id="password" name="password" type="password" required autocomplete="new-password"
                 class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-slate-400">
        </div>
        <div class="flex items-center justify-end">
          <button class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">Crear cuenta</button>
        </div>
      </form>
    </div>
  </main>
</body></html>
