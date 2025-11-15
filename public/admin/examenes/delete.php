<?php
// public/examenes/delete.php
// Borra un examen por ID (POST).

// TODO: ajusta la ruta según tu proyecto
require_once __DIR__ . '/../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

if ($id <= 0) {
    die('ID de examen inválido.');
}

try {
    // Gracias a la FK con ON DELETE CASCADE, si la tienes,
    // se borrarán también las relaciones en examenes_actividades.
    $stmt = $pdo->prepare("DELETE FROM examenes WHERE id = :id");
    $stmt->execute([':id' => $id]);

} catch (PDOException $e) {
    die('Error al borrar el examen: ' . htmlspecialchars($e->getMessage()));
}

// Volvemos al listado
header('Location: index.php');
exit;
