<?php
declare(strict_types=1);
require __DIR__ . '/inc/auth.php';
require_login();

$user = current_user();
$rol  = current_role_id(); // 1=admin, 2=profesor, 3=alumno
$activeTab = $_GET['tab'] ?? 'catalogo';
?>
<?php include __DIR__ . '/views/layout/header.php'; ?>

<?php if ($rol !== 1): ?>
  <main class="max-w-3xl mx-auto px-4 py-8">
    <div class="card p-6">
      <h2 class="text-base font-semibold mb-2">Acceso restringido</h2>
      <p class="text-gray-600">Esta sección es solo para administradores.</p>
    </div>
  </main>
<?php else: ?>
  <main class="max-w-6xl mx-auto px-4 py-6">
    <?php include __DIR__ . '/views/layout/tabs.php'; ?>

    <?php
      include __DIR__ . '/views/admin/resumen.php';
      include __DIR__ . '/views/admin/usuarios.php';
      include __DIR__ . '/views/admin/actividades.php'; // NUEVO
      include __DIR__ . '/views/admin/catalogos.php';
      include __DIR__ . '/views/admin/informes.php';
    ?>
  </main>

  <script>
    window.CURRENT_USER_ID = <?= (int)$user['id'] ?>;
    window.ACTIVE_TAB = <?= json_encode($activeTab, JSON_UNESCAPED_UNICODE) ?>;
  </script>

  <?php include __DIR__ . '/views/layout/footer.php'; ?>
<?php endif; ?>
