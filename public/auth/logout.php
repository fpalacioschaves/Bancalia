<?php
// /public/auth/logout.php
declare(strict_types=1);
require_once __DIR__ . '/../../config.php';

logout_user(); // borra sesión y cookies
flash('success','Sesión cerrada.');
header('Location: ' . PUBLIC_URL . '/auth/login.php'); exit;
