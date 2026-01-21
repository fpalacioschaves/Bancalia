<?php
declare(strict_types=1);

class CursoService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function find(int $id): ?array
    {
        $st = $this->pdo->prepare("SELECT * FROM cursos WHERE id = ?");
        $st->execute([$id]);
        return $st->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function findAll(array $filters = []): array
    {
        $sql = "SELECT c.*, f.nombre as familia_nombre 
                FROM cursos c 
                JOIN familias_profesionales f ON f.id = c.familia_id";
        $where = [];
        $params = [];

        if (!empty($filters['q'])) {
            $where[] = "(c.nombre LIKE :q OR c.slug LIKE :q OR f.nombre LIKE :q)";
            $params[':q'] = "%" . $filters['q'] . "%";
        }
        if (isset($filters['familia_id']) && $filters['familia_id'] > 0) {
            $where[] = "c.familia_id = :familia_id";
            $params[':familia_id'] = $filters['familia_id'];
        }
        if (isset($filters['onlyActive']) && $filters['onlyActive']) {
            $where[] = "c.is_active = 1";
        }

        if ($where) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        $sql .= " ORDER BY f.nombre ASC, c.orden ASC, c.nombre ASC";
        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(array $data): int
    {
        $this->validate($data);
        $slug = $data['slug'] ?? str_slug($data['nombre']);

        $st = $this->pdo->prepare("
            INSERT INTO cursos (familia_id, nombre, slug, descripcion, orden, is_active, created_at, updated_at)
            VALUES (:familia_id, :nombre, :slug, :descripcion, :orden, :is_active, NOW(), NOW())
        ");
        $st->execute([
            ':familia_id' => $data['familia_id'],
            ':nombre' => $data['nombre'],
            ':slug' => $slug,
            ':descripcion' => $data['descripcion'] ?? null,
            ':orden' => $data['orden'] ?? 1,
            ':is_active' => isset($data['is_active']) ? (int) $data['is_active'] : 1
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $this->validate($data, $id);
        $slug = $data['slug'] ?? str_slug($data['nombre']);

        $st = $this->pdo->prepare("
            UPDATE cursos SET
                familia_id = :familia_id,
                nombre = :nombre,
                slug = :slug,
                descripcion = :descripcion,
                orden = :orden,
                is_active = :is_active,
                updated_at = NOW()
            WHERE id = :id
        ");
        $st->execute([
            ':familia_id' => $data['familia_id'],
            ':nombre' => $data['nombre'],
            ':slug' => $slug,
            ':descripcion' => $data['descripcion'] ?? null,
            ':orden' => $data['orden'] ?? 1,
            ':is_active' => isset($data['is_active']) ? (int) $data['is_active'] : 1,
            ':id' => $id
        ]);
    }

    public function delete(int $id): void
    {
        $st = $this->pdo->prepare("DELETE FROM cursos WHERE id = ?");
        $st->execute([$id]);
    }

    private function validate(array $data, ?int $ignoreId = null): void
    {
        if (empty($data['nombre'])) {
            throw new RuntimeException("El nombre es obligatorio.");
        }
        if (empty($data['familia_id'])) {
            throw new RuntimeException("La familia profesional es obligatoria.");
        }

        $slug = $data['slug'] ?? str_slug($data['nombre']);

        $sql = "SELECT id FROM cursos WHERE familia_id = :familia_id AND slug = :slug";
        $params = [':familia_id' => $data['familia_id'], ':slug' => $slug];
        if ($ignoreId) {
            $sql .= " AND id != :id";
            $params[':id'] = $ignoreId;
        }

        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        if ($st->fetch()) {
            throw new RuntimeException("Ya existe un curso con ese nombre o slug en esta familia.");
        }
    }
}
