<?php
declare(strict_types=1);

namespace Src\Controller;

use Src\Core\Request;
use Src\Core\Session;

final class AdminController extends BaseController
{
    private function guard(): void
    {
        Session::start();
        if (Session::roleId() !== 1) { header('Location: /Bancalia/public/login'); exit; }
    }

    public function dashboard(Request $req): void
    {
        $this->guard();
        $this->view('admin/dashboard.php');
    }

    public function gestor(Request $req, string $entity): void
    {
        $this->guard();
        $this->view('admin/gestor.php', ['entity'=>$entity]);
    }
}
