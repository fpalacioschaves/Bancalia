<?php
namespace Src;


final class Autoload {
public static function register(): void {
spl_autoload_register(function ($class) {
$prefix = 'Src\\';
$baseDir = __DIR__ . DIRECTORY_SEPARATOR; // /src
$len = strlen($prefix);
if (strncmp($prefix, $class, $len) !== 0) return;
$relative = substr($class, $len);
$file = $baseDir . str_replace('\\', DIRECTORY_SEPARATOR, $relative) . '.php';
if (is_file($file)) require $file;
});
}
}