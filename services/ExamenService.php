<?php
declare(strict_types=1);

namespace Services;

use PDO;
use RuntimeException;

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
