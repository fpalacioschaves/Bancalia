<?php
require __DIR__ . '/../src/Autoload.php';
\Src\Autoload::register();


use Src\Core\App;
use Src\Core\Request;
use Src\Core\Router;
use Src\Core\ErrorHandler;


ErrorHandler::register(/* api = */ true);
App::boot(__DIR__ . '/..');


$req = new Request();
$router = new Router();


require __DIR__ . '/../src/Routes/api.php';


$router->dispatch($req);