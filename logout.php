<?php
declare(strict_types=1);
require __DIR__ . '/inc/auth.php';
logout();
header('Location: /Bancalia/index.php');
