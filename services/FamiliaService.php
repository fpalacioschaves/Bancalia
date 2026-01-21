<?php
declare(strict_types=1);

class FamiliaService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function find(int $id): ?array
    {
        $st = $this->pdo->prepare("SELECT * FROM familias_profesionales WHERE id = ?");
        $st->execute([$id]);
        return $st->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function findAll(array $filters = []): array
    {
        $sql = "SELECT * FROM familias_profesionales";
        $where = [];
        $params = [];

        if (!empty($filters['q'])) {
            $where[] = "(nombre LIKE :q OR slug LIKE :q)";
            $params[':q'] = "%" . $filters['q'] . "%";
        }
        if (isset($filters['onlyActive']) && $filters['onlyActive']) {
            $where[] = "is_active = 1";
        }

        if ($where) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        $sql .= " ORDER BY nombre ASC";
        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(array $data): int
    {
        $this->validate($data);
        $slug = $data['slug'] ?? str_slug($data['nombre']);

        $st = $this->pdo->prepare("
            INSERT INTO familias_profesionales (nombre, slug, descripcion, is_active, created_at, updated_at)
            VALUES (:nombre, :slug, :descripcion, :is_active, NOW(), NOW())
        ");
        $st->execute([
            ':nombre' => $data['nombre'],
            ':slug' => $slug,
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
            UPDATE familias_profesionales SET
                nombre = :nombre,
                slug = :slug,
                descripcion = :descripcion,
                is_active = :is_active,
                updated_at = NOW()
            WHERE id = :id
        ");
        $st->execute([
            ':nombre' => $data['nombre'],
            ':slug' => $slug,
            ':descripcion' => $data['descripcion'] ?? null,
            ':is_active' => isset($data['is_active']) ? (int) $data['is_active'] : 1,
            ':id' => $id
        ]);
    }

    public function delete(int $id): void
    {
        $st = $this->pdo->prepare("DELETE FROM familias_profesionales WHERE id = ?");
        $st->execute([$id]);
    }

    private function validate(array $data, ?int $ignoreId = null): void
    {
        if (empty($data['nombre'])) {
            throw new RuntimeException("El nombre es obligatorio.");
        }

        $slug = $data['slug'] ?? str_slug($data['nombre']);

        $sql = "SELECT id FROM familias_profesionales WHERE (nombre = :nombre OR slug = :slug)";
        $params = [':nombre' => $data['nombre'], ':slug' => $slug];
        if ($ignoreId) {
            $sql .= " AND id != :id";
            $params[':id'] = $ignoreId;
        }

        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        if ($st->fetch()) {
            throw new RuntimeException("Ya existe una familia con ese nombre o slug.");
        }
    }
}
