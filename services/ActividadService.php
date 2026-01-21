<?php
declare(strict_types=1);

class ActividadService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public static function getTipos(): array
    {
        return [
            'opcion_multiple' => 'Opción múltiple',
            'verdadero_falso' => 'Verdadero / Falso',
            'respuesta_corta' => 'Respuesta corta',
            'rellenar_huecos' => 'Rellenar huecos',
            'emparejar' => 'Emparejar',
            'tarea' => 'Tarea / entrega larga',
        ];
    }

    public static function getDificultades(): array
    {
        return [
            'baja' => 'Baja',
            'media' => 'Media',
            'alta' => 'Alta',
        ];
    }

    public static function getVisibilidades(): array
    {
        return [
            'privada' => 'Privada',
            'centro' => 'Centro',
            'publica' => 'Pública',
        ];
    }

    public static function getEstados(): array
    {
        return [
            'borrador' => 'Borrador',
            'publicada' => 'Publicada',
        ];
    }

    /**
     * Busca una actividad por ID.
     */
    public function find(int $id): ?array
    {
        $st = $this->pdo->prepare('SELECT * FROM actividades WHERE id=:id LIMIT 1');
        $st->execute([':id' => $id]);
        return $st->fetch() ?: null;
    }

    /**
     * Busca una actividad por ID con todos sus datos específicos.
     */
    public function findFull(int $id): ?array
    {
        $act = $this->find($id);
        if (!$act)
            return null;

        $type = $act['tipo'];
        $table = '';
        switch ($type) {
            case 'tarea':
                $table = 'actividades_tarea';
                break;
            case 'verdadero_falso':
                $table = 'actividades_vf';
                break;
            case 'respuesta_corta':
                $table = 'actividades_rc';
                break;
            case 'rellenar_huecos':
                $table = 'actividades_rh';
                break;
            case 'opcion_multiple':
                $table = 'actividades_om';
                break;
            case 'emparejar':
                $table = 'actividades_emp_pares';
                break;
        }

        if ($table) {
            $st = $this->pdo->prepare("SELECT * FROM $table WHERE actividad_id=:aid LIMIT 1");
            $st->execute([':aid' => $id]);
            $specific = $st->fetch();
            if ($specific) {
                $act = array_merge($act, $specific);
            }
        }

        // Si es OM, también necesitamos las opciones (items)
        if ($type === 'opcion_multiple') {
            $act['om_options'] = $this->getItems($id);
        }

        return $act;
    }

    /**
     * Obtiene todas las actividades con filtros avanzados y visibilidad.
     */
    public function findAll(array $filters = [], array $user = []): array
    {
        $role = $user['role'] ?? '';
        $profesorId = (int) ($user['profesor_id'] ?? 0);
        $centroId = (int) ($user['centro_id'] ?? 0);

        $params = [];
        $sql = "SELECT a.id,
                       a.titulo,
                       a.tipo,
                       a.visibilidad,
                       a.estado,
                       a.dificultad,
                       a.updated_at,
                       a.profesor_id,
                       a.centro_id,
                       a.asignatura_id,
                       a.curso_id,
                       a.familia_id,
                       asig.nombre AS asignatura,
                       c.nombre    AS curso,
                       f.nombre    AS familia,
                       COALESCE(pop.popularidad, 0) AS popularidad
                FROM actividades a
                JOIN asignaturas asig ON asig.id = a.asignatura_id
                JOIN cursos c         ON c.id = a.curso_id
                JOIN familias_profesionales f ON f.id = a.familia_id
                LEFT JOIN (
                  SELECT ea.actividad_id,
                         COUNT(DISTINCT e.profesor_id) AS popularidad
                  FROM examenes_actividades ea
                  JOIN examenes e ON e.id = ea.examen_id
                  GROUP BY ea.actividad_id
                ) pop ON pop.actividad_id = a.id";

        $where = [];

        // Visibilidad según rol
        if ($role !== 'admin') {
            // Obtenemos asignaturas del profesor para filtrar públicas
            $st = $this->pdo->prepare('SELECT DISTINCT asignatura_id FROM profesor_asignacion WHERE profesor_id=?');
            $st->execute([$profesorId]);
            $misAsignaturas = array_map('intval', array_column($st->fetchAll(), 'asignatura_id'));

            if ($misAsignaturas) {
                $in = implode(',', array_fill(0, count($misAsignaturas), '?'));
                $where[] = "(
                  a.profesor_id = ?
                  OR (a.visibilidad = 'publica' AND a.asignatura_id IN ($in))
                  OR (a.visibilidad = 'centro' AND a.centro_id = ?)
                )";
                $params[] = $profesorId;
                foreach ($misAsignaturas as $ma)
                    $params[] = $ma;
                $params[] = $centroId;
            } else {
                $where[] = "(
                  a.profesor_id = ?
                  OR a.visibilidad = 'publica'
                  OR (a.visibilidad = 'centro' AND a.centro_id = ?)
                )";
                $params[] = $profesorId;
                $params[] = $centroId;
            }
        }

        // Filtros adicionales
        if (!empty($filters['q'])) {
            $where[] = "(a.titulo LIKE ? OR a.descripcion LIKE ?)";
            $params[] = '%' . $filters['q'] . '%';
            $params[] = '%' . $filters['q'] . '%';
        }
        if (!empty($filters['familia_id'])) {
            $where[] = "a.familia_id = ?";
            $params[] = $filters['familia_id'];
        }
        if (!empty($filters['curso_id'])) {
            $where[] = "a.curso_id = ?";
            $params[] = $filters['curso_id'];
        }
        if (!empty($filters['asignatura_id'])) {
            $where[] = "a.asignatura_id = ?";
            $params[] = $filters['asignatura_id'];
        }
        if (!empty($filters['tipo'])) {
            $where[] = "a.tipo = ?";
            $params[] = $filters['tipo'];
        }
        if (!empty($filters['dificultad'])) {
            $where[] = "a.dificultad = ?";
            $params[] = $filters['dificultad'];
        }
        if (!empty($filters['visibilidad'])) {
            $where[] = "a.visibilidad = ?";
            $params[] = $filters['visibilidad'];
        }
        if (!empty($filters['estado'])) {
            $where[] = "a.estado = ?";
            $params[] = $filters['estado'];
        }

        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        // Orden
        $orden = $filters['orden'] ?? 'fecha';
        $orderSql = 'a.updated_at DESC, a.id DESC';
        if ($orden === 'popularidad') {
            $orderSql = 'popularidad DESC, a.updated_at DESC';
        } elseif ($orden === 'dificultad') {
            $orderSql = 'a.dificultad ASC, a.updated_at DESC';
        }
        $sql .= " ORDER BY $orderSql LIMIT 200";

        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        return $st->fetchAll();
    }

    /**
     * Crea una actividad y su bloque específico.
     */
    public function create(array $data, int $profesorId, ?int $centroId = null): int
    {
        $this->validateActividadData($data);

        $sql = 'INSERT INTO actividades (profesor_id, centro_id, familia_id, curso_id, asignatura_id, tema_id, tipo, visibilidad, estado, titulo, descripcion, dificultad, created_at, updated_at) 
                VALUES (:prof, :centro, :fam, :cur, :asi, :tema, :tipo, :vis, :est, :tit, :des, :dif, NOW(), NOW())';

        $st = $this->pdo->prepare($sql);
        $st->execute([
            ':prof' => $profesorId,
            ':centro' => $centroId,
            ':fam' => (int) $data['familia_id'],
            ':cur' => (int) $data['curso_id'],
            ':asi' => (int) $data['asignatura_id'],
            ':tema' => !empty($data['tema_id']) ? (int) $data['tema_id'] : null,
            ':tipo' => $data['tipo'],
            ':vis' => $data['visibilidad'] ?? 'privada',
            ':est' => $data['estado'] ?? 'borrador',
            ':tit' => $data['titulo'],
            ':des' => !empty($data['descripcion']) ? $data['descripcion'] : null,
            ':dif' => !empty($data['dificultad']) ? $data['dificultad'] : null,
        ]);

        $actividadId = (int) $this->pdo->lastInsertId();

        // Bloques específicos
        switch ($data['tipo']) {
            case 'tarea':
                $this->createTarea($actividadId, $data);
                break;
            case 'verdadero_falso':
                $this->createVF($actividadId, $data);
                break;
            case 'respuesta_corta':
                $this->createRC($actividadId, $data);
                break;
            case 'rellenar_huecos':
                $this->createRH($actividadId, $data);
                break;
            case 'opcion_multiple':
                $this->createOM($actividadId, $data);
                break;
            case 'emparejar':
                $this->createEmparejar($actividadId, $data);
                break;
        }

        return $actividadId;
    }

    private function createTarea(int $aid, array $data): void
    {
        $sql = 'INSERT INTO actividades_tarea (actividad_id, instrucciones, perm_texto, perm_archivo, perm_enlace, max_archivos, max_peso_mb, evaluacion_modo, puntuacion_max, rubrica_json, created_at, updated_at)
                VALUES (:aid, :inst, :pt, :pa, :pe, :maxf, :maxmb, :modo, :pmax, :rub, NOW(), NOW())';
        $st = $this->pdo->prepare($sql);
        $st->execute([
            ':aid' => $aid,
            ':inst' => !empty($data['t_instrucciones']) ? $data['t_instrucciones'] : null,
            ':pt' => isset($data['t_perm_texto']) ? 1 : 0,
            ':pa' => isset($data['t_perm_archivo']) ? 1 : 0,
            ':pe' => isset($data['t_perm_enlace']) ? 1 : 0,
            ':maxf' => !empty($data['t_max_archivos']) ? (int) $data['t_max_archivos'] : null,
            ':maxmb' => !empty($data['t_max_peso_mb']) ? (int) $data['t_max_peso_mb'] : null,
            ':modo' => !empty($data['t_evaluacion_modo']) ? $data['t_evaluacion_modo'] : null,
            ':pmax' => !empty($data['t_puntuacion_max']) ? (int) $data['t_puntuacion_max'] : null,
            ':rub' => !empty($data['t_rubrica_json']) ? $data['t_rubrica_json'] : null,
        ]);
    }

    private function createVF(int $aid, array $data): void
    {
        if (empty($data['vf_respuesta_correcta']))
            throw new RuntimeException('Debes indicar si la respuesta correcta es Verdadero o Falso.');

        $sql = 'INSERT INTO actividades_vf (actividad_id, respuesta_correcta, feedback_correcta, feedback_incorrecta, created_at, updated_at)
                VALUES (:aid, :resp, :fb_ok, :fb_fail, NOW(), NOW())';
        $st = $this->pdo->prepare($sql);
        $st->execute([
            ':aid' => $aid,
            ':resp' => $data['vf_respuesta_correcta'],
            ':fb_ok' => !empty($data['vf_feedback_correcta']) ? $data['vf_feedback_correcta'] : null,
            ':fb_fail' => !empty($data['vf_feedback_incorrecta']) ? $data['vf_feedback_incorrecta'] : null,
        ]);
    }

    private function createRC(int $aid, array $data): void
    {
        $sql = 'INSERT INTO actividades_rc (actividad_id, modo, case_sensitive, normalizar_acentos, trim_espacios, palabras_clave_json, coincidencia_minima, puntuacion_max, regex_pattern, regex_flags, respuesta_muestra, feedback_correcta, feedback_incorrecta, created_at, updated_at)
                VALUES (:aid, :modo, :case_s, :acentos, :trim, :pjson, :cmin, :pmax, :rpat, :rflags, :rmuestra, :fb_ok, :fb_fail, NOW(), NOW())';
        $st = $this->pdo->prepare($sql);
        $st->execute([
            ':aid' => $aid,
            ':modo' => $data['rc_modo'] ?? 'palabras_clave',
            ':case_s' => isset($data['rc_case_sensitive']) ? 1 : 0,
            ':acentos' => isset($data['rc_normalizar_acentos']) ? 1 : 0,
            ':trim' => isset($data['rc_trim']) ? 1 : 0,
            ':pjson' => !empty($data['rc_palabras_clave_json']) ? $data['rc_palabras_clave_json'] : null,
            ':cmin' => !empty($data['rc_coincidencia_minima']) ? (int) $data['rc_coincidencia_minima'] : null,
            ':pmax' => !empty($data['rc_puntuacion_max']) ? (int) $data['rc_puntuacion_max'] : null,
            ':rpat' => !empty($data['rc_regex_pattern']) ? $data['rc_regex_pattern'] : null,
            ':rflags' => !empty($data['rc_regex_flags']) ? $data['rc_regex_flags'] : null,
            ':rmuestra' => !empty($data['rc_answer_muestra']) ? $data['rc_answer_muestra'] : null,
            ':fb_ok' => !empty($data['rc_feedback_correcta']) ? $data['rc_feedback_correcta'] : null,
            ':fb_fail' => !empty($data['rc_feedback_incorrecta']) ? $data['rc_feedback_incorrecta'] : null,
        ]);
    }

    private function createRH(int $aid, array $data): void
    {
        $sql = 'INSERT INTO actividades_rh (actividad_id, enunciado_html, huecos_json, case_sensitive, normalizar_acentos, trim_espacios, puntuacion_max, feedback_correcta, feedback_incorrecta, created_at, updated_at)
                VALUES (:aid, :html, :huecos, :case_s, :acentos, :trim, :pmax, :fb_ok, :fb_fail, NOW(), NOW())';
        $st = $this->pdo->prepare($sql);
        $st->execute([
            ':aid' => $aid,
            ':html' => $data['rh_plantilla'] ?? '',
            ':huecos' => !empty($data['rh_soluciones_json']) ? $data['rh_soluciones_json'] : null,
            ':case_s' => isset($data['rh_case_sensitive']) ? 1 : 0,
            ':acentos' => isset($data['rh_normalizar_acentos']) ? 1 : 0,
            ':trim' => isset($data['rh_trim']) ? 1 : 0,
            ':pmax' => !empty($data['rh_puntuacion_max']) ? (int) $data['rh_puntuacion_max'] : null,
            ':fb_ok' => !empty($data['rh_feedback_correcta']) ? $data['rh_feedback_correcta'] : null,
            ':fb_fail' => !empty($data['rh_feedback_incorrecta']) ? $data['rh_feedback_incorrecta'] : null,
        ]);
    }

    private function createOM(int $aid, array $data): void
    {
        $sql = 'INSERT INTO actividades_om (actividad_id, enunciado_html, feedback_correcta, feedback_incorrecta, created_at, updated_at)
                VALUES (:aid, :enun, :fb_ok, :fb_fail, NOW(), NOW())';
        $st = $this->pdo->prepare($sql);
        $st->execute([
            ':aid' => $aid,
            ':enun' => $data['om_enunciado_html'] ?? '',
            ':fb_ok' => !empty($data['om_feedback_correcta']) ? $data['om_feedback_correcta'] : null,
            ':fb_fail' => !empty($data['om_feedback_incorrecta']) ? $data['om_feedback_incorrecta'] : null,
        ]);

        // Guardar opciones
        $options = $data['om_opciones'] ?? [];
        $correct = (int) ($data['om_correcta'] ?? -1);
        foreach ($options as $idx => $content) {
            $content = trim((string) $content);
            if ($content === '')
                continue;
            $this->addItem($aid, [
                'opcion_html' => $content,
                'es_correcta' => ($idx === $correct) ? 1 : 0,
                'orden' => $idx
            ]);
        }
    }

    private function createEmparejar(int $aid, array $data): void
    {
        $pairs_izq = $data['emp_izq'] ?? [];
        $pairs_der = $data['emp_der'] ?? [];
        $pairs = [];
        $n = max(count($pairs_izq), count($pairs_der));
        for ($i = 0; $i < $n; $i++) {
            $a = trim((string) ($pairs_izq[$i] ?? ''));
            $b = trim((string) ($pairs_der[$i] ?? ''));
            if ($a !== '' && $b !== '')
                $pairs[] = [$a, $b];
        }

        $sql = 'INSERT INTO actividades_emp_pares (actividad_id, pares_json, created_at, updated_at)
                VALUES (:aid, :pares, NOW(), NOW())';
        $st = $this->pdo->prepare($sql);
        $st->execute([
            ':aid' => $aid,
            ':pares' => json_encode($pairs, JSON_UNESCAPED_UNICODE),
        ]);
    }

    /**
     * Actualiza una actividad y su bloque específico.
     */
    public function update(int $id, array $data): void
    {
        $this->validateActividadData($data, $id);

        $sql = 'UPDATE actividades 
                SET familia_id=:fam, curso_id=:cur, asignatura_id=:asi, tema_id=:tema, 
                    tipo=:tipo, visibilidad=:vis, estado=:est, titulo=:tit, 
                    descripcion=:des, dificultad=:dif, updated_at=NOW()
                WHERE id=:id';

        $st = $this->pdo->prepare($sql);
        $st->execute([
            ':id' => $id,
            ':fam' => (int) $data['familia_id'],
            ':cur' => (int) $data['curso_id'],
            ':asi' => (int) $data['asignatura_id'],
            ':tema' => !empty($data['tema_id']) ? (int) $data['tema_id'] : null,
            ':tipo' => $data['tipo'],
            ':vis' => $data['visibilidad'] ?? 'privada',
            ':est' => $data['estado'] ?? 'borrador',
            ':tit' => $data['titulo'],
            ':des' => !empty($data['descripcion']) ? $data['descripcion'] : null,
            ':dif' => !empty($data['dificultad']) ? $data['dificultad'] : null,
        ]);

        // Actualizar bloques específicos (Borrar y volver a crear para simplificar)
        $this->pdo->prepare('DELETE FROM actividades_tarea WHERE actividad_id=?')->execute([$id]);
        $this->pdo->prepare('DELETE FROM actividades_vf WHERE actividad_id=?')->execute([$id]);
        $this->pdo->prepare('DELETE FROM actividades_rc WHERE actividad_id=?')->execute([$id]);
        $this->pdo->prepare('DELETE FROM actividades_rh WHERE actividad_id=?')->execute([$id]);
        $this->pdo->prepare('DELETE FROM actividades_om WHERE actividad_id=?')->execute([$id]);
        $this->pdo->prepare('DELETE FROM actividades_emp_pares WHERE actividad_id=?')->execute([$id]);
        $this->pdo->prepare('DELETE FROM actividades_om_opciones WHERE actividad_id=?')->execute([$id]);

        switch ($data['tipo']) {
            case 'tarea':
                $this->createTarea($id, $data);
                break;
            case 'verdadero_falso':
                $this->createVF($id, $data);
                break;
            case 'respuesta_corta':
                $this->createRC($id, $data);
                break;
            case 'rellenar_huecos':
                $this->createRH($id, $data);
                break;
            case 'opcion_multiple':
                $this->createOM($id, $data);
                break;
            case 'emparejar':
                $this->createEmparejar($id, $data);
                break;
        }
    }

    /**
     * Duplica una actividad y todos sus datos relacionados.
     */
    public function duplicate(int $id, ?int $newProfesorId = null, ?int $newCentroId = null): int
    {
        $original = $this->findFull($id);
        if (!$original)
            throw new RuntimeException('Actividad no encontrada.');

        $data = $original;
        $data['titulo'] .= ' (Copia)';

        $profId = $newProfesorId ?? (int) $original['profesor_id'];
        $centId = $newCentroId ?? (int) ($original['centro_id'] ?? 0);

        return $this->create($data, $profId, $centId);
    }

    /**
     * Elimina una actividad y todos sus datos relacionados.
     */
    public function delete(int $id): void
    {
        // Limpiar tablas específicas
        $this->pdo->prepare('DELETE FROM actividades_tarea WHERE actividad_id=?')->execute([$id]);
        $this->pdo->prepare('DELETE FROM actividades_vf WHERE actividad_id=?')->execute([$id]);
        $this->pdo->prepare('DELETE FROM actividades_rc WHERE actividad_id=?')->execute([$id]);
        $this->pdo->prepare('DELETE FROM actividades_rh WHERE actividad_id=?')->execute([$id]);
        $this->pdo->prepare('DELETE FROM actividades_om WHERE actividad_id=?')->execute([$id]);
        $this->pdo->prepare('DELETE FROM actividades_emp_pares WHERE actividad_id=?')->execute([$id]);
        $this->pdo->prepare('DELETE FROM actividades_om_opciones WHERE actividad_id=?')->execute([$id]);

        $st = $this->pdo->prepare('DELETE FROM actividades WHERE id=:id LIMIT 1');
        $st->execute([':id' => $id]);
    }

    /**
     * Obtiene los ítems de una actividad.
     */
    public function getItems(int $actividadId): array
    {
        $st = $this->pdo->prepare('SELECT * FROM actividades_om_opciones WHERE actividad_id=:aid ORDER BY orden ASC, id ASC');
        $st->execute([':aid' => $actividadId]);
        return $st->fetchAll();
    }

    /**
     * Añade un ítem a una actividad.
     */
    public function addItem(int $actividadId, array $data): int
    {
        $sql = 'INSERT INTO actividades_om_opciones (actividad_id, orden, opcion_html, es_correcta) 
                VALUES (:aid, :ord, :html, :corr)';

        $st = $this->pdo->prepare($sql);
        $st->execute([
            ':aid' => $actividadId,
            ':ord' => $data['orden'] ?? 0,
            ':html' => $data['opcion_html'] ?? '',
            ':corr' => $data['es_correcta'] ?? 0,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    private function validateActividadData(array $data, ?int $ignoreId = null): void
    {
        $v = new Validator($data);
        $v->required('titulo', 'Título')
            ->required('tipo', 'Tipo de actividad');

        if ($v->fails()) {
            throw new RuntimeException($v->getFirstError());
        }
    }
}
