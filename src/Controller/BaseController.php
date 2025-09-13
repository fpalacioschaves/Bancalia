<?php
namespace Src\Controller;


use Src\Core\Response;


abstract class BaseController {
protected function view(string $view, array $data=[]): void { Response::view($view,$data); }
protected function json($data, int $code=200): void { Response::json($data,$code); }
}