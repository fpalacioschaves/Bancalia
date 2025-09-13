<?php
declare(strict_types=1);

namespace Src\Controller;

use Src\Core\Request;
use Src\Core\Session;
use Src\Core\Crud;
use Src\Config\DB;
use Throwable;

final class AdminCrudController extends BaseController
{
    /** Config por entidad (para formularios / meta) */
    private function config(string $entity): array
    {
        $uid = Session::userId() ?? 0;

        return match ($entity) {
            'roles' => [
                'table'=>'roles','pk'=>'id','order'=>'nombre ASC',
                'fields'=>[
                    'nombre'=>['w'=>true,'r'=>true],
                ],
                'search'=>['nombre']
            ],
            'grados' => [
                'table'=>'grados','pk'=>'id','order'=>'nombre ASC',
                'fields'=>[
                    'nombre'=>['w'=>true,'r'=>true],
                ],
                'search'=>['nombre']
            ],
            'asignaturas' => [
                'table'=>'asignaturas','pk'=>'id','order'=>'nombre ASC',
                'fields'=>[
                    'grado_id'=>['w'=>true,'r'=>true],
                    'nombre'  =>['w'=>true,'r'=>true],
                    'codigo'  =>['w'=>true,'r'=>false],
                ],
                'search'=>['nombre','codigo']
            ],
            'temas' => [
                'table'=>'temas','pk'=>'id',
                'order'=>'asignatura_id ASC, COALESCE(orden,9999) ASC, nombre ASC',
                'fields'=>[
                    'asignatura_id'=>['w'=>true,'r'=>true],
                    'nombre'       =>['w'=>true,'r'=>true],
                    'codigo'       =>['w'=>true,'r'=>false],
                    'orden'        =>['w'=>true,'r'=>false],
                ],
                'search'=>['nombre','codigo']
            ],
            'etiquetas' => [
                'table'=>'etiquetas','pk'=>'id','order'=>'nombre ASC',
                'fields'=>[
                    'nombre'    =>['w'=>true,'r'=>true],
                    'color'     =>['w'=>true,'r'=>false],
                    'creador_id'=>['w'=>true,'r'=>false, 'transform'=>fn($v)=> $v ?: $uid],
                ],
                'search'=>['nombre']
            ],
            'usuarios' => [
                'table'=>'usuarios','pk'=>'id','order'=>'id DESC',
                'fields'=>[
                    'nombre'       =>['w'=>true,'r'=>true],
                    'email'        =>['w'=>true,'r'=>true],
                    'rol_id'       =>['w'=>true,'r'=>true],
                    'estado'       =>['w'=>true,'r'=>false],
                    // password virtual: si llega "password", se guarda en password_hash (bcrypt)
                    'password_hash'=>['w'=>true,'r'=>false, 'transform'=>function($v,$all){
                        if (isset($all['password']) && $all['password'] !== '') {
                            return password_hash((string)$all['password'], PASSWORD_BCRYPT);
                        }
                        return null; // no tocar si no llega password
                    }],
                ],
                'search'=>['nombre','email']
            ],
            'actividades' => [
                'table'=>'actividades','pk'=>'id','order'=>'id DESC',
                'fields'=>[
                    'titulo'            =>['w'=>true,'r'=>true],
                    'enunciado'         =>['w'=>true,'r'=>true],
                    'autor_id'          =>['w'=>true,'r'=>false, 'transform'=>fn($v)=> $v ?: $uid],
                    'tipo_id'           =>['w'=>true,'r'=>true],
                    'visibilidad'       =>['w'=>true,'r'=>false],
                    'estado'            =>['w'=>true,'r'=>false],
                    'dificultad'        =>['w'=>true,'r'=>false],
                    'tiempo_estimado_min'=>['w'=>true,'r'=>false],
                    'publico_slug'      =>['w'=>true,'r'=>false],
                ],
                'search'=>['titulo']
            ],
            default => throw new \InvalidArgumentException('Entidad no soportada: '.$entity),
        };
    }

    /** Metadatos para el front (formularios) */
    public function meta(Request $req, string $entity)
    {
        Session::requireApiRole([1]);
        try {
            $cfg = $this->config($entity);
            $fields = [];
            foreach ($cfg['fields'] as $k=>$o){
                $fields[] = ['name'=>$k, 'required'=> (bool)($o['r'] ?? false), 'writable'=>(bool)($o['w'] ?? true)];
            }
            $this->json(['entity'=>$entity,'pk'=>$cfg['pk'],'fields'=>$fields,'search'=>$cfg['search'] ?? []]);
        } catch (Throwable $e) {
            $this->json(['error'=>$e->getMessage()], 500);
        }
    }

