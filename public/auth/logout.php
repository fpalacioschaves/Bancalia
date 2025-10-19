<?php
// /public/auth/logout.php
declare(strict_types=1);
require_once __DIR__ . '/../../config/config.php';
logout_user();
flash('success','Sesión cerrada.');
header('Location: '.BASE_URL.'/public/index.php');
exit;
