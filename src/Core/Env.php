<?php
namespace Src\Core;


final class Env {
public static function load(string $path): void {
if (!is_file($path)) return;
foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
if ($line[0] === '#' || !str_contains($line, '=')) continue;
[$k,$v] = array_map('trim', explode('=', $line, 2));
$v = trim($v, "\"' ");
$_ENV[$k] = $v; putenv("$k=$v");
}
}
}