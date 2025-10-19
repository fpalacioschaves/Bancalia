<?php
declare(strict_types=1);
require_once __DIR__ . '/../config.php';
$u = current_user();

// URLs base por si no están definidas (auth.php ya las define por defecto)
if (!defined('BASE_URL'))   define('BASE_URL',   '/Bancalia');
if (!defined('PUBLIC_URL')) define('PUBLIC_URL', BASE_URL . '/public');


?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Bancalia</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 text-slate-900">
  <header class="sticky top-0 z-40 border-b border-slate-200 bg-white/90 backdrop-blur">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 h-14 flex items-center justify-between">
      <a href="<?= PUBLIC_URL ?>/dashboard.php" class="flex items-center gap-2 font-semibold">
        <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-slate-900 text-white">B</span>
        <span>Bancalia</span>
      </a>

      <nav class="flex items-center gap-1">
        <a href="<?= PUBLIC_URL ?>/dashboard.php" class="rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">Panel</a>

        <?php if ($u): ?>
          <?php if (($u['role'] ?? '') === 'admin'): ?>
            <a href="<?= PUBLIC_URL ?>/admin/familias/index.php" class="rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">Familias</a>
            <a href="<?= PUBLIC_URL ?>/admin/cursos/index.php" class="rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">Cursos</a>
            <a href="<?= PUBLIC_URL ?>/admin/asignaturas/index.php" class="rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">Asignaturas</a>
            <a href="<?= PUBLIC_URL ?>/admin/temas/index.php" class="rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">Temas</a>
            <a href="<?= PUBLIC_URL ?>/admin/centros/index.php" class="rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">Centros</a>
            <a href="<?= PUBLIC_URL ?>/admin/profesores/index.php" class="rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">Profesores</a>
            <!-- Admin solo ve listado de actividades (sin crear/editar/borrar) -->
            <a href="<?= PUBLIC_URL ?>/admin/actividades/index.php" class="rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">Actividades</a>

          <?php elseif (($u['role'] ?? '') === 'profesor'): ?>
            <a href="<?= PUBLIC_URL ?>/mi-perfil.php" class="rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">Mi Perfil</a>
            <a href="<?= PUBLIC_URL ?>/admin/centros/index.php" class="rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">Centros</a>
            <a href="<?= PUBLIC_URL ?>/admin/actividades/index.php" class="rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">Actividades</a>
          <?php endif; ?>
        <?php endif; ?>
      </nav>

      <div class="flex items-center gap-3">
        <?php if ($u): ?>
          <span class="hidden sm:inline text-sm text-slate-600">
            <?= h($u['role']) ?> · <?= h($u['nombre'] ?? $u['email'] ?? 'usuario') ?>
          </span>
          <a href="<?= PUBLIC_URL ?>/auth/logout.php"
             class="inline-flex items-center rounded-lg bg-slate-900 px-3 py-2 text-sm font-semibold text-white hover:bg-slate-800">Salir</a>
        <?php else: ?>
          <a href="<?= PUBLIC_URL ?>/auth/login.php" class="rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">Entrar</a>
          <a href="<?= PUBLIC_URL ?>/auth/register.php" class="inline-flex items-center rounded-lg bg-slate-900 px-3 py-2 text-sm font-semibold text-white hover:bg-slate-800">Registrarse</a>
        <?php endif; ?>
      </div>
    </div>
  </header>

  <main class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-6">
