<?php
declare(strict_types=1);

use Src\Core\Router;
use Src\Controller\AuthController;
use Src\Controller\ActividadController;
use Src\Controller\CatalogoController;
use Src\Controller\AdminCrudController;
use Src\Controller\ProfesorPerfilController;
use Src\Controller\ProfesorActividadesController;

/** @var Router $router */

// Auth
$router->post('/login',    [AuthController::class, 'login']);
$router->post('/logout',   [AuthController::class, 'logout']);
$router->post('/register', [AuthController::class, 'register']);
$router->get('/me',        [AuthController::class, 'me']);

// Catálogo básico
$router->get('/grados',        [CatalogoController::class, 'grados']);
$router->get('/cursos',        [CatalogoController::class, 'cursos']);      // ?grado_id=...
$router->get('/asignaturas',   [CatalogoController::class, 'asignaturas']); // ?grado_id=...

// Otros
$router->get('/actividades',   [ActividadController::class, 'list']);
$router->get('/catalogo',      [CatalogoController::class, 'index']);

// Admin CRUD
$router->get   ('/admin/meta/{entity}', [AdminCrudController::class, 'meta']);
$router->get   ('/admin/{entity}',      [AdminCrudController::class, 'index']);
$router->get   ('/admin/{entity}/{id}', [AdminCrudController::class, 'show']);
$router->post  ('/admin/{entity}',      [AdminCrudController::class, 'store']);
$router->put   ('/admin/{entity}/{id}', [AdminCrudController::class, 'update']);
$router->delete('/admin/{entity}/{id}', [AdminCrudController::class, 'destroy']);

// ===== PERFIL PROFESOR (API) =====
// OJO: este archivo ya vive bajo /api, por eso las rutas NO llevan /api delante
$router->get ('/profesor/perfil',               [ProfesorPerfilController::class, 'apiGet']);
$router->put ('/profesor/perfil',               [ProfesorPerfilController::class, 'apiUpdate']);
$router->post('/profesor/perfil/update',        [ProfesorPerfilController::class, 'apiUpdate']); // alias PUT

$router->post ('/profesor/imparte',             [ProfesorPerfilController::class, 'apiImparteAdd']);
$router->delete('/profesor/imparte/{id}',       [ProfesorPerfilController::class, 'apiImparteDelete']);
$router->post ('/profesor/imparte/delete/{id}', [ProfesorPerfilController::class, 'apiImparteDelete']); // alias DELETE

// ===== ACTIVIDADES PROFESOR (API) =====
$router->get('/profesor/actividades', [ProfesorActividadesController::class, 'apiIndex']);
