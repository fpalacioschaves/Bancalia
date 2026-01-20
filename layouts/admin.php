<?php
// /layouts/admin.php
declare(strict_types=1);

// Ensure configuration is loaded if not already (header handles it, but good practice)
require_once __DIR__ . '/../config.php';

// Variables expected:
// $content (string): The main content of the page.
// $pageTitle (string): Optional page title (currently header.php doesn't use it dynamically, but good for future).

require_once __DIR__ . '/../partials/header.php';
?>

<!-- Layout Content Wrapper -->
<div class="layout-admin">
    <?= $content ?? '' ?>
</div>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>