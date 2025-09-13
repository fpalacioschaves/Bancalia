<?php
namespace Src\Controller;


use Src\Core\Request; use Src\Core\Response; use Src\Config\DB; use Throwable; use PDO;


final class CatalogoController extends BaseController {
public function index(Request $req){
try {
$pdo = DB::pdo();
$data = [];
$data['grados'] = $pdo->query('SELECT id,nombre FROM grados ORDER BY nombre')->fetchAll();
$data['asignaturas'] = $pdo->query('SELECT id,grado_id,nombre FROM asignaturas ORDER BY nombre')->fetchAll();
$data['temas'] = $pdo->query('SELECT id,asignatura_id,nombre,orden FROM temas ORDER BY asignatura_id, orden')->fetchAll();
$data['ra'] = $pdo->query('SELECT id,asignatura_id,codigo,descripcion FROM ra ORDER BY asignatura_id, codigo')->fetchAll();
$this->json($data);
} catch (Throwable $e) { $this->json(['error'=>$e->getMessage()], 500); }
}


public function grados(Request $req){
try { $rows = DB::pdo()->query('SELECT id,nombre FROM grados ORDER BY nombre')->fetchAll(); $this->json($rows); }
catch (Throwable $e){ $this->json(['error'=>$e->getMessage()],500); }
}


public function cursos(Request $req){
try {
$q = $req->query(); $grado = isset($q['grado_id']) ? (int)$q['grado_id'] : 0;
$pdo = DB::pdo();
if ($grado > 0) {
$st = $pdo->prepare('SELECT id,grado_id,nombre,orden FROM cursos WHERE grado_id=? ORDER BY orden,nombre');
$st->execute([$grado]);
$this->json($st->fetchAll());
} else {
$this->json($pdo->query('SELECT id,grado_id,nombre,orden FROM cursos ORDER BY grado_id,orden,nombre')->fetchAll());
}
} catch (Throwable $e){ $this->json(['error'=>$e->getMessage()],500); }
}


public function asignaturas(Request $req){
try {
$q = $req->query(); $grado = isset($q['grado_id']) ? (int)$q['grado_id'] : 0;
$pdo = DB::pdo();
if ($grado > 0) {
$st = $pdo->prepare('SELECT id,grado_id,nombre FROM asignaturas WHERE grado_id=? ORDER BY nombre');
$st->execute([$grado]);
$this->json($st->fetchAll());
} else {
$this->json($pdo->query('SELECT id,grado_id,nombre FROM asignaturas ORDER BY grado_id,nombre')->fetchAll());
}
} catch (Throwable $e){ $this->json(['error'=>$e->getMessage()],500); }
}
}