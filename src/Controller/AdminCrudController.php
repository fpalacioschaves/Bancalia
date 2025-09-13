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
    /** Config por entidad (para meta/formularios genéricos) */
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
                    'creador_id'=>['w'=>true,'r'=>false, 'transform'=>fn($v)=> $v ?: ($uid ?: null)],
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
                    // El password se gestiona aparte en store/update
                    'password_hash'=>['w'=>true,'r'=>false],
                ],
                'search'=>['nombre','email']
            ],
            'actividades' => [
                'table'=>'actividades','pk'=>'id','order'=>'id DESC',
                'fields'=>[
                    'titulo'            =>['w'=>true,'r'=>true],
                    'enunciado'         =>['w'=>true,'r'=>true],
                    'autor_id'          =>['w'=>true,'r'=>false, 'transform'=>fn($v)=> $v ?: ($uid ?: null)],
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
            $q   = $req->query();
            $pdo = DB::pdo();

            if ($entity === 'asignaturas') {
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
                // Listado sin password_hash, rol por nombre, fechas sin hora
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
                            DATE(u.creado_en)      AS creado,
                            DATE(u.actualizado_en) AS actualizado
                        FROM usuarios u
                        JOIN roles r ON r.id = u.rol_id";
                if ($where) $sql .= ' WHERE '.implode(' AND ', $where);
                $sql .= " ORDER BY u.id DESC
                          LIMIT {$limit} OFFSET {$offset}";
                $st = $pdo->prepare($sql); $st->execute($args);
                return $this->json($st->fetchAll());
            }

            if ($entity === 'actividades') {
                // ===== Listado Admin de Actividades: titulo, tipo, autor, asignatura, tema =====
                $limit  = max(1, min((int)($q['limit'] ?? 100), 500));
                $offset = max(0, (int)($q['offset'] ?? 0));

                $where = []; $args = [];

                if (!empty($q['q'])) {
                    $term = '%'.$q['q'].'%';
                    $where[] = '(a.titulo LIKE ? OR at.nombre LIKE ? OR u.nombre LIKE ? OR asig.nombre LIKE ? OR t.nombre LIKE ?)';
                    $args = [$term,$term,$term,$term,$term];
                }
                if (!empty($q['tipo_id']))       { $where[]='a.tipo_id = ?';        $args[]=(int)$q['tipo_id']; }
                if (!empty($q['autor_id']))      { $where[]='a.autor_id = ?';       $args[]=(int)$q['autor_id']; }
                if (!empty($q['asignatura_id'])) { $where[]='t.asignatura_id = ?';  $args[]=(int)$q['asignatura_id']; }
                if (!empty($q['tema_id']))       { $where[]='t.id = ?';             $args[]=(int)$q['tema_id']; }

                $sql = "SELECT
                            a.id,
                            a.titulo,
                            at.nombre AS tipo,
                            u.nombre  AS autor,
                            GROUP_CONCAT(DISTINCT asig.nombre ORDER BY asig.nombre SEPARATOR ', ') AS asignatura,
                            GROUP_CONCAT(DISTINCT t.nombre    ORDER BY COALESCE(t.orden,9999), t.nombre SEPARATOR ', ') AS tema
                        FROM actividades a
                        JOIN actividad_tipos at ON at.id = a.tipo_id
                        JOIN usuarios u        ON u.id  = a.autor_id
                        LEFT JOIN actividad_tema atema  ON atema.actividad_id = a.id
                        LEFT JOIN temas t               ON t.id = atema.tema_id
                        LEFT JOIN asignaturas asig      ON asig.id = t.asignatura_id";
                if ($where) $sql .= ' WHERE '.implode(' AND ', $where);
                $sql .= " GROUP BY a.id, a.titulo, at.nombre, u.nombre
                          ORDER BY a.id DESC
                          LIMIT {$limit} OFFSET {$offset}";

                $st = $pdo->prepare($sql);
                $st->execute($args);
                return $this->json($st->fetchAll());
            }

            // genérico (fallback)
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

            if ($entity === 'usuarios' && isset($row['password_hash'])) {
                unset($row['password_hash']); // no exponer
            }

            $this->json($row);
        } catch (Throwable $e) {
            $this->json(['error'=>$e->getMessage()], 500);
        }
    }

    /** Crear */
    public function store(Request $req, string $entity)
    {
        Session::requireApiRole([1]);
        $pdo = DB::pdo();
        try {
            $cfg  = $this->config($entity);
            $data = $req->json();

            if ($entity === 'usuarios') {
                $plain = trim((string)($data['password'] ?? ''));
                if ($plain === '') {
                    return $this->json(['error'=>'El password es obligatorio al crear un usuario'], 422);
                }
                $data['password_hash'] = password_hash($plain, PASSWORD_BCRYPT);
                unset($data['password']);
            }

            $pdo->beginTransaction();
            $id = Crud::create($cfg, $data);
            $pdo->commit();
            $this->json(['ok'=>true,'id'=>$id], 201);
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $this->json(['error'=>$e->getMessage()], 500);
        }
    }

    /** Actualizar */
    public function update(Request $req, string $entity, string $id)
    {
        Session::requireApiRole([1]);
        $pdo = DB::pdo();
        try {
            $cfg  = $this->config($entity);
            $data = $req->json();

            if ($entity === 'usuarios') {
                if (array_key_exists('password', $data)) {
                    $plain = trim((string)$data['password']);
                    if ($plain !== '') {
                        $data['password_hash'] = password_hash($plain, PASSWORD_BCRYPT);
                    }
                    unset($data['password']);
                }
            }

            $pdo->beginTransaction();
            Crud::update($cfg, (int)$id, $data);
            $pdo->commit();
            $this->json(['ok'=>true]);
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
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
