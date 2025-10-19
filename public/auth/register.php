<?php
// /public/auth/register.php
declare(strict_types=1);

require_once __DIR__ . '/../../middleware/require_guest.php';
require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../partials/header.php';

// POST: procesar registro
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    csrf_check($_POST['csrf'] ?? null);

    $nombre    = trim((string)($_POST['nombre'] ?? ''));
    $email     = trim((string)($_POST['email'] ?? ''));
    $password  = (string)($_POST['password'] ?? '');
    $password2 = (string)($_POST['password2'] ?? '');

    if ($nombre === '')   throw new RuntimeException('El nombre es obligatorio.');
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
      throw new RuntimeException('Email no válido.');
    }
    if ($password === '') throw new RuntimeException('La contraseña es obligatoria.');
    if ($password !== $password2) throw new RuntimeException('Las contraseñas no coinciden.');

    // Unicidad por email en users
    $st = pdo()->prepare('SELECT 1 FROM users WHERE email = :e LIMIT 1');
    $st->execute([':e' => $email]);
    if ($st->fetch()) {
      throw new RuntimeException('Ya existe un usuario con ese email.');
    }

    // Insert en users
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $insUser = pdo()->prepare('
      INSERT INTO users (nombre, email, password_hash, role, is_active)
      VALUES (:n, :e, :h, "profesor", 1)
    ');
    $insUser->execute([
      ':n' => $nombre,
      ':e' => $email,
      ':h' => $hash,
    ]);
    $userId = (int) pdo()->lastInsertId();

    // Crear profesor enlazado por email (usaremos el email para localizar su perfil)
    // Si ya existiera un profesor con ese email, NO duplicamos: lo reutilizamos.
    $stProf = pdo()->prepare('SELECT id FROM profesores WHERE email = :e LIMIT 1');
    $stProf->execute([':e' => $email]);
    $prof = $stProf->fetch();

    if ($prof) {
      $profesorId = (int)$prof['id'];
      // Asegura nombre si estaba vacío
      $upd = pdo()->prepare('UPDATE profesores SET nombre = COALESCE(NULLIF(nombre,""), :n) WHERE id=:id');
      $upd->execute([':n'=>$nombre, ':id'=>$profesorId]);
    } else {
      $insProf = pdo()->prepare('
        INSERT INTO profesores (centro_id, nombre, apellidos, email, telefono, notas, is_active)
        VALUES (NULL, :n, "", :e, NULL, NULL, 1)
      ');
      $insProf->execute([':n'=>$nombre, ':e'=>$email]);
      $profesorId = (int) pdo()->lastInsertId();
    }

    // Autologin
    $_SESSION['user'] = [
      'id'          => $userId,
      'username'    => $email,     // mantenemos esta clave por compatibilidad con current_user()
      'role'        => 'profesor',
      'profesor_id' => $profesorId,
      'nombre'      => $nombre,
      'email'       => $email,
    ];

    flash('success', 'Registro completado. Ahora puedes completar tu perfil.');
    header('Location: ' . PUBLIC_URL . '/mi-perfil.php');
    exit;

  } catch (Throwable $e) {
    flash('error', $e->getMessage());
    header('Location: ' . PUBLIC_URL . '/auth/register.php');
    exit;
  }
}
?>

<div class="min-h-[80vh] flex items-center justify-center py-8">
  <div class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    <h1 class="text-xl font-semibold tracking-tight">Crear cuenta</h1>
    <p class="mt-1 text-sm text-slate-600">Regístrate para poder completar tu perfil y asignaciones.</p>

    <form method="post" action="" class="mt-5 space-y-4">
      <?= csrf_field() ?>

      <div>
        <label for="nombre" class="mb-1 block text-sm font-medium text-slate-700">Nombre</label>
        <input id="nombre" name="nombre" type="text" required
               class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-400">
      </div>

      <div>
        <label for="email" class="mb-1 block text-sm font-medium text-slate-700">Email</label>
        <input id="email" name="email" type="email" required
               autocomplete="email"
               class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-400">
      </div>

      <div>
        <label for="password" class="mb-1 block text-sm font-medium text-slate-700">Contraseña</label>
        <input id="password" name="password" type="password" required
               autocomplete="new-password"
               class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-400">
      </div>

      <div>
        <label for="password2" class="mb-1 block text-sm font-medium text-slate-700">Repite la contraseña</label>
        <input id="password2" name="password2" type="password" required
               autocomplete="new-password"
               class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-400">
      </div>

      <button type="submit"
              class="w-full rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
        Crear cuenta
      </button>

      <p class="mt-2 text-center text-xs text-slate-600">
        ¿Ya tienes cuenta?
        <a href="<?= PUBLIC_URL ?>/auth/login.php" class="text-indigo-600 hover:underline font-medium">Inicia sesión</a>.
      </p>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
