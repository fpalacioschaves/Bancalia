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

// --- PROFESOR ---
$router->get('/profesor', [\Src\Controller\ProfesorController::class, 'home']);
;

// Perfil (vista)
$router->get('/profesor/perfil', [\Src\Controller\ProfesorPerfilController::class, 'page']);

// API perfil (datos básicos)
$router->get ('/api/profesor/perfil',  [\Src\Controller\ProfesorPerfilController::class, 'apiGet']);
$router->put ('/api/profesor/perfil',  [\Src\Controller\ProfesorPerfilController::class, 'apiUpdate']);
// Alias por si el router no maneja PUT:
$router->post('/api/profesor/perfil/update', [\Src\Controller\ProfesorPerfilController::class, 'apiUpdate']);

// API materias impartidas
$router->post  ('/api/profesor/imparte',        [\Src\Controller\ProfesorPerfilController::class, 'apiImparteAdd']);
$router->delete('/api/profesor/imparte/{id}',   [\Src\Controller\ProfesorPerfilController::class, 'apiImparteDelete']);
// Alias por si el router no maneja DELETE:
$router->post  ('/api/profesor/imparte/delete/{id}', [\Src\Controller\ProfesorPerfilController::class, 'apiImparteDelete']);

$router->get('/profesor/actividades', [\Src\Controller\ProfesorActividadesController::class, 'pageIndex']);





