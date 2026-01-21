<?php
// /public/admin/examenes/delete.php
declare(strict_types=1);

require_once __DIR__ . '/../../../config.php';
require_login_or_redirect();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . PUBLIC_URL . '/admin/examenes/index.php');
    exit;
}

try {
    csrf_check($_POST['csrf'] ?? null);

    $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
    if ($id <= 0) {
        throw new RuntimeException('ID de examen inválido.');
    }

    $examenService = new ExamenService(pdo());
    $examenService->delete($id);

    flash('success', 'Examen eliminado correctamente.');

} catch (Throwable $e) {
    flash('error', 'Error al borrar el examen: ' . $e->getMessage());
}

header('Location: ' . PUBLIC_URL . '/admin/examenes/index.php');
exit;
