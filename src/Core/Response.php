<?php
namespace Src\Core;


final class Response {
public static function json($data, int $code=200): void {
http_response_code($code);
header('Content-Type: application/json; charset=utf-8');
echo json_encode($data, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
}


public static function view(string $view, array $data=[]): void {
$path = App::$root . '/public/views/' . ltrim($view, '/');
if (!is_file($path)) {
http_response_code(500);
echo "Vista no encontrada: $view"; return;
}
extract($data, EXTR_SKIP);
include App::$root . '/public/views/layout.php';
}
}