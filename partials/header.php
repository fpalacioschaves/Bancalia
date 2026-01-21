<?php
declare(strict_types=1);
require_once __DIR__ . '/../config.php';
$u = current_user();

// URLs base se definen centralizadamente en config.php
if (!defined('PUBLIC_URL')) {
  // Fallback de emergencia, aunque require_once config.php ya lo hace arriba
  define('PUBLIC_URL', '/public');
}


?>
<!doctype html>
<html lang="es">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Bancalia</title>
  <script src="https://cdn.tailwindcss.com?plugins=forms,typography,aspect-ratio"></script>
  <!-- Simple-DataTables CDN -->
  <link href="https://cdn.jsdelivr.net/npm/simple-datatables@latest/dist/style.css" rel="stylesheet" type="text/css">
  <script src="https://cdn.jsdelivr.net/npm/simple-datatables@latest/dist/umd/simple-datatables.js"
    type="text/javascript"></script>
  <style>
    /* Custom Datatables Styling to match Bancalia */
    .datatable-input,
    .datatable-selector {
      border-color: #cbd5e1 !important;
      /* slate-300 */
      border-radius: 0.5rem !important;
      /* rounded-lg */
      padding: 0.4rem 0.75rem !important;
      font-size: 0.875rem !important;
      /* text-sm */
      background-color: white !important;
    }

    .datatable-input:focus,
    .datatable-selector:focus {
      outline: 2px solid transparent !important;
      outline-offset: 2px !important;
      --tw-ring-offset-shadow: var(--tw-ring-inset) 0 0 0 var(--tw-ring-offset-width) var(--tw-ring-offset-color) !important;
      --tw-ring-shadow: var(--tw-ring-inset) 0 0 0 calc(2px + var(--tw-ring-offset-width)) #94a3b8 !important;
      /* focus:ring-slate-400 */
      box-shadow: var(--tw-ring-offset-shadow), var(--tw-ring-shadow), var(--tw-shadow, 0 0 #0000) !important;
      border-color: #94a3b8 !important;
    }

    .datatable-dropdown label {
      font-size: 0.875rem !important;
      color: #475569 !important;
      /* slate-600 */
    }

    .datatable-info {
      font-size: 0.875rem !important;
      color: #475569 !important;
    }

    .datatable-pagination a {
      border-radius: 0.375rem !important;
      margin: 0 0.125rem !important;
      padding: 0.4rem 0.75rem !important;
      font-size: 0.875rem !important;
    }

    .datatable-pagination .active a {
      background-color: #0f172a !important;
      /* slate-900 */
      color: white !important;
    }

    /* Fix for the overlapping selector */
    .datatable-dropdown {
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }
  </style>
</head>

<body class="bg-slate-50 text-slate-900">
  <header class="sticky top-0 z-40 border-b border-slate-200 bg-white/90 backdrop-blur">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 h-14 flex items-center justify-between">
      <a href="<?= PUBLIC_URL ?>/dashboard.php" class="flex items-center gap-2 font-semibold">
        <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-slate-900 text-white">B</span>
        <span>Bancalia</span>
      </a>

      <nav class="flex items-center gap-1">
        <a href="<?= PUBLIC_URL ?>/dashboard.php"
          class="rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">Panel</a>

        <?php if ($u): ?>
          <?php if (($u['role'] ?? '') === 'admin'): ?>
            <a href="<?= PUBLIC_URL ?>/admin/familias/index.php"
              class="rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">Familias</a>
            <a href="<?= PUBLIC_URL ?>/admin/cursos/index.php"
              class="rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">Cursos</a>
            <a href="<?= PUBLIC_URL ?>/admin/asignaturas/index.php"
              class="rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">Asignaturas</a>
            <a href="<?= PUBLIC_URL ?>/admin/temas/index.php"
              class="rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">Temas</a>
            <a href="<?= PUBLIC_URL ?>/admin/centros/index.php"
              class="rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">Centros</a>
            <a href="<?= PUBLIC_URL ?>/admin/profesores/index.php"
              class="rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">Profesores</a>
            <!-- Admin solo ve listado de actividades (sin crear/editar/borrar) -->
            <a href="<?= PUBLIC_URL ?>/admin/actividades/index.php"
              class="rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">Actividades</a>
            <a href="<?= PUBLIC_URL ?>/admin/examenes/index.php"
              class="rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">Exámenes</a>

          <?php elseif (($u['role'] ?? '') === 'profesor'): ?>
            <a href="<?= PUBLIC_URL ?>/mi-perfil.php"
              class="rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">Mi Perfil</a>

            <!-- AÑADIDO: el profesor también puede gestionar catálogo académico -->
            <a href="<?= PUBLIC_URL ?>/admin/familias/index.php"
              class="rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">Familias</a>
            <a href="<?= PUBLIC_URL ?>/admin/cursos/index.php"
              class="rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">Cursos</a>
            <a href="<?= PUBLIC_URL ?>/admin/asignaturas/index.php"
              class="rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">Asignaturas</a>
            <a href="<?= PUBLIC_URL ?>/admin/temas/index.php"
              class="rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">Temas</a>

            <a href="<?= PUBLIC_URL ?>/admin/centros/index.php"
              class="rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">Centros</a>
            <a href="<?= PUBLIC_URL ?>/admin/actividades/index.php"
              class="rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">Actividades</a>
            <a href="<?= PUBLIC_URL ?>/admin/examenes/index.php"
              class="rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">Exámenes</a>
          <?php endif; ?>
        <?php endif; ?>
        <a href="<?= PUBLIC_URL ?>/ayuda.php"
          class="rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">Ayuda</a>
      </nav>

      <div class="flex items-center gap-3">
        <?php if ($u): ?>
          <span class="hidden sm:inline text-sm text-slate-600">
            <?= h($u['role']) ?> · <?= h($u['nombre'] ?? $u['email'] ?? 'usuario') ?>
          </span>
          <a href="<?= PUBLIC_URL ?>/auth/logout.php"
            class="inline-flex items-center rounded-lg bg-slate-900 px-3 py-2 text-sm font-semibold text-white hover:bg-slate-800">Salir</a>
        <?php else: ?>
          <a href="<?= PUBLIC_URL ?>/auth/login.php"
            class="rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">Entrar</a>
          <a href="<?= PUBLIC_URL ?>/auth/register.php"
            class="inline-flex items-center rounded-lg bg-slate-900 px-3 py-2 text-sm font-semibold text-white hover:bg-slate-800">Registrarse</a>
        <?php endif; ?>
      </div>
    </div>
  </header>

  <main class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-6">