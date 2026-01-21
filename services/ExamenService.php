<?php
declare(strict_types=1);

class ExamenService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function find(int $id): ?array
    {
        $st = $this->pdo->prepare('SELECT * FROM examenes WHERE id = :id LIMIT 1');
        $st->execute([':id' => $id]);
        return $st->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function findFull(int $id): ?array
    {
        $st = $this->pdo->prepare('
            SELECT e.*, 
                   p.nombre     AS profesor_nombre,
                   p.apellidos  AS profesor_apellidos,
                   a.nombre     AS asignatura_nombre,
                   c.nombre     AS curso_nombre,
                   f.nombre     AS familia_nombre
            FROM examenes e
            LEFT JOIN profesores p              ON p.id = e.profesor_id
            LEFT JOIN asignaturas a            ON a.id = e.asignatura_id
            LEFT JOIN cursos c                 ON c.id = e.curso_id
            LEFT JOIN familias_profesionales f ON f.id = e.familia_id
            WHERE e.id = :id
            LIMIT 1
        ');
        $st->execute([':id' => $id]);
        return $st->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function findAll(array $filters = []): array
    {
        $sql = "SELECT e.*, 
                       p.nombre AS profesor_nombre, p.apellidos AS profesor_apellidos,
                       f.nombre AS familia_nombre,
                       c.nombre AS curso_nombre,
                       a.nombre AS asignatura_nombre
                FROM examenes e
                LEFT JOIN profesores p ON p.id = e.profesor_id
                LEFT JOIN familias_profesionales f ON f.id = e.familia_id
                LEFT JOIN cursos c ON c.id = e.curso_id
                LEFT JOIN asignaturas a ON a.id = e.asignatura_id";

        $where = [];
        $params = [];

        if (!empty($filters['q'])) {
            $where[] = "e.titulo LIKE :q";
            $params[':q'] = "%" . $filters['q'] . "%";
        }
        if (!empty($filters['estado'])) {
            $where[] = "e.estado = :estado";
            $params[':estado'] = $filters['estado'];
        }
        if (!empty($filters['tipo'])) {
            $where[] = "e.tipo = :tipo";
            $params[':tipo'] = $filters['tipo'];
        }
        if (!empty($filters['profesor_id'])) {
            $where[] = "e.profesor_id = :profesor_id";
            $params[':profesor_id'] = $filters['profesor_id'];
        }

        if ($where) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        $sql .= " ORDER BY e.fecha IS NULL ASC, e.fecha ASC, e.hora ASC, e.id DESC";

        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(array $data): int
    {
        $this->validate($data);

        $sql = "INSERT INTO examenes (
                    profesor_id, familia_id, curso_id, asignatura_id,
                    titulo, descripcion, estado, tipo,
                    fecha, hora, duracion_minutos, created_at, updated_at
                ) VALUES (
                    :profesor_id, :familia_id, :curso_id, :asignatura_id,
                    :titulo, :descripcion, :estado, :tipo,
                    :fecha, :hora, :duracion_minutos, NOW(), NOW()
                )";

        $st = $this->pdo->prepare($sql);
        $st->execute([
            ':profesor_id' => $data['profesor_id'],
            ':familia_id' => $data['familia_id'],
            ':curso_id' => $data['curso_id'],
            ':asignatura_id' => $data['asignatura_id'],
            ':titulo' => $data['titulo'],
            ':descripcion' => !empty($data['descripcion']) ? $data['descripcion'] : null,
            ':estado' => $data['estado'] ?? 'borrador',
            ':tipo' => $data['tipo'] ?? 'examen',
            ':fecha' => !empty($data['fecha']) ? $data['fecha'] : null,
            ':hora' => !empty($data['hora']) ? $data['hora'] : null,
            ':duracion_minutos' => !empty($data['duracion_minutos']) ? (int) $data['duracion_minutos'] : null,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $this->validate($data);

        $sql = "UPDATE examenes SET
                    profesor_id = :profesor_id,
                    familia_id = :familia_id,
                    curso_id = :curso_id,
                    asignatura_id = :asignatura_id,
                    titulo = :titulo,
                    descripcion = :descripcion,
                    estado = :estado,
                    tipo = :tipo,
                    fecha = :fecha,
                    hora = :hora,
                    duracion_minutos = :duracion_minutos,
                    updated_at = NOW()
                WHERE id = :id";

        $st = $this->pdo->prepare($sql);
        $st->execute([
            ':profesor_id' => $data['profesor_id'],
            ':familia_id' => $data['familia_id'],
            ':curso_id' => $data['curso_id'],
            ':asignatura_id' => $data['asignatura_id'],
            ':titulo' => $data['titulo'],
            ':descripcion' => !empty($data['descripcion']) ? $data['descripcion'] : null,
            ':estado' => $data['estado'] ?? 'borrador',
            ':tipo' => $data['tipo'] ?? 'examen',
            ':fecha' => !empty($data['fecha']) ? $data['fecha'] : null,
            ':hora' => !empty($data['hora']) ? $data['hora'] : null,
            ':duracion_minutos' => !empty($data['duracion_minutos']) ? (int) $data['duracion_minutos'] : null,
            ':id' => $id,
        ]);
    }

    public function delete(int $id): void
    {
        // Limpiar asociaciones con actividades
        $this->pdo->prepare("DELETE FROM examenes_actividades WHERE examen_id = ?")->execute([$id]);

        // El delete real
        $st = $this->pdo->prepare("DELETE FROM examenes WHERE id = ?");
        $st->execute([$id]);
    }

    public function getActivities(int $examenId): array
    {
        $sql = "SELECT ea.*, a.titulo, a.tipo
                FROM examenes_actividades ea
                JOIN actividades a ON a.id = ea.actividad_id
                WHERE ea.examen_id = :id
                ORDER BY ea.orden ASC, ea.id ASC";
        $st = $this->pdo->prepare($sql);
        $st->execute([':id' => $examenId]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene las actividades del examen con todos sus datos específicos.
     */
    public function getActivitiesFull(int $examenId): array
    {
        $rows = $this->getActivities($examenId);
        $actService = new ActividadService($this->pdo);

        $full = [];
        foreach ($rows as $r) {
            $f = $actService->findFull((int) $r['actividad_id']);
            if ($f) {
                // Mezclar datos de la relación (orden, puntuación) con los de la actividad
                $f['rel_orden'] = $r['orden'];
                $f['rel_puntuacion'] = $r['puntuacion'];
                $full[] = $f;
            }
        }
        return $full;
    }

    public function syncActivities(int $examenId, array $postedActivities): void
    {
        $this->pdo->beginTransaction();
        try {
            $this->pdo->prepare("DELETE FROM examenes_actividades WHERE examen_id = ?")->execute([$examenId]);

            $ins = $this->pdo->prepare("INSERT INTO examenes_actividades (examen_id, actividad_id, orden, puntuacion) VALUES (?, ?, ?, ?)");
            foreach ($postedActivities as $actId => $data) {
                if (isset($data['selected']) && (int) $data['selected'] === 1) {
                    $ins->execute([
                        $examenId,
                        (int) $actId,
                        (int) ($data['orden'] ?? 1),
                        (!isset($data['puntuacion']) || $data['puntuacion'] === '') ? null : (float) $data['puntuacion']
                    ]);
                }
            }
            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function createAttempt(int $examenId, string $nombre, string $email): int
    {
        $token = bin2hex(random_bytes(16));

        $st = $this->pdo->prepare("
            INSERT INTO examen_intentos (examen_id, nombre_alumno, email_alumno, token, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $st->execute([$examenId, $nombre, $email, $token]);

        return (int) $this->pdo->lastInsertId();
    }

    public function saveAnswers(int $intentoId, array $respuestas): void
    {
        $st = $this->pdo->prepare("
            INSERT INTO examen_respuestas (intento_id, actividad_id, respuesta_json)
            VALUES (?, ?, ?)
        ");

        foreach ($respuestas as $actividadId => $resp) {
            $json = json_encode($resp, JSON_UNESCAPED_UNICODE);
            $st->execute([$intentoId, $actividadId, $json]);
        }
    }

    public function getAttempt(int $intentoId): ?array
    {
        $st = $this->pdo->prepare("
            SELECT 
                ei.*,
                e.id        AS examen_id,
                e.titulo    AS examen_titulo,
                e.descripcion AS examen_descripcion
            FROM examen_intentos ei
            JOIN examenes e ON e.id = ei.examen_id
            WHERE ei.id = ?
        ");
        $st->execute([$intentoId]);
        return $st->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function getAttempts(int $examenId): array
    {
        $st = $this->pdo->prepare("
            SELECT *
            FROM examen_intentos
            WHERE examen_id = ?
            ORDER BY id DESC
        ");
        $st->execute([$examenId]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAnswers(int $intentoId): array
    {
        $st = $this->pdo->prepare("
            SELECT actividad_id, respuesta_json, puntuacion, corregida
            FROM examen_respuestas
            WHERE intento_id = ?
        ");
        $st->execute([$intentoId]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateAnswerGrades(int $intentoId, array $notasActividad): float
    {
        $this->pdo->beginTransaction();
        try {
            foreach ($notasActividad as $actIdStr => $notaStr) {
                $actividadId = (int) $actIdStr;
                $notaStr = trim((string) $notaStr);

                if ($actividadId <= 0)
                    continue;

                if ($notaStr === '') {
                    $puntuacion = null;
                    $corregida = 0;
                } else {
                    $puntuacion = str_replace(',', '.', $notaStr);
                    $puntuacion = (float) $puntuacion;
                    $puntuacion = round($puntuacion, 2);
                    $corregida = 1;
                }

                $stUp = $this->pdo->prepare("
                    UPDATE examen_respuestas
                    SET puntuacion = :puntuacion,
                        corregida  = :corregida
                    WHERE intento_id = :intento_id
                      AND actividad_id = :actividad_id
                ");
                $stUp->execute([
                    ':puntuacion' => $puntuacion,
                    ':corregida' => $corregida,
                    ':intento_id' => $intentoId,
                    ':actividad_id' => $actividadId,
                ]);
            }

            // Recalcular nota total
            $stSum = $this->pdo->prepare("
                SELECT SUM(COALESCE(puntuacion,0)) AS total
                FROM examen_respuestas
                WHERE intento_id = ?
            ");
            $stSum->execute([$intentoId]);
            $total = (float) ($stSum->fetchColumn() ?? 0);

            $stUpInt = $this->pdo->prepare("
                UPDATE examen_intentos
                SET nota = :nota,
                    corregido = 1
                WHERE id = :id
            ");
            $stUpInt->execute([
                ':nota' => $total,
                ':id' => $intentoId,
            ]);

            $this->pdo->commit();
            return $total;
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function getPendingSummary(int $profesorId): array
    {
        $st = $this->pdo->prepare('
            SELECT
                e.id,
                e.titulo,
                e.fecha,
                e.hora,
                COUNT(ei.id) AS intentos_totales,
                SUM(CASE WHEN ei.corregido = 1 THEN 1 ELSE 0 END) AS intentos_corregidos,
                SUM(CASE WHEN ei.corregido IS NULL OR ei.corregido = 0 THEN 1 ELSE 0 END) AS intentos_pendientes
            FROM examenes e
            LEFT JOIN examen_intentos ei ON ei.examen_id = e.id
            WHERE e.profesor_id = :p
            GROUP BY e.id, e.titulo, e.fecha, e.hora
            ORDER BY e.fecha IS NULL ASC, e.fecha DESC, e.hora DESC, e.id DESC
        ');
        $st->execute([':p' => $profesorId]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPendingTasks(int $profesorId): array
    {
        $st = $this->pdo->prepare('
            SELECT
                a.id          AS actividad_id,
                a.titulo      AS actividad_titulo,
                e.id          AS examen_id,
                e.titulo      AS examen_titulo,
                COUNT(er.id)  AS pendientes
            FROM examenes e
            JOIN examenes_actividades ea ON ea.examen_id = e.id
            JOIN actividades a            ON a.id = ea.actividad_id AND a.tipo = "tarea"
            JOIN examen_intentos ei       ON ei.examen_id = e.id
            JOIN examen_respuestas er     ON er.intento_id = ei.id AND er.actividad_id = a.id
            WHERE e.profesor_id = :p
                AND er.puntuacion IS NULL
            GROUP BY a.id, a.titulo, e.id, e.titulo
            HAVING pendientes > 0
            ORDER BY pendientes DESC, e.titulo ASC, a.titulo ASC
        ');
        $st->execute([':p' => $profesorId]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    private function validate(array $data): void
    {
        if (empty($data['titulo']))
            throw new RuntimeException('El título es obligatorio.');
        if (empty($data['profesor_id']))
            throw new RuntimeException('El profesor es obligatorio.');
        if (empty($data['familia_id']))
            throw new RuntimeException('La familia es obligatoria.');
        if (empty($data['curso_id']))
            throw new RuntimeException('El curso es obligatorio.');
        if (empty($data['asignatura_id']))
            throw new RuntimeException('La asignatura es obligatoria.');

        // Validar coherencia (opcional pero recomendado)
        // Por ahora confiamos en los IDs enviados desde el formulario que ya vienen de los selects.
    }
}
