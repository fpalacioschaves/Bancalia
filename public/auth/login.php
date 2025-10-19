<?php
// /public/auth/login.php
declare(strict_types=1);
require_once __DIR__ . '/../../middleware/require_guest.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    csrf_check($_POST['csrf'] ?? null);
    $email = trim($_POST['email'] ?? '');
    $pass  = trim($_POST['password'] ?? '');
    if ($email === '' || $pass === '') {
      throw new RuntimeException('Email y contraseña son obligatorios.');
    }
    login_user($email, $pass);
    flash('success', 'Sesión iniciada correctamente.');
    header('Location: ' . PUBLIC_URL . '/dashboard.php');
    exit;
  } catch (Throwable $e) {
    flash('error', $e->getMessage());
    header('Location: ' . PUBLIC_URL . '/auth/login.php');
    exit;
  }
}

require_once __DIR__ . '/../../partials/header.php';
?>

<div class="flex min-h-[70vh] items-center justify-center">
  <div class="w-full max-w-md rounded-xl border border-slate-200 bg-white p-8 shadow-sm">
    <h1 class="mb-6 text-center text-2xl font-semibold tracking-tight text-slate-800">
      Iniciar sesión
    </h1>

    <form method="post" action="" class="space-y-5">
      <?= csrf_field() ?>

      <div>
        <label for="email" class="mb-1 block text-sm font-medium text-slate-700">Correo electrónico</label>
        <input
          id="email"
          name="email"
          type="email"
          required
          class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm placeholder-slate-400 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-400"
          placeholder="tu@email.com"
        >
      </div>

      <div>
        <label for="password" class="mb-1 block text-sm font-medium text-slate-700">Contraseña</label>
        <input
          id="password"
          name="password"
          type="password"
          required
          class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm placeholder-slate-400 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-400"
          placeholder="••••••••"
        >
      </div>

      <div class="flex items-center justify-between">
        <a href="<?= PUBLIC_URL ?>/auth/forgot.php" class="text-sm font-medium text-indigo-600 hover:underline">
          ¿Olvidaste la contraseña?
        </a>
      </div>

      <div class="pt-3">
        <button
          type="submit"
          class="w-full rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800 active:scale-[0.99] transition"
        >
          Entrar
        </button>
      </div>
    </form>

    <div class="mt-6 text-center text-sm text-slate-600">
      ¿No tienes cuenta?
      <a href="<?= PUBLIC_URL ?>/auth/register.php" class="font-medium text-indigo-600 hover:underline">Regístrate</a>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
