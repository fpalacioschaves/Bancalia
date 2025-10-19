<?php
// /middleware/require_auth.php
declare(strict_types=1);
require_once __DIR__ . '/../config/config.php';
if (!current_user()) {
  header('Location: '.BASE_URL.'/public/auth/login.php');
  exit;
}
