<?php
namespace Src\Core;


use Throwable;


final class ErrorHandler {
public static function register(bool $api=false): void {
ini_set('display_errors', '1');
error_reporting(E_ALL);
set_exception_handler(function (Throwable $e) use ($api) {
http_response_code(500);
if ($api) {
Response::json(['error'=>'Internal Server Error','message'=>$e->getMessage()]);
} else {
$msg = htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
echo "<h1>500 — Internal Server Error</h1><pre>$msg</pre>";
}
});
}
}