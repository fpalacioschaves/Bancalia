<?php
// Redirige automáticamente a la carpeta /public
$host = $_SERVER['HTTP_HOST'] ?? '';
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
header("Location: $scheme://$host/public/");
exit;