<?php
// /public/auth/reset.php
declare(strict_types=1);
require_once __DIR__ . '/../../middleware/require_guest.php';

$token = $_GET['token'] ?? '';
if ($token === '') {
  flash('error','Token no proporcionado.');
  header('Location: '.BASE_URL.'/public/auth/forgot.php');
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    csrf_check($_POST['csrf'] ?? null);
    $pass = trim($_POST['password'] ?? '');
    if (strlen($pass) < 8) {
      throw new RuntimeException('La contraseña debe tener al menos 8 caracteres.');
    }
    $st = pdo()->prepare('SELECT id FROM users WHERE reset_token=:t AND reset_expires_at > NOW() LIMIT 1');
    $st->execute([':t'=>$token]);
    $u = $st->fetch();
    if (!$u) throw new RuntimeException('Token inválido o caducado.');

    $hash = password_hash($pass, PASSWORD_DEFAULT);
    $up = pdo()->prepare('UPDATE users SET password_hash=:h, reset_token=NULL, reset_expires_at=NULL WHERE id=:id');
    $up->execute([':h'=>$hash, ':id'=>$u['id']]);

    flash('success','Contraseña actualizada. Inicia sesión.');
    header('Location: '.BASE_URL.'/public/auth/login.php');
    exit;
  } catch (Throwable $e) {
    flash('error',$e->getMessage());
    header('Location: '.BASE_URL.'/public/auth/reset.php?token='.$token);
    exit;
  }
}

require_once __DIR__ . '/../../partials/header.php';
?>
<h1>Restablecer contraseña</h1>
<form method="post" action="">
  <?= csrf_field() ?>
  <label>Nueva contraseña
    <input type="password" name="password" required minlength="8">
  </label>
  <button type="submit">Actualizar</button>
</form>
<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
