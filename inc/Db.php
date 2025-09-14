<?php
declare(strict_types=1);

/**
 * Conexión PDO limpia. Ajusta credenciales.
 */
$DB_DSN  = 'mysql:host=localhost;dbname=bancalia;charset=utf8mb4';
$DB_USER = 'root';
$DB_PASS = '';

$pdo = new PDO($DB_DSN, $DB_USER, $DB_PASS, [
  PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  PDO::ATTR_EMULATE_PREPARES   => false,
]);

