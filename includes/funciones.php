<?php
function conectar_db() {
    $host = 'localhost';
    $usuario = 'root';
    $clave = '';
    $bd = 'bancalia';
    $mysqli = new mysqli($host, $usuario, $clave, $bd);
    if ($mysqli->connect_errno) {
        die('Error de conexión: ' . $mysqli->connect_error);
    }
    return $mysqli;
}

function verificar_login($email, $password) {
    $mysqli = conectar_db();
    $stmt = $mysqli->prepare("SELECT id, password_hash FROM usuarios WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->bind_result($id, $password_hash);
    $login_ok = false;
    if ($stmt->fetch()) {
        if (password_verify($password, $password_hash)) {
            $login_ok = $id; // Login correcto, devuelve el id de usuario
        }
    }
    $stmt->close();
    $mysqli->close();
    return $login_ok ? $login_ok : false; // Login incorrecto
}
?>