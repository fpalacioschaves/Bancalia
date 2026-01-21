<?php
declare(strict_types=1);

class AsignaturaService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function find(int $id): ?array
    {
        $st = $this->pdo->prepare("SELECT * FROM asignaturas WHERE id = ?");
        $st->execute([$id]);
        return $st->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function findFull(int $id): ?array
    {
        $st = $this->pdo->prepare("
            SELECT a.*, f.nombre as familia_nombre, c.nombre as curso_nombre
            FROM asignaturas a
            JOIN familias_profesionales f ON f.id = a.familia_id
            JOIN cursos c ON c.id = a.curso_id
            WHERE a.id = ?
        ");
        $st->execute([$id]);
        return $st->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function findAll(array $filters = []): array
    {
        $sql = "SELECT a.*, f.nombre as familia_nombre, c.nombre as curso_nombre
                FROM asignaturas a
                JOIN familias_profesionales f ON f.id = a.familia_id
                JOIN cursos c ON c.id = a.curso_id";
        $where = [];
        $params = [];

        if (!empty($filters['q'])) {
            $where[] = "(a.nombre LIKE :q OR a.slug LIKE :q OR a.codigo LIKE :q OR f.nombre LIKE :q OR c.nombre LIKE :q)";
            $params[':q'] = "%" . $filters['q'] . "%";
        }
        if (isset($filters['familia_id']) && $filters['familia_id'] > 0) {
            $where[] = "a.familia_id = :familia_id";
            $params[':familia_id'] = $filters['familia_id'];
        }
        if (isset($filters['curso_id']) && $filters['curso_id'] > 0) {
            $where[] = "a.curso_id = :curso_id";
            $params[':curso_id'] = $filters['curso_id'];
        }
        if (isset($filters['onlyActive']) && $filters['onlyActive']) {
            $where[] = "a.is_active = 1";
        }

        if ($where) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        $sql .= " ORDER BY f.nombre ASC, c.orden ASC, a.orden ASC, a.nombre ASC";
        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(array $data): int
    {
        $this->validate($data);
        $slug = $data['slug'] ?? str_slug($data['nombre']);

        $st = $this->pdo->prepare("
            INSERT INTO asignaturas (familia_id, curso_id, nombre, slug, codigo, horas, descripcion, orden, is_active, created_at, updated_at)
            VALUES (:familia_id, :curso_id, :nombre, :slug, :codigo, :horas, :descripcion, :orden, :is_active, NOW(), NOW())
        ");
        $st->execute([
            ':familia_id' => $data['familia_id'],
            ':curso_id' => $data['curso_id'],
            ':nombre' => $data['nombre'],
            ':slug' => $slug,
            ':codigo' => $data['codigo'] ?? null,
            ':horas' => $data['horas'] ?? null,
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
            UPDATE asignaturas SET
                familia_id = :familia_id,
                curso_id = :curso_id,
                nombre = :nombre,
                slug = :slug,
                codigo = :codigo,
                horas = :horas,
                descripcion = :descripcion,
                orden = :orden,
                is_active = :is_active,
                updated_at = NOW()
            WHERE id = :id
        ");
        $st->execute([
            ':familia_id' => $data['familia_id'],
            ':curso_id' => $data['curso_id'],
            ':nombre' => $data['nombre'],
            ':slug' => $slug,
            ':codigo' => $data['codigo'] ?? null,
            ':horas' => $data['horas'] ?? null,
            ':descripcion' => $data['descripcion'] ?? null,
            ':orden' => $data['orden'] ?? 1,
            ':is_active' => isset($data['is_active']) ? (int) $data['is_active'] : 1,
            ':id' => $id
        ]);
    }

    public function delete(int $id): void
    {
        $st = $this->pdo->prepare("DELETE FROM asignaturas WHERE id = ?");
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
        if (empty($data['curso_id'])) {
            throw new RuntimeException("El curso es obligatorio.");
        }

        $familia_id = (int) $data['familia_id'];
        $curso_id = (int) $data['curso_id'];

        // Verificar pertenencia curso a familia
        $st = $this->pdo->prepare("SELECT familia_id FROM cursos WHERE id = ?");
        $st->execute([$curso_id]);
        $c = $st->fetch();
        if (!$c)
            throw new RuntimeException("El curso seleccionado no existe.");
        if ((int) $c['familia_id'] !== $familia_id) {
            throw new RuntimeException("El curso no pertenece a la familia seleccionada.");
        }

        $slug = $data['slug'] ?? str_slug($data['nombre']);
        $codigo = $data['codigo'] ?? null;

        // Unicidad slug por curso
        $sql = "SELECT id FROM asignaturas WHERE curso_id = :curso_id AND slug = :slug";
        $params = [':curso_id' => $curso_id, ':slug' => $slug];
        if ($ignoreId) {
            $sql .= " AND id != :id";
            $params[':id'] = $ignoreId;
        }
        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        if ($st->fetch()) {
            throw new RuntimeException("Ya existe una asignatura con ese slug en este curso.");
        }

        // Unicidad código por curso
        if ($codigo) {
            $sql = "SELECT id FROM asignaturas WHERE curso_id = :curso_id AND codigo = :codigo";
            $params = [':curso_id' => $curso_id, ':codigo' => $codigo];
            if ($ignoreId) {
                $sql .= " AND id != :id";
                $params[':id'] = $ignoreId;
            }
            $st = $this->pdo->prepare($sql);
            $st->execute($params);
            if ($st->fetch()) {
                throw new RuntimeException("Ya existe una asignatura con ese código en este curso.");
            }
        }
    }
}
