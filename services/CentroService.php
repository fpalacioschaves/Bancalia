<?php
declare(strict_types=1);

class CentroService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Busca un centro por ID.
     */
    public function find(int $id): ?array
    {
        $st = $this->pdo->prepare('SELECT * FROM centros WHERE id=:id LIMIT 1');
        $st->execute([':id' => $id]);
        return $st->fetch() ?: null;
    }

    /**
     * Busca un centro por Slug.
     */
    public function findBySlug(string $slug): ?array
    {
        $st = $this->pdo->prepare('SELECT * FROM centros WHERE slug=:s LIMIT 1');
        $st->execute([':s' => $slug]);
        return $st->fetch() ?: null;
    }

    /**
     * Obtiene todos los centros con filtros opcionales.
     */
    public function findAll(array $filters = []): array
    {
        $params = [];
        $sql = 'SELECT id, nombre, slug, localidad, provincia, comunidad, telefono, email, web, is_active FROM centros';
        $w = [];

        if (!empty($filters['q'])) {
            $w[] = '(nombre LIKE :q OR localidad LIKE :q OR provincia LIKE :q OR comunidad LIKE :q OR slug LIKE :q)';
            $params[':q'] = '%' . $filters['q'] . '%';
        }

        if (isset($filters['onlyActive']) && $filters['onlyActive']) {
            $w[] = 'is_active = 1';
        }

        if ($w) {
            $sql .= ' WHERE ' . implode(' AND ', $w);
        }

        $sql .= ' ORDER BY nombre ASC';

        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        return $st->fetchAll();
    }

    /**
     * Crea un nuevo centro.
     */
    public function create(array $data): int
    {
        $this->validateCentroData($data);

        if (empty($data['slug'])) {
            $data['slug'] = str_slug($data['nombre']);
        }

        // Unicidad de slug
        if ($this->findBySlug($data['slug'])) {
            throw new RuntimeException('Ya existe un centro con ese slug.');
        }

        $sql = 'INSERT INTO centros (nombre, slug, direccion, cp, localidad, provincia, comunidad, telefono, email, web, lat, lng, is_active, created_at, updated_at) 
                VALUES (:n, :s, :d, :cp, :loc, :pr, :co, :t, :e, :w, :lat, :lng, :active, NOW(), NOW())';

        $st = $this->pdo->prepare($sql);
        $st->execute([
            ':n' => $data['nombre'],
            ':s' => $data['slug'],
            ':d' => $data['direccion'] ?? null,
            ':cp' => $data['cp'] ?? null,
            ':loc' => $data['localidad'] ?? null,
            ':pr' => $data['provincia'] ?? null,
            ':co' => $data['comunidad'] ?? null,
            ':t' => $data['telefono'] ?? null,
            ':e' => (!empty($data['email']) && filter_var($data['email'], FILTER_VALIDATE_EMAIL)) ? $data['email'] : null,
            ':w' => $data['web'] ?? null,
            ':lat' => isset($data['lat']) ? (float) $data['lat'] : null,
            ':lng' => isset($data['lng']) ? (float) $data['lng'] : null,
            ':active' => $data['is_active'] ?? 1,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Actualiza un centro existente.
     */
    public function update(int $id, array $data): void
    {
        $this->validateCentroData($data, $id);

        if (empty($data['slug'])) {
            $data['slug'] = str_slug($data['nombre']);
        }

        // Unicidad de slug excluyendo este id
        $chk = $this->pdo->prepare('SELECT 1 FROM centros WHERE slug=:s AND id<>:id LIMIT 1');
        $chk->execute([':s' => $data['slug'], ':id' => $id]);
        if ($chk->fetch()) {
            throw new RuntimeException('Ya existe otro centro con ese slug.');
        }

        $sql = 'UPDATE centros 
                SET nombre=:n, slug=:s, direccion=:d, cp=:cp, localidad=:loc, provincia=:pr, comunidad=:co,
                    telefono=:t, email=:e, web=:w, lat=:lat, lng=:lng, is_active=:active, updated_at=NOW()
                WHERE id=:id';

        $st = $this->pdo->prepare($sql);
        $st->execute([
            ':n' => $data['nombre'],
            ':s' => $data['slug'],
            ':d' => $data['direccion'] ?? null,
            ':cp' => $data['cp'] ?? null,
            ':loc' => $data['localidad'] ?? null,
            ':pr' => $data['provincia'] ?? null,
            ':co' => $data['comunidad'] ?? null,
            ':t' => $data['telefono'] ?? null,
            ':e' => (!empty($data['email']) && filter_var($data['email'], FILTER_VALIDATE_EMAIL)) ? $data['email'] : null,
            ':w' => $data['web'] ?? null,
            ':lat' => isset($data['lat']) ? (float) $data['lat'] : null,
            ':lng' => isset($data['lng']) ? (float) $data['lng'] : null,
            ':active' => $data['is_active'] ?? 1,
            ':id' => $id
        ]);
    }

    /**
     * Elimina un centro.
     */
    public function delete(int $id): void
    {
        $st = $this->pdo->prepare('DELETE FROM centros WHERE id=:id LIMIT 1');
        $st->execute([':id' => $id]);
    }

    private function validateCentroData(array $data, ?int $ignoreId = null): void
    {
        $v = new Validator($data);
        $v->required('nombre', 'Nombre');

        if (!empty($data['email'])) {
            $v->email('email', 'Email');
        }

        if ($v->fails()) {
            throw new RuntimeException($v->getFirstError());
        }
    }
}
