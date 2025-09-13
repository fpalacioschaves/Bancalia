<?php
namespace Src\Controller;


use Src\Util\Response;


class EntregaController {
public function create(){ Response::json(['ok'=>true,'msg'=>'entrega stub']); }
public function byAlumno($id){ Response::json(['ok'=>true,'alumno_id'=>$id,'items'=>[]]); }
}