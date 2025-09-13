<?php
declare(strict_types=1);

use Src\Core\Router;
use Src\Controller\HomeController;
use Src\Controller\AdminController;

/** @var Router $router */
$router->get('/', [HomeController::class, 'login']);
$router->get('/login', [HomeController::class, 'login']);
$router->get('/register', [HomeController::class, 'register']);

$router->get('/profesor/actividades', [HomeController::class, 'profesorActividades']);
$router->get('/alumno/actividades',   [HomeController::class, 'alumnoActividades']);

$router->get('/admin', [AdminController::class, 'dashboard']);
$router->get('/admin/gestor/{entity}', [AdminController::class, 'gestor']);

$router->get('/logout', [HomeController::class, 'logout']);
