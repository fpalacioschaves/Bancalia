<?php
use Src\Core\Session;
use Src\Config\DB;

Session::start();
$isAuth   = Session::check();
$rolId    = Session::roleId();
$rolTxt   = $rolId === 1 ? 'Admin' : ($rolId === 2 ? 'Profesor' : ($rolId === 3 ? 'Alumno' : ''));
$userId   = Session::userId();
$userName = '';

if ($isAuth && $userId) {
  try {
    $pdo = DB::pdo();
    $st  = $pdo->prepare('SELECT nombre FROM usuarios WHERE id=? LIMIT 1');
    $st->execute([$userId]);
    $row = $st->fetch();
    if ($row) { $userName = $row['nombre']; }
  } catch (\Throwable $e) { /* no romper la vista */ }
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Bancalia</title>
  <link rel="stylesheet" href="/Bancalia/public/assets/css/app.css" />
</head>
<body>
  <header class="topbar">
    <div class="container topbar-row">
      <a class="brand" href="/Bancalia/public/"><span class="logo-dot"></span> Bancalia</a>

      <nav class="mainnav" aria-label="Principal">
        <?php if ($isAuth && $rolId === 1): ?>
          <a class="link" href="/Bancalia/public/admin">Admin</a>
          <span class="divider"></span>
          <a class="link" href="/Bancalia/public/admin/gestor/grados">Grados</a>
          <a class="link" href="/Bancalia/public/admin/gestor/asignaturas">Asignaturas</a>
          <a class="link" href="/Bancalia/public/admin/gestor/temas">Temas</a>
          <a class="link" href="/Bancalia/public/admin/gestor/etiquetas">Etiquetas</a>
          <a class="link" href="/Bancalia/public/admin/gestor/actividades">Actividades</a>
          <a class="link" href="/Bancalia/public/admin/gestor/usuarios">Usuarios</a>
        <?php endif; ?>

        <?php if ($isAuth && $rolId === 2): ?>
          <a class="link" href="/Bancalia/public/profesor/actividades">Profesor</a>
        <?php endif; ?>

        <?php if ($isAuth && $rolId === 3): ?>
          <a class="link" href="/Bancalia/public/alumno/actividades">Alumno</a>
        <?php endif; ?>

        <?php if (!$isAuth): ?>
          <a class="link" href="/Bancalia/public/login">Login</a>
          <a class="btn" href="/Bancalia/public/register">Registro</a>
        <?php endif; ?>
      </nav>

      <div class="spacer"></div>

      <div class="userzone">
        <?php if ($isAuth): ?>
          <span class="userpill" title="<?= htmlspecialchars($rolTxt ?: '') ?>">
            <span class="avatar" aria-hidden="true"><?= strtoupper(substr($userName,0,1) ?: 'U') ?></span>
            <span class="uinfo">
              <strong class="uname"><?= htmlspecialchars($userName ?: '') ?></strong>
              <?php if ($rolTxt): ?><small class="urole"><?= htmlspecialchars($rolTxt) ?></small><?php endif; ?>
            </span>
          </span>
          <a class="btn ghost" href="/Bancalia/public/logout">Salir</a>
        <?php endif; ?>
      </div>
    </div>
  </header>

  <main class="container page">
    <?php include $path; ?>
  </main>

  <script>window.API_BASE='/Bancalia/api';</script>
  <script defer src="/Bancalia/public/assets/js/auth.js"></script>
</body>
</html>
