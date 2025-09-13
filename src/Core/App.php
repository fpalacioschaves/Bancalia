<?php
namespace Src\Core;


final class App {
public static string $root;
public static function boot(string $root): void {
self::$root = rtrim(str_replace('\\','/',$root), '/');
date_default_timezone_set('Europe/Madrid');
Env::load(self::$root . '/.env');
}
}