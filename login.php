<?php
session_start();
require_once __DIR__ . '/includes/funciones.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = isset($_POST['usuario']) ? trim($_POST['usuario']) : '';
    $password = isset($_POST['clave']) ? $_POST['clave'] : '';
    $user_id = verificar_login($email, $password);

    if ($user_id) {
        $_SESSION['usuario_id'] = $user_id;
        if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
            echo json_encode(['success' => true, 'redirect' => 'panel.php']);
            exit;
        } else {
            header('Location: panel.php');
            exit;
        }
    } else {
        if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
            echo json_encode(['success' => false, 'error' => 'Usuario o contraseña incorrectos.']);
            exit;
        }
        header('Location: index.php');
        exit;
    }
}
?>