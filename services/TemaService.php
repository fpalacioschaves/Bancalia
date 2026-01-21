<?php
declare(strict_types=1);

class TemaService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function find(int $id): ?array
    {
        $st = $this->pdo->prepare("SELECT * FROM temas WHERE id = ?");
        $st->execute([$id]);
        return $st->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function findFull(int $id): ?array
    {
        $st = $this->pdo->prepare("
            SELECT t.*, a.nombre as asignatura_nombre, c.nombre as curso_nombre, f.nombre as familia_nombre
            FROM temas t
            JOIN asignaturas a ON a.id = t.asignatura_id
            JOIN cursos c ON c.id = a.curso_id
            JOIN familias_profesionales f ON f.id = a.familia_id
            WHERE t.id = ?
        ");
        $st->execute([$id]);
        return $st->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function findAll(array $filters = []): array
    {
        $sql = "SELECT t.*, a.nombre as asignatura_nombre, c.nombre as curso_nombre, f.nombre as familia_nombre
                FROM temas t
                JOIN asignaturas a ON a.id = t.asignatura_id
                JOIN cursos c ON c.id = a.curso_id
                JOIN familias_profesionales f ON f.id = a.familia_id";
        $where = [];
        $params = [];

        if (!empty($filters['q'])) {
            $where[] = "(t.nombre LIKE :q OR t.slug LIKE :q OR a.nombre LIKE :q OR c.nombre LIKE :q OR f.nombre LIKE :q)";
            $params[':q'] = "%" . $filters['q'] . "%";
        }
        if (isset($filters['asignatura_id']) && $filters['asignatura_id'] > 0) {
            $where[] = "t.asignatura_id = :asignatura_id";
            $params[':asignatura_id'] = $filters['asignatura_id'];
        }
        if (isset($filters['onlyActive']) && $filters['onlyActive']) {
            $where[] = "t.is_active = 1";
        }

        if ($where) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        $sql .= " ORDER BY f.nombre ASC, c.orden ASC, a.orden ASC, t.numero ASC, t.nombre ASC";
        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(array $data): int
    {
        $this->validate($data);
        $slug = $data['slug'] ?? str_slug($data['nombre']);

        $st = $this->pdo->prepare("
            INSERT INTO temas (asignatura_id, nombre, slug, numero, descripcion, is_active, created_at, updated_at)
            VALUES (:asignatura_id, :nombre, :slug, :numero, :descripcion, :is_active, NOW(), NOW())
        ");
        $st->execute([
            ':asignatura_id' => $data['asignatura_id'],
            ':nombre' => $data['nombre'],
            ':slug' => $slug,
            ':numero' => $data['numero'] ?? 1,
            ':descripcion' => $data['descripcion'] ?? null,
            ':is_active' => isset($data['is_active']) ? (int) $data['is_active'] : 1
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $this->validate($data, $id);
        $slug = $data['slug'] ?? str_slug($data['nombre']);

        $st = $this->pdo->prepare("
            UPDATE temas SET
                asignatura_id = :asignatura_id,
                nombre = :nombre,
                slug = :slug,
                numero = :numero,
                descripcion = :descripcion,
                is_active = :is_active,
                updated_at = NOW()
            WHERE id = :id
        ");
        $st->execute([
            ':asignatura_id' => $data['asignatura_id'],
            ':nombre' => $data['nombre'],
            ':slug' => $slug,
            ':numero' => $data['numero'] ?? 1,
            ':descripcion' => $data['descripcion'] ?? null,
            ':is_active' => isset($data['is_active']) ? (int) $data['is_active'] : 1,
            ':id' => $id
        ]);
    }

    public function delete(int $id): void
    {
        $st = $this->pdo->prepare("DELETE FROM temas WHERE id = ?");
        $st->execute([$id]);
    }

    private function validate(array $data, ?int $ignoreId = null): void
    {
        if (empty($data['nombre'])) {
            throw new RuntimeException("El nombre es obligatorio.");
        }
        if (empty($data['asignatura_id'])) {
            throw new RuntimeException("La asignatura es obligatoria.");
        }

        $asignatura_id = (int) $data['asignatura_id'];
        $slug = $data['slug'] ?? str_slug($data['nombre']);
        $numero = (int) ($data['numero'] ?? 1);

        // Unicidad slug por asignatura
        $sql = "SELECT id FROM temas WHERE asignatura_id = :asignatura_id AND slug = :slug";
        $params = [':asignatura_id' => $asignatura_id, ':slug' => $slug];
        if ($ignoreId) {
            $sql .= " AND id != :id";
            $params[':id'] = $ignoreId;
        }
        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        if ($st->fetch()) {
            throw new RuntimeException("Ya existe un tema con ese slug en esta asignatura.");
        }

        // Unicidad numero por asignatura
        $sql = "SELECT id FROM temas WHERE asignatura_id = :asignatura_id AND numero = :numero";
        $params = [':asignatura_id' => $asignatura_id, ':numero' => $numero];
        if ($ignoreId) {
            $sql .= " AND id != :id";
            $params[':id'] = $ignoreId;
        }
        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        if ($st->fetch()) {
            throw new RuntimeException("Ya existe un tema con ese número en esta asignatura.");
        }
    }
}
