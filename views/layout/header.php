<?php
// Usa $user si existe
?><!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <title>Bancalia — Panel</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="/Bancalia/assets/css/app.css" />
  <style>
    .ui-input, .ui-select, .ui-btn { height: 2.5rem; font-size:.875rem; line-height:1.25rem; border-radius:.5rem; }
    .ui-input, .ui-select { border:1px solid #e5e7eb; padding:.5rem .75rem; }
    .ui-btn { padding:.5rem 1rem; }
    .tablink { height:2.5rem; font-size:.875rem; border-radius:.75rem; border:1px solid #e5e7eb; background:#fff; transition:all .15s; }
    .tablink[aria-selected="true"] { border-color:#93c5fd; background:#eff6ff; }
    .card { background:#fff; border:1px solid #e5e7eb; border-radius:.75rem; }
    thead th { font-weight:600; }
    tbody tr:nth-child(even){ background:#fafafa; }
    .table-wrap { max-height:520px; overflow:auto; }
    .sticky-th { position:sticky; top:0; background:#fff; z-index:1; }
    .btn-disabled { opacity:.5; cursor:not-allowed; }
    .tag { font-size:.75rem; padding:.125rem .5rem; border-radius:.5rem; }
    .tag-ok { background:#e0f2fe; color:#075985; }
    .tag-bad{ background:#fee2e2; color:#7f1d1d; }
    .btn-red  { background:#ef4444; color:#fff; } .btn-red:hover { background:#dc2626; }
    .btn-gray { background:#6b7280; color:#fff; } .btn-gray:hover { background:#4b5563; }
    .btn-green{ background:#16a34a; color:#fff; } .btn-green:hover{ background:#15803d; }
    .btn-blue { background:#2563eb; color:#fff; } .btn-blue:hover{ background:#1d4ed8; }
  </style>
</head>
<body class="bg-gray-100 min-h-screen text-gray-900">
  <header class="bg-white border-b">
    <div class="max-w-6xl mx-auto px-4 py-3 flex items-center justify-between">
      <div class="flex items-center gap-3">
        <img src="/Bancalia/assets/images/logo.png" alt="Bancalia" class="w-9 h-9" />
        <h1 class="text-lg font-semibold">Panel</h1>
      </div>
      <div class="text-sm text-gray-600">
        <?php if (!empty($user)): ?>
          <span class="mr-3">Hola, <?= htmlspecialchars($user['nombre']) ?></span>
        <?php endif; ?>
        <a href="/Bancalia/logout.php" class="text-blue-600 hover:underline">Salir</a>
      </div>
    </div>
  </header>
