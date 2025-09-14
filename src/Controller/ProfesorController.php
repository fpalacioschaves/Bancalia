<?php
declare(strict_types=1);

namespace Src\Controller;

use Src\Core\Session;
use Src\Config\DB;

final class ProfesorController extends BaseController
{
    public function home(): void
    {
        if (!Session::check()) {
            $this->redirect('/Bancalia/public/login'); return;
        }
        $rolId = (int)Session::roleId();
        if ($rolId === 1) { $this->redirect('/Bancalia/public/admin'); return; }
        if ($rolId !== 2) { $this->redirect('/Bancalia/public/');     return; }

        // Cargamos datos mínimos del usuario (para el encabezado del panel)
        $user = null;
        $uid = Session::userId();
        if ($uid) {
            try {
                $pdo = DB::pdo();
                $st  = $pdo->prepare('SELECT id,nombre,email,rol_id FROM usuarios WHERE id=? LIMIT 1');
                $st->execute([$uid]);
                $user = $st->fetch() ?: null;
            } catch (\Throwable $e) {
                $user = ['id'=>$uid, 'nombre'=>'Profesor', 'rol_id'=>2];
            }
        }

        $this->view('profesor/home', [
            'title' => 'Panel del profesor',
            'user'  => $user
        ]);
    }
}

