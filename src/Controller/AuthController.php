<?php
declare(strict_types=1);

namespace Src\Controller;

use Src\Core\Request;
use Src\Core\Session;
use Src\Config\DB;
use Throwable;

final class AuthController extends BaseController
{
    public function login(Request $req)
    {
        try {
            $data = $req->json();
            $email = trim($data['email'] ?? '');
            $password = (string)($data['password'] ?? '');

            if ($email === '' || $password === '') {
                return $this->json(['error' => 'Email y password son obligatorios'], 422);
            }

            $pdo = DB::pdo();
            $st = $pdo->prepare('SELECT id,nombre,email,password_hash,rol_id FROM usuarios WHERE email = ? LIMIT 1');
            $st->execute([$email]);
            $user = $st->fetch();

            if (!$user) return $this->json(['error' => 'Credenciales inválidas'], 401);

            $hash = $user['password_hash'] ?? '';
            $ok = $hash && str_starts_with($hash, '$2y$')
                ? password_verify($password, $hash)
                : hash_equals($hash, $password);

            if (!$ok) return $this->json(['error' => 'Credenciales inválidas'], 401);

            Session::login((int)$user['id'], (int)$user['rol_id']);
            unset($user['password_hash']);

            $redirect = '/Bancalia/public/';
            if ((int)$user['rol_id'] === 1)      $redirect = '/Bancalia/public/admin';
            else if ((int)$user['rol_id'] === 2) $redirect = '/Bancalia/public/profesor';

            return $this->json(['ok' => true, 'user' => $user, 'redirect' => $redirect]);
        } catch (Throwable $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }

    public function register(Request $req)
    {
        $pdo = DB::pdo();
        try {
            $d = $req->json();
            $nombre   = trim($d['nombre']   ?? '');
            $email    = trim($d['email']    ?? '');
            $password = (string)($d['password'] ?? '');
            $rolTxt   = strtolower(trim($d['rol'] ?? 'alumno')); // 'profesor' | 'alumno'

            if ($nombre === '' || $email === '' || $password === '') return $this->json(['error'=>'Nombre, email y password son obligatorios'], 422);
            if (!filter_var($email, FILTER_VALIDATE_EMAIL))          return $this->json(['error'=>'Email no válido'], 422);
            if (!in_array($rolTxt, ['profesor','alumno'], true))     return $this->json(['error'=>'Rol no válido'], 422);

            // rol -> id
            $st = $pdo->prepare('SELECT id FROM roles WHERE nombre=? LIMIT 1');
            $st->execute([$rolTxt]); $rolRow = $st->fetch();
            if (!$rolRow) return $this->json(['error'=>'Rol no configurado en la BD'], 500);
            $rolId = (int)$rolRow['id'];

            // dependencias
            $cursoId = (int)($d['curso_id'] ?? 0);
            $asigId  = (int)($d['asignatura_id'] ?? 0);
            $imparte = is_array($d['imparte'] ?? null) ? $d['imparte'] : [];

            if ($rolTxt === 'alumno') {
                if ($cursoId <= 0) return $this->json(['error'=>'Selecciona curso'], 422);
                $st = $pdo->prepare('SELECT id FROM cursos WHERE id=?');
                $st->execute([$cursoId]); if (!$st->fetch()) return $this->json(['error'=>'Curso no válido'], 422);
            } else { // profesor
                // Si viene array imparte, lo usaremos; si no, exigimos el par único
                if (empty($imparte)) {
                    if ($cursoId <= 0 || $asigId <= 0) return $this->json(['error'=>'Selecciona curso y asignatura'], 422);
                    // validación de coherencia
                    $st = $pdo->prepare('SELECT id,grado_id FROM cursos WHERE id=?');       $st->execute([$cursoId]); $curso = $st->fetch();
                    $st = $pdo->prepare('SELECT id,grado_id FROM asignaturas WHERE id=?');  $st->execute([$asigId]);  $asig  = $st->fetch();
                    if (!$curso || !$asig) return $this->json(['error'=>'Curso/Asignatura no válidos'], 422);
                    if ((int)$curso['grado_id'] !== (int)$asig['grado_id']) return $this->json(['error'=>'Curso y asignatura de grados distintos'], 422);
                } else {
                    // Validamos rápidamente la estructura
                    foreach ($imparte as $i => $row) {
                        $gid = (int)($row['grado_id'] ?? 0);
                        $cid = (int)($row['curso_id'] ?? 0);
                        $aid = (int)($row['asignatura_id'] ?? 0);
                        if ($gid<=0 || $cid<=0 || $aid<=0) return $this->json(['error'=>"Fila imparte #".($i+1)." incompleta"], 422);

                        $st = $pdo->prepare('SELECT grado_id FROM cursos WHERE id=?');       $st->execute([$cid]); $gCurso = $st->fetchColumn();
                        $st = $pdo->prepare('SELECT grado_id FROM asignaturas WHERE id=?');  $st->execute([$aid]); $gAsig  = $st->fetchColumn();

                        if (!$gCurso || !$gAsig || (int)$gCurso!==$gid || (int)$gAsig!==$gid){
                            return $this->json(['error'=>"Fila imparte #".($i+1)." no coherente con el grado"], 422);
                        }
                    }
                }
            }

            // email único
            $st = $pdo->prepare('SELECT id FROM usuarios WHERE email=?');
            $st->execute([$email]); if ($st->fetch()) return $this->json(['error'=>'El email ya está registrado'], 409);

            // transacción
            $pdo->beginTransaction();

            $hash = password_hash($password, PASSWORD_BCRYPT);
            $ins  = $pdo->prepare('INSERT INTO usuarios (nombre,email,password_hash,rol_id,estado) VALUES (?,?,?,?,?)');
            $ins->execute([$nombre,$email,$hash,$rolId,'activo']);
            $uid = (int)$pdo->lastInsertId();

            if ($rolTxt === 'alumno') {
                // si existe perfiles_alumno en tu BD
                try {
                    $pdo->prepare('INSERT INTO perfiles_alumno (usuario_id, curso_id) VALUES (?,?)')->execute([$uid,$cursoId]);
                } catch (\Throwable $e) { /* ignora si no existe */ }
            } else {
                if (!empty($imparte)) {
                    $insPI = $pdo->prepare('INSERT IGNORE INTO profesor_imparte (profesor_id,grado_id,curso_id,asignatura_id) VALUES (?,?,?,?)');
                    foreach ($imparte as $row) {
                        $gid=(int)$row['grado_id']; $cid=(int)$row['curso_id']; $aid=(int)$row['asignatura_id'];
                        $insPI->execute([$uid,$gid,$cid,$aid]);
                    }
                } else {
                    // compat: un solo curso/asignatura -> deducimos grado
                    $st = $pdo->prepare('SELECT grado_id FROM cursos WHERE id=?'); $st->execute([$cursoId]); $gid = (int)$st->fetchColumn();
                    $pdo->prepare('INSERT IGNORE INTO profesor_imparte (profesor_id,grado_id,curso_id,asignatura_id) VALUES (?,?,?,?)')
                        ->execute([$uid,$gid,$cursoId,$asigId]);
                }
            }

            $pdo->commit();
            return $this->json(['ok'=>true,'user'=>['id'=>$uid,'nombre'=>$nombre,'email'=>$email,'rol_id'=>$rolId]], 201);

        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            return $this->json(['error'=>$e->getMessage()], 500);
        }
    }

    public function logout(Request $req)
    {
        Session::logout();
        return $this->json(['ok'=>true]);
    }

    public function me(Request $req)
    {
        Session::start();
        $uid = Session::userId();
        if (!$uid) return $this->json(['auth'=>false]);

        $pdo = DB::pdo();
        $st = $pdo->prepare('SELECT id,nombre,email,rol_id FROM usuarios WHERE id=?');
        $st->execute([$uid]);
        $user = $st->fetch();

        return $this->json(['auth'=>true,'user'=>$user]);
    }
}
