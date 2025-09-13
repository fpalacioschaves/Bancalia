<?php
namespace Src\Controller;


use Src\Core\Request; use Src\Config\DB; use PDO; use Throwable;


final class ActividadController extends BaseController {
public function list(Request $req){
try {
$pdo = DB::pdo();
$sql = "SELECT a.id, a.titulo, a.estado, a.visibilidad, a.creado_en, t.clave AS tipo
FROM actividades a LEFT JOIN actividad_tipos t ON t.id=a.tipo_id
ORDER BY a.creado_en DESC LIMIT 100";
$rows = $pdo->query($sql)->fetchAll();
$this->json($rows);
} catch (Throwable $e) {
$this->json(['error'=>$e->getMessage()], 500);
}
}
}