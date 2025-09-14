<?php
declare(strict_types=1);

namespace Src\Controller;

use Src\Core\Session;
use Src\Config\DB;
use Throwable;

final class ProfesorPerfilController extends BaseController
{
    /** Vista del perfil */
    public function page(): void
    {
        if (!Session::check() || Session::roleId() !== 2) {
            $this->redirect('/Bancalia/public/login'); return;
        }

        // Datos mínimos del usuario
        $user = null;
        $uid = Session::userId();
        if ($uid) {
            try {
                $pdo = DB::pdo();
                $st  = $pdo->prepare('SELECT id,nombre,email,rol_id FROM usuarios WHERE id=? LIMIT 1');
                $st->execute([$uid]);
                $user = $st->fetch() ?: null;
            } catch (Throwable $e) {
                $user = ['id'=>$uid,'nombre'=>'Profesor','email'=>'','rol_id'=>2];
            }
        }

        $this->view('profesor/perfil', ['title'=>'Mi perfil','user'=>$user]);
    }

    /** GET /api/profesor/perfil : datos básicos y materias impartidas */
    public function apiGet(): void
    {
        if (!Session::check() || Session::roleId() !== 2) {
            $this->json(['error'=>'No autorizado'], 401);
        }
        $uid = (int)Session::userId();
        $pdo = DB::pdo();

        $st = $pdo->prepare('SELECT id,nombre,email FROM usuarios WHERE id=? LIMIT 1');
        $st->execute([$uid]);
        $user = $st->fetch() ?: null;

        $sql = "SELECT
                  pi.id,
                  g.nombre   AS grado,
                  c.nombre   AS curso,
                  a.nombre   AS asignatura,
                  pi.grado_id, pi.curso_id, pi.asignatura_id
                FROM profesor_imparte pi
                JOIN grados g      ON g.id = pi.grado_id
                JOIN cursos c      ON c.id = pi.curso_id
                JOIN asignaturas a ON a.id = pi.asignatura_id
                WHERE pi.profesor_id = ?
                ORDER BY g.nombre, c.orden, c.nombre, a.nombre";
        $st2 = $pdo->prepare($sql);
        $st2->execute([$uid]);
        $imparte = $st2->fetchAll();

        $this->json(['user'=>$user, 'imparte'=>$imparte]);
    }

    /** PUT /api/profesor/perfil : actualizar nombre/email/password */
    public function apiUpdate(): void
    {
        if (!Session::check() || Session::roleId() !== 2) {
            $this->json(['error'=>'No autorizado'], 401);
        }
        $uid = (int)Session::userId();
        $pdo = DB::pdo();

        $d = json_decode(file_get_contents('php://input'), true) ?: [];
        $nombre   = trim((string)($d['nombre'] ?? ''));
        $email    = trim((string)($d['email'] ?? ''));
        $password = (string)($d['password'] ?? '');

        if ($nombre === '' || $email === '') {
            $this->json(['error'=>'Nombre y email son obligatorios'], 422);
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->json(['error'=>'Email no válido'], 422);
        }

        // Email único (para otro usuario)
        $st = $pdo->prepare('SELECT id FROM usuarios WHERE email=? AND id<>?');
        $st->execute([$email, $uid]);
        if ($st->fetch()) $this->json(['error'=>'Ese email ya está en uso'], 409);

        $pdo->beginTransaction();
        try {
            if ($password !== '') {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $up = $pdo->prepare('UPDATE usuarios SET nombre=?, email=?, password_hash=? WHERE id=?');
                $up->execute([$nombre, $email, $hash, $uid]);
            } else {
                $up = $pdo->prepare('UPDATE usuarios SET nombre=?, email=? WHERE id=?');
                $up->execute([$nombre, $email, $uid]);
            }
            $pdo->commit();
            $this->json(['ok'=>true]);
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $this->json(['error'=>$e->getMessage()], 500);
        }
    }

    /** POST /api/profesor/imparte : añadir (grado,curso,asignatura) */
    public function apiImparteAdd(): void
    {
        if (!Session::check() || Session::roleId() !== 2) {
            $this->json(['error'=>'No autorizado'], 401);
        }
        $uid = (int)Session::userId();
        $pdo = DB::pdo();
        $d = json_decode(file_get_contents('php://input'), true) ?: [];

        $gradoId = (int)($d['grado_id'] ?? 0);
        $cursoId = (int)($d['curso_id'] ?? 0);
        $asigId  = (int)($d['asignatura_id'] ?? 0);

        if ($gradoId<=0 || $cursoId<=0 || $asigId<=0) {
            $this->json(['error'=>'Selecciona grado, curso y asignatura'], 422);
        }

        // Coherencia grado
        $st = $pdo->prepare('SELECT grado_id FROM cursos WHERE id=?');
        $st->execute([$cursoId]); $gCurso = $st->fetchColumn();
        $st = $pdo->prepare('SELECT grado_id FROM asignaturas WHERE id=?');
        $st->execute([$asigId]);  $gAsig  = $st->fetchColumn();
        if (!$gCurso || !$gAsig || (int)$gCurso !== $gradoId || (int)$gAsig !== $gradoId) {
            $this->json(['error'=>'grado_id no coincide con el curso/asignatura'], 422);
        }

        // Insert (ignorar duplicado)
        try {
            $ins = $pdo->prepare('INSERT INTO profesor_imparte (profesor_id,grado_id,curso_id,asignatura_id) VALUES (?,?,?,?)');
            $ins->execute([$uid,$gradoId,$cursoId,$asigId]);
            $this->json(['ok'=>true,'id'=>(int)$pdo->lastInsertId()], 201);
        } catch (Throwable $e) {
            // Duplicado u otro error
            $this->json(['error'=>'Ya existe o no se pudo guardar'], 400);
        }
    }

    /** DELETE /api/profesor/imparte/{id} : eliminar fila si es suya */
    public function apiImparteDelete(string $id): void
    {
        if (!Session::check() || Session::roleId() !== 2) {
            $this->json(['error'=>'No autorizado'], 401);
        }
        $uid = (int)Session::userId();
        $pdo = DB::pdo();

        $st = $pdo->prepare('DELETE FROM profesor_imparte WHERE id=? AND profesor_id=?');
        $st->execute([(int)$id, $uid]);
        $this->json(['ok'=> ($st->rowCount() > 0)]);
    }
}

