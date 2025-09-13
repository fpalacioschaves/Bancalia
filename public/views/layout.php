
<?php \Src\Core\Session::start(); $isAuth = \Src\Core\Session::check(); $rol = \Src\Core\Session::roleId(); ?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Bancalia</title>
<link rel="stylesheet" href="/Bancalia/public/assets/css/app.css" />
</head>
<body>
<nav class="topbar">
<div class="container">
<a class="brand" href="/Bancalia/public/">Bancalia</a>
<div class="spacer"></div>
<?php if($isAuth): ?>
<?php if($rol===2): ?><a href="/Bancalia/public/profesor/actividades">Profesor</a><?php endif; ?>
<?php if($rol===3): ?><a href="/Bancalia/public/alumno/actividades">Alumno</a><?php endif; ?>
<a class="btn" href="/Bancalia/public/logout">Salir</a>
<?php else: ?>
<a href="/Bancalia/public/login">Login</a>
<a class="btn" href="/Bancalia/public/register">Registro</a>
<?php endif; ?>
</div>
</nav>
<main class="container">
<?php include $path; ?>
</main>
<script>window.API_BASE='/Bancalia/api';</script>
<script defer src="/Bancalia/public/assets/js/auth.js"></script>
</body>
</html>
?>