    /** Listados (con joins/formatos donde aplica) */
    public function index(Request $req, string $entity)
    {
        Session::requireApiRole([1]);
        try {
            $q = $req->query();

            if ($entity === 'asignaturas') {
                $pdo = DB::pdo();
                $limit  = max(1, min((int)($q['limit'] ?? 100), 500));
                $offset = max(0, (int)($q['offset'] ?? 0));
                $where = []; $args = [];
                if (!empty($q['q'])) {
                    $term = '%'.$q['q'].'%';
                    $where[] = '(a.nombre LIKE ? OR a.codigo LIKE ? OR g.nombre LIKE ?)';
                    $args = [$term,$term,$term];
                }
                if (!empty($q['grado_id'])) { $where[]='a.grado_id=?'; $args[]=(int)$q['grado_id']; }
                $sql = "SELECT a.id, g.nombre AS grado, a.nombre, a.codigo
                        FROM asignaturas a
                        JOIN grados g ON g.id=a.grado_id";
                if ($where) $sql .= ' WHERE '.implode(' AND ', $where);
                $sql .= " ORDER BY g.nombre ASC, a.nombre ASC
                          LIMIT {$limit} OFFSET {$offset}";
                $st = $pdo->prepare($sql); $st->execute($args);
                return $this->json($st->fetchAll());
            }

            if ($entity === 'temas') {
                $pdo = DB::pdo();
                $limit  = max(1, min((int)($q['limit'] ?? 100), 500));
                $offset = max(0, (int)($q['offset'] ?? 0));
                $where = []; $args = [];
                if (!empty($q['q'])) {
                    $term = '%'.$q['q'].'%';
                    $where[] = '(t.nombre LIKE ? OR t.codigo LIKE ? OR a.nombre LIKE ?)';
                    $args = [$term,$term,$term];
                }
                if (!empty($q['asignatura_id'])) { $where[]='t.asignatura_id=?'; $args[]=(int)$q['asignatura_id']; }
                if (!empty($q['grado_id']))      { $where[]='a.grado_id=?';      $args[]=(int)$q['grado_id']; }
                $sql = "SELECT t.id, a.nombre AS asignatura, t.nombre, t.codigo, t.orden
                        FROM temas t
                        JOIN asignaturas a ON a.id=t.asignatura_id";
                if ($where) $sql .= ' WHERE '.implode(' AND ', $where);
                $sql .= " ORDER BY a.nombre ASC, COALESCE(t.orden,9999) ASC, t.nombre ASC
                          LIMIT {$limit} OFFSET {$offset}";
                $st = $pdo->prepare($sql); $st->execute($args);
                return $this->json($st->fetchAll());
            }

            if ($entity === 'etiquetas') {
                $pdo = DB::pdo();
                $limit  = max(1, min((int)($q['limit'] ?? 100), 500));
                $offset = max(0, (int)($q['offset'] ?? 0));
                $where = []; $args = [];
                if (!empty($q['q'])) { $term = '%'.$q['q'].'%'; $where[] = '(e.nombre LIKE ? OR u.nombre LIKE ?)'; $args = [$term,$term]; }
                $sql = "SELECT e.id, e.nombre, e.color, COALESCE(u.nombre,'—') AS creador
                        FROM etiquetas e
                        LEFT JOIN usuarios u ON u.id = e.creador_id";
                if ($where) $sql .= ' WHERE '.implode(' AND ', $where);
                $sql .= " ORDER BY e.nombre ASC
                          LIMIT {$limit} OFFSET {$offset}";
                $st = $pdo->prepare($sql); $st->execute($args);
                return $this->json($st->fetchAll());
            }

            if ($entity === 'usuarios') {
                // Listado sin password_hash, con rol (nombre) y fechas sin hora
                $pdo = DB::pdo();
                $limit  = max(1, min((int)($q['limit'] ?? 100), 500));
                $offset = max(0, (int)($q['offset'] ?? 0));
                $where = []; $args = [];
                if (!empty($q['q'])) {
                    $term = '%'.$q['q'].'%';
                    $where[] = '(u.nombre LIKE ? OR u.email LIKE ? OR r.nombre LIKE ?)';
                    $args = [$term,$term,$term];
                }
                $sql = "SELECT
                            u.id,
                            u.nombre,
                            u.email,
                            r.nombre AS rol,
                            u.estado,
                            DATE(u.creado_en)     AS creado,
                            DATE(u.actualizado_en) AS actualizado
                        FROM usuarios u
                        JOIN roles r ON r.id = u.rol_id";
                if ($where) $sql .= ' WHERE '.implode(' AND ', $where);
                $sql .= " ORDER BY u.id DESC
                          LIMIT {$limit} OFFSET {$offset}";
                $st = $pdo->prepare($sql); $st->execute($args);
                return $this->json($st->fetchAll());
            }

            // genérico
            $this->json(Crud::index($this->config($entity), $q));
        } catch (Throwable $e) {
            $this->json(['error'=>$e->getMessage()], 500);
        }
    }

    /** Ver detalle (para edición) */
    public function show(Request $req, string $entity, string $id)
    {
        Session::requireApiRole([1]);
        try {
            $row = Crud::show($this->config($entity), (int)$id);
            if (!$row) return $this->json(['error'=>'No encontrado'], 404);
            $this->json($row);
        } catch (Throwable $e) {
            $this->json(['error'=>$e->getMessage()], 500);
        }
    }

    /** Crear */
    public function store(Request $req, string $entity)
    {
        Session::requireApiRole([1]);
        try {
            $cfg = $this->config($entity);
            $pdo = DB::pdo();
            $pdo->beginTransaction();
            $id = Crud::create($cfg, $req->json());
            $pdo->commit();
            $this->json(['ok'=>true,'id'=>$id], 201);
        } catch (Throwable $e) {
            if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
            $this->json(['error'=>$e->getMessage()], 500);
        }
    }

    /** Actualizar */
    public function update(Request $req, string $entity, string $id)
    {
        Session::requireApiRole([1]);
        try {
            $cfg = $this->config($entity);
            $pdo = DB::pdo();
            $pdo->beginTransaction();
            Crud::update($cfg, (int)$id, $req->json());
            $pdo->commit();
            $this->json(['ok'=>true]);
        } catch (Throwable $e) {
            if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
            $this->json(['error'=>$e->getMessage()], 500);
        }
    }

    /** Borrar */
    public function destroy(Request $req, string $entity, string $id)
    {
        Session::requireApiRole([1]);
        try {
            $cfg = $this->config($entity);
            Crud::destroy($cfg, (int)$id);
            $this->json(['ok'=>true]);
        } catch (Throwable $e) {
            $this->json(['error'=>$e->getMessage()], 500);
        }
    }
}
