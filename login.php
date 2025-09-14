<?php
declare(strict_types=1);

require __DIR__ . '/inc/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: /Bancalia/index.php');
  exit;
}

header('Content-Type: application/json; charset=utf-8');

try {
  $email = trim((string)($_POST['usuario'] ?? $_POST['email'] ?? ''));
  $pass  = (string)($_POST['clave'] ?? $_POST['password'] ?? '');
  if ($email === '' || $pass === '') {
    throw new RuntimeException('Faltan credenciales.');
  }
  $user = login_with_credentials($pdo, $email, $pass);
  echo json_encode([
    'success'  => true,
    'user'     => ['id'=>$user['id'],'nombre'=>$user['nombre'],'rol_id'=>$user['rol_id']],
    'redirect' => '/Bancalia/panel.php'
  ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(400);
  echo json_encode(['success'=>false,'error'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
}
