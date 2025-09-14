<?php
declare(strict_types=1);

namespace Src\Controller;

abstract class BaseController
{
    /** JSON simple */
    protected function json(mixed $data, int $status = 200): void
    {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code($status);
        }
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /** Redirect */
    protected function redirect(string $url, int $status = 302): void
    {
        if (!headers_sent()) header('Location: '.$url, true, $status);
        exit;
    }

    /**
     * Renderiza una vista dentro del layout.
     * Acepta 'carpeta/vista' o 'carpeta/vista.php'
     * y resuelve a /public/views/carpeta/vista.php
     */
    protected function view(string $view, array $data = []): void
    {
        $base = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'views';

        // Normaliza separadores y evita doble ".php"
        $rel = str_replace(['\\','/'], DIRECTORY_SEPARATOR, ltrim($view, '/'));
        if (substr($rel, -4) !== '.php') {
            $rel .= '.php';
        }

        $file = $base . DIRECTORY_SEPARATOR . $rel;

        if (!is_file($file)) {
            if (!headers_sent()) http_response_code(500);
            echo 'Vista no encontrada: ' . htmlspecialchars($view) . ' (buscada: ' . $file . ')';
            exit;
        }

        // variables disponibles en la vista y el layout
        extract($data, EXTR_SKIP);

        // el layout original hace: include $path;
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
