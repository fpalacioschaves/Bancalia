<?php
// /public/auth/forgot.php
declare(strict_types=1);
require_once __DIR__ . '/../../middleware/require_guest.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    csrf_check($_POST['csrf'] ?? null);
    $email = trim($_POST['email'] ?? '');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      throw new RuntimeException('Email no válido.');
    }
    // Generar token y guardar
    $st = pdo()->prepare('SELECT id FROM users WHERE email=:e LIMIT 1');
    $st->execute([':e'=>mb_strtolower($email)]);
    if ($u = $st->fetch()) {
      $token = bin2hex(random_bytes(32));
      $exp = (new DateTime('+1 hour'))->format('Y-m-d H:i:s');
      $up = pdo()->prepare('UPDATE users SET reset_token=:t, reset_expires_at=:x WHERE id=:id');
      $up->execute([':t'=>$token, ':x'=>$exp, ':id'=>$u['id']]);
      // Aquí enviarías email con enlace:
      // $link = BASE_URL."/public/auth/reset.php?token=$token";
    }
    flash('success','Si el email existe, recibirás instrucciones.');
    header('Location: '.BASE_URL.'/public/auth/forgot.php');
    exit;
  } catch (Throwable $e) {
    flash('error',$e->getMessage());
    header('Location: '.BASE_URL.'/public/auth/forgot.php');
    exit;
  }
}

require_once __DIR__ . '/../../partials/header.php';
?>
<h1>Recuperar contraseña</h1>
<form method="post" action="">
  <?= csrf_field() ?>
  <label>Email
    <input type="email" name="email" required>
  </label>
  <button type="submit">Enviar</button>
</form>
<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
