<?php
declare(strict_types=1);

namespace Src\Controller;

use Src\Core\Request;
use Src\Core\Session;

final class HomeController extends BaseController
{
    public function login(Request $req): void
    {
        $this->view('auth/login.php');
    }

    public function register(Request $req): void
    {
        $this->view('auth/register.php');
    }

    public function profesorActividades(Request $req): void
    {
        Session::start();
        if (Session::roleId() !== 2) {
            header('Location: /Bancalia/public/login');
            exit;
        }
        $this->view('profesor/actividades.php');
    }

    public function alumnoActividades(Request $req): void
    {
        Session::start();
        if (Session::roleId() !== 3) {
            header('Location: /Bancalia/public/login');
            exit;
        }
        $this->view('alumno/actividades.php');
    }

    public function logout(Request $req): void
    {
        Session::logout();
        header('Location: /Bancalia/public/login');
        exit;
    }
}
