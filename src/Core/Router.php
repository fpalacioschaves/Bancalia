<?php
namespace Src\Core;


final class Router {
private array $routes = [];


public function add(string $method, string $path, $handler): void {
$this->routes[] = [strtoupper($method), $path, $handler];
}
public function get($p,$h){$this->add('GET',$p,$h);} public function post($p,$h){$this->add('POST',$p,$h);} public function put($p,$h){$this->add('PUT',$p,$h);} public function delete($p,$h){$this->add('DELETE',$p,$h);}


public function dispatch(Request $req): void {
foreach ($this->routes as [$m,$p,$h]) {
if ($m !== $req->method) continue;
$regex = '@^' . preg_replace('@\\{([a-zA-Z_][a-zA-Z0-9_]*)\\}@','(?P<$1>[^/]+)',$p) . '$@';
if (preg_match($regex, $req->path, $mats)) {
$params = array_filter($mats, 'is_string', ARRAY_FILTER_USE_KEY);
if (is_callable($h)) { echo $h($req, ...array_values($params)); return; }
if (is_array($h)) { $obj = new $h[0]; echo $obj->{$h[1]}($req, ...array_values($params)); return; }
}
}
http_response_code(404);
if (str_contains($req->basePath, '/api')) {
Response::json(['error'=>'Not found','path'=>$req->path], 404);
} else {
echo '<h1>404</h1><p>Página no encontrada.</p>';
}
}
}