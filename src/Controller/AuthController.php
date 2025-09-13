<?php
namespace Src\Controller;

use Src\Core\Request;
use Src\Config\DB;
use Throwable;

final class AuthController extends BaseController {
  public function login(Request $req){
    try {
      $data = $req->json();
      $email = trim($data['email'] ?? '');
      $password = (string)($data['password'] ?? '');
      if ($email === '' || $password === '') return $this->json(['error'=>'Email y password son obligatorios'], 422);

      $pdo = DB::pdo();
      $st = $pdo->prepare('SELECT id,nombre,email,password_hash,rol_id FROM usuarios WHERE email = ? LIMIT 1');
      $st->execute([$email]);
      $user = $st->fetch();
      if (!$user) return $this->json(['error'=>'Credenciales inválidas'], 401);

      $hash = $user['password_hash'] ?? '';
      $ok = $hash && str_starts_with($hash, '$2y$') ? password_verify($password, $hash) : hash_equals($hash, $password);
      if (!$ok) return $this->json(['error'=>'Credenciales inválidas'], 401);

      if (session_status() !== PHP_SESSION_ACTIVE) session_start();
      $_SESSION['uid'] = (int)$user['id'];
      $_SESSION['rol_id'] = (int)$user['rol_id'];

      unset($user['password_hash']);
      return $this->json(['ok'=>true,'user'=>$user]);
    } catch (Throwable $e) {
      return $this->json(['error'=>$e->getMessage()], 500);
    }
  }

  public function register(Request $req){
    $pdo = DB::pdo();
    try {
      $data = $req->json();
      $nombre   = trim($data['nombre']   ?? '');
      $email    = trim($data['email']    ?? '');
      $password = (string)($data['password'] ?? '');
      $rolTxt   = strtolower(trim($data['rol'] ?? 'alumno')); // 'profesor' | 'alumno'

      if ($nombre === '' || $email === '' || $password === '') return $this->json(['error'=>'Nombre, email y password son obligatorios'], 422);
      if (!filter_var($email, FILTER_VALIDATE_EMAIL))          return $this->json(['error'=>'Email no válido'], 422);
      if (!in_array($rolTxt, ['profesor','alumno'], true))     return $this->json(['error'=>'Rol no válido'], 422);

      // Mapear rol -> id existente
      $st = $pdo->prepare('SELECT id FROM roles WHERE nombre=? LIMIT 1');
      $st->execute([$rolTxt]);
      $rolRow = $st->fetch();
      if (!$rolRow) return $this->json(['error'=>'Rol no configurado en la BD'], 500);
      $rolId = (int)$rolRow['id'];

      // Validar IDs dependientes
      $cursoId = (int)($data['curso_id'] ?? 0);
      $asigId  = (int)($data['asignatura_id'] ?? 0);

      if ($rolTxt === 'alumno') {
        if ($cursoId <= 0) return $this->json(['error'=>'Selecciona curso'], 422);
        $st = $pdo->prepare('SELECT id, grado_id FROM cursos WHERE id=?');
        $st->execute([$cursoId]); $curso = $st->fetch();
        if (!$curso) return $this->json(['error'=>'Curso no válido'], 422);
      } else {
        if ($cursoId <= 0 || $asigId <= 0) return $this->json(['error'=>'Selecciona curso y asignatura'], 422);
        $st = $pdo->prepare('SELECT id, grado_id FROM cursos WHERE id=?');
        $st->execute([$cursoId]); $curso = $st->fetch();
        if (!$curso) return $this->json(['error'=>'Curso no válido'], 422);
        $st = $pdo->prepare('SELECT id, grado_id FROM asignaturas WHERE id=?');
        $st->execute([$asigId]); $asig = $st->fetch();
        if (!$asig) return $this->json(['error'=>'Asignatura no válida'], 422);
        if ((int)$curso['grado_id'] !== (int)$asig['grado_id']) return $this->json(['error'=>'Curso y asignatura de grados distintos'], 422);
      }

      // Email único
      $st = $pdo->prepare('SELECT id FROM usuarios WHERE email=?');
      $st->execute([$email]);
      if ($st->fetch()) return $this->json(['error'=>'El email ya está registrado'], 409);

      // Transacción: usuario + perfil
      $pdo->beginTransaction();

      $hash = password_hash($password, PASSWORD_BCRYPT);
      $insU = $pdo->prepare('INSERT INTO usuarios (nombre,email,password_hash,rol_id,estado) VALUES (?,?,?,?,?)');
      $insU->execute([$nombre,$email,$hash,$rolId,'activo']);
      $uid = (int)$pdo->lastInsertId();

      if ($rolTxt === 'alumno') {
        // la tabla debe existir ya en tu esquema
        $insP = $pdo->prepare('INSERT INTO perfiles_alumno (usuario_id, curso_id) VALUES (?, ?)');
        $insP->execute([$uid, $cursoId]);
      } else {
        $insP = $pdo->prepare('INSERT INTO perfiles_profesor (usuario_id, curso_id, asignatura_id) VALUES (?, ?, ?)');
        $insP->execute([$uid, $cursoId, $asigId]);
      }

      $pdo->commit();
      return $this->json(['ok'=>true, 'user'=>['id'=>$uid,'nombre'=>$nombre,'email'=>$email,'rol_id'=>$rolId]], 201);

    } catch (Throwable $e) {
      if ($pdo->inTransaction()) $pdo->rollBack();
      return $this->json(['error'=>$e->getMessage()], 500);
    }
  }

  public function logout(Request $req){
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    session_unset(); session_destroy();
    return $this->json(['ok'=>true]);
  }
}
