<?php
use Src\Router\Router;
use Src\Controller\AuthController;
use Src\Controller\ActividadController;
use Src\Controller\EntregaController;
use Src\Controller\CatalogoController;


// IMPORTANTE: sin prefijo aquí. El recorte de base ya lo hace el Router
$router = new Router();


// Auth
$router->post('/login', [AuthController::class, 'login']);
$router->post('/logout', [AuthController::class, 'logout']);


// Catálogo
$router->get('/catalogo', [CatalogoController::class, 'index']);


// Actividades
$router->get('/actividades', [ActividadController::class, 'list']);


$router->dispatch();
?>