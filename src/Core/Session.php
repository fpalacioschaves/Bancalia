<?php
declare(strict_types=1);

namespace Src\Core;

final class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) return;
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'secure'   => false,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }

    public static function login(int $uid, int $rolId): void
    {
        self::start();
        $_SESSION['uid']    = $uid;
        $_SESSION['rol_id'] = $rolId;
    }

    public static function logout(): void
    {
        self::start();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'] ?? '', $p['secure'], $p['httponly']);
        }
        session_destroy();
    }

    public static function check(): bool
    {
        self::start();
        return !empty($_SESSION['uid']);
    }

    public static function userId(): ?int
    {
        self::start();
        return isset($_SESSION['uid']) ? (int)$_SESSION['uid'] : null;
    }

    public static function roleId(): ?int
    {
        self::start();
        return isset($_SESSION['rol_id']) ? (int)$_SESSION['rol_id'] : null;
    }

    /** Para proteger endpoints de API por rol */
    public static function requireApiRole(array $roles): void
    {
        self::start();
        $r = (int)($_SESSION['rol_id'] ?? 0);
        if (!$r || !in_array($r, $roles, true)) {
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'No autorizado']);
            exit;
        }
    }
}
