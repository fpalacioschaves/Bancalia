<?php
// /admin/actividades/items/delete.php
declare(strict_types=1);
require_once __DIR__ . '/../../../middleware/require_auth.php';
require_once __DIR__ . '/../../../lib/auth.php';

$id = (int)($_GET['id'] ?? 0);
$actividad_id = (int)($_GET['actividad_id'] ?? 0);

if ($id>0){
  $st = pdo()->prepare('SELECT a.profesor_id, i.actividad_id FROM actividad_items i JOIN actividades a ON a.id=i.actividad_id WHERE i.id=:id');
  $st->execute([':id'=>$id]);
  $row = $st->fetch();
  if ($row){
    $u = current_user();
    if (($u['role'] ?? '')==='admin' || (int)$row['profesor_id']===(int)($u['profesor_id'] ?? 0)) {
      $del = pdo()->prepare('DELETE FROM actividad_items WHERE id=:id');
      $del->execute([':id'=>$id]);
      $_SESSION['flash']='Ítem eliminado.';
    } else {
      $_SESSION['flash']='Sin permiso.';
    }
  }
}
header('Location: '.PUBLIC_URL.'/admin/actividades/edit.php?id='.$actividad_id);
exit;
