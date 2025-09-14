<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

require __DIR__ . '/../../inc/auth.php';
require_login();
if (!is_admin()) { http_response_code(403); echo json_encode(['ok'=>false,'error'=>'Solo admin']); exit; }

try {
  // KPIs básicos
  $kpis = [];

  $kpis['usuarios_total'] = (int)$pdo->query('SELECT COUNT(*) FROM usuarios')->fetchColumn();
  $kpis['admins']      = (int)$pdo->query('SELECT COUNT(*) FROM usuarios WHERE rol_id=1')->fetchColumn();
  $kpis['profesores']  = (int)$pdo->query('SELECT COUNT(*) FROM usuarios WHERE rol_id=2')->fetchColumn();
  $kpis['alumnos']     = (int)$pdo->query('SELECT COUNT(*) FROM usuarios WHERE rol_id=3')->fetchColumn();

  // Actividades (según tu esquema actividades/visibilidad/estado)
  $kpis['actividades_total']      = (int)$pdo->query('SELECT COUNT(*) FROM actividades')->fetchColumn();
  $kpis['actividades_publicadas'] = (int)$pdo->query("SELECT COUNT(*) FROM actividades WHERE estado='publicada'")->fetchColumn();
  $kpis['actividades_compartidas']= (int)$pdo->query("SELECT COUNT(*) FROM actividades WHERE visibilidad='compartida'")->fetchColumn();
  $kpis['actividades_privadas']   = (int)$pdo->query("SELECT COUNT(*) FROM actividades WHERE visibilidad='privada'")->fetchColumn();

  // Últimos 8 usuarios
  $st = $pdo->query("SELECT u.id, u.nombre, u.email, u.creado_en, r.nombre AS rol
                     FROM usuarios u JOIN roles r ON r.id=u.rol_id
                     ORDER BY u.creado_en DESC
                     LIMIT 8");
  $recentes = $st->fetchAll();

  echo json_encode(['ok'=>true, 'kpis'=>$kpis, 'recentes'=>$recentes], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false, 'error'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
}
