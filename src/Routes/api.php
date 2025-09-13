<?php
use Src\Core\Router; use Src\Controller\AuthController; use Src\Controller\ActividadController; use Src\Controller\CatalogoController;
/* @var $router Router */
$router->post('/login', [AuthController::class, 'login']);
$router->post('/logout', [AuthController::class, 'logout']);
$router->post('/register', [AuthController::class, 'register']);
$router->get('/me', [AuthController::class, 'me']);
$router->get('/grados', [CatalogoController::class, 'grados']);
$router->get('/cursos', [CatalogoController::class, 'cursos']);
$router->get('/asignaturas', [CatalogoController::class, 'asignaturas']);
$router->get('/actividades', [ActividadController::class, 'list']);
$router->get('/catalogo', [CatalogoController::class, 'index']);
?>