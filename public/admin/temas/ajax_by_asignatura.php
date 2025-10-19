<?php
// /public/admin/temas/ajax_by_asignatura.php
declare(strict_types=1);

require_once __DIR__ . '/../../../middleware/require_auth.php';

// Cabeceras JSON
header('Content-Type: application/json; charset=utf-8');

try {
  // Validar parámetro
  $asignatura_id = isset($_GET['asignatura_id']) ? (int)$_GET['asignatura_id'] : 0;
  if ($asignatura_id <= 0) {
    echo json_encode([]); exit;
  }

  // Traer temas activos de esa asignatura
  $st = pdo()->prepare('
    SELECT id, nombre
    FROM temas
    WHERE asignatura_id = :a AND is_active = 1
    ORDER BY numero ASC, nombre ASC
  ');
  $st->execute([':a' => $asignatura_id]);
  $rows = $st->fetchAll() ?: [];

  // Normalizar salida
  $out = [];
  foreach ($rows as $r) {
    $out[] = ['id' => (int)$r['id'], 'nombre' => (string)$r['nombre']];
  }

  echo json_encode($out, JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  // En caso de error, devolvemos array vacío (mejor UX que 500 en un selector)
  echo json_encode([]);
}
