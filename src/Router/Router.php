<?php
/*
Copia y sustituye estos archivos para que el router funcione en subcarpetas
como /Bancalia/public y /Bancalia/api (XAMPP).
*/
?>
<?php
namespace Src\Router;


class Router {
private array $routes = [];
private string $prefix;


public function __construct(string $prefix = '') { $this->prefix = rtrim($prefix, '/'); }


public function add(string $method, string $path, $handler) {
$this->routes[] = [strtoupper($method), $this->prefix . $path, $handler];
}
public function get($p,$h){$this->add('GET',$p,$h);} public function post($p,$h){$this->add('POST',$p,$h);} public function put($p,$h){$this->add('PUT',$p,$h);} public function delete($p,$h){$this->add('DELETE',$p,$h);}


public function dispatch(){
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);


// 1) Recorta la subcarpeta base (p.ej. /Bancalia/public o /Bancalia/api)
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$scriptDir = rtrim($scriptDir, '/');
if ($scriptDir && $scriptDir !== '/' && str_starts_with($uri, $scriptDir)) {
$uri = substr($uri, strlen($scriptDir));
if ($uri === '' || $uri[0] !== '/') $uri = '/' . $uri;
}


// 2) Normaliza trailing slash ("/ruta" y "/ruta/" equivalen)
if ($uri !== '/' && str_ends_with($uri, '/')) { $uri = rtrim($uri, '/'); }


foreach ($this->routes as [$m,$p,$h]) {
$regex = '@^' . preg_replace('@\\{([a-zA-Z_][a-zA-Z0-9_]*)\\}@','(?P<$1>[^/]+)',$p) . '$@';
if ($m === $method && preg_match($regex, $uri, $mats)) {
$args = array_filter($mats, 'is_string', ARRAY_FILTER_USE_KEY);
if (is_callable($h)) return $h(...array_values($args));
if (is_array($h)) return (new $h[0])->{$h[1]}(...array_values($args));
}
}
http_response_code(404); header('Content-Type: application/json'); echo json_encode(['error'=>'Not found','path'=>$uri]);
}
}
?>