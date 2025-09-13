<?php
namespace Src\Core;


final class Request {
public string $method;
public string $path;
public string $basePath;


public function __construct() {
$this->method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
$scriptDir = str_replace('\\','/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$scriptDir = rtrim($scriptDir, '/');
$this->basePath = $scriptDir === '/' ? '' : $scriptDir; // p.ej. /Bancalia/public o /Bancalia/api
if ($this->basePath && str_starts_with($uri, $this->basePath)) {
$uri = substr($uri, strlen($this->basePath)) ?: '/';
}
if ($uri !== '/' && str_ends_with($uri, '/')) $uri = rtrim($uri, '/');
$this->path = $uri === '' ? '/' : $uri;
}


public function json(): array {
$raw = file_get_contents('php://input');
$data = json_decode($raw ?: '', true);
return is_array($data) ? $data : [];
}


public function query(): array { return $_GET; }
}