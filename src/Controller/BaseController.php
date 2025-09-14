<?php
declare(strict_types=1);

namespace Src\Controller;

abstract class BaseController
{
    protected function json(mixed $data, int $status = 200): void
    {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code($status);
        }
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    protected function redirect(string $url, int $status = 302): void
    {
        if (!headers_sent()) header('Location: '.$url, true, $status);
        exit;
    }

    /**
     * Renderiza una vista dentro del layout.
     * Acepta 'carpeta/vista' o 'carpeta/vista.php'.
     * Si no existe, prueba automáticamente 'carpeta/vista/index.php'.
     */
    protected function view(string $view, array $data = []): void
    {
        $base = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'views';
        $rel  = str_replace(['\\','/'], DIRECTORY_SEPARATOR, ltrim($view, '/'));
        $hasExt = (substr($rel, -4) === '.php');

        $candidates = [];
        if ($hasExt) {
            $candidates[] = $base . DIRECTORY_SEPARATOR . $rel;
        } else {
            $candidates[] = $base . DIRECTORY_SEPARATOR . $rel . '.php';
            $candidates[] = $base . DIRECTORY_SEPARATOR . rtrim($rel, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'index.php';
        }

        $file = null;
        foreach ($candidates as $cand) {
            if (is_file($cand)) { $file = $cand; break; }
        }

        if (!$file) {
            if (!headers_sent()) http_response_code(500);
            echo 'Vista no encontrada: ' . htmlspecialchars($view) . ' (buscadas: ' . implode(' | ', $candidates) . ')';
            exit;
        }

        extract($data, EXTR_SKIP);

        $path   = $file;
        $layout = $base . DIRECTORY_SEPARATOR . 'layout.php';

        if (is_file($layout)) {
            include $layout;
        } else {
            include $path;
        }
        exit;
    }
}
