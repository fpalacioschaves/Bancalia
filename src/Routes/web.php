<?php
use Src\Core\Router; use Src\Controller\HomeController;
/* @var $router Router */
$router->get('/', [HomeController::class, 'login']);
$router->get('/login', [HomeController::class, 'login']);
$router->get('/register', [HomeController::class, 'register']);
$router->get('/profesor/actividades', [HomeController::class, 'profesorActividades']);
$router->get('/alumno/actividades', [HomeController::class, 'alumnoActividades']);
$router->get('/logout', [HomeController::class, 'logout']);