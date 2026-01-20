<?php
declare(strict_types=1);

class ProfesorService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Busca un profesor por ID.
     */
    public function find(int $id): ?array
    {
        $st = $this->pdo->prepare('SELECT * FROM profesores WHERE id=:id LIMIT 1');
        $st->execute([':id' => $id]);
        return $st->fetch() ?: null;
    }

    /**
     * Busca un profesor por Email.
     */
    public function findByEmail(string $email): ?array
    {
        $st = $this->pdo->prepare('SELECT * FROM profesores WHERE email=:e LIMIT 1');
        $st->execute([':e' => $email]);
        return $st->fetch() ?: null;
    }

    /**
     * Crea un nuevo profesor.
     */
    public function create(array $data): int
    {
        $this->validateProfesorData($data);

        // Check duplicado email
        if (!empty($data['email']) && $this->findByEmail($data['email'])) {
            throw new RuntimeException('Ya existe un profesor con ese email.');
        }

        $sql = 'INSERT INTO profesores (centro_id, nombre, apellidos, email, telefono, notas, is_active, created_at, updated_at) 
                VALUES (:c, :n, :a, :e, :t, :no, :ac, NOW(), NOW())';

        $st = $this->pdo->prepare($sql);
        $st->execute([
            ':c' => $data['centro_id'] ?? null,
            ':n' => $data['nombre'],
            ':a' => $data['apellidos'],
            ':e' => $data['email'],
            ':t' => $data['telefono'] ?? null,
            ':no' => $data['notas'] ?? null,
            ':ac' => $data['is_active'] ?? 1,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Actualiza un profesor existente.
     */
    public function update(int $id, array $data): void
    {
        $this->validateProfesorData($data, $id);

        $sql = 'UPDATE profesores 
                SET centro_id=:c, nombre=:n, apellidos=:a, email=:e, telefono=:t, notas=:no, is_active=:ac, updated_at=NOW()
                WHERE id=:id';

        $st = $this->pdo->prepare($sql);
        $st->execute([
            ':c' => $data['centro_id'] ?? null,
            ':n' => $data['nombre'],
            ':a' => $data['apellidos'],
            ':e' => $data['email'],
            ':t' => $data['telefono'] ?? null,
            ':no' => $data['notas'] ?? null,
            ':ac' => $data['is_active'] ?? 1,
            ':id' => $id,
        ]);
    }

    /**
     * Gestiona las asignaciones (insertar, actualizar, borrar).
     * Recibe arrays paralelos directamente del formulario o ya procesados.
     * En este caso, para simplificar la refactorización, aceptaremos los datos "crudos" del POST 
     * procesados mínimamente, similar al código original.
     */
    public function saveAssignments(int $profesorId, ?int $centroIdDefault, array $inputs, array $mappings): void
    {
        // DEBUG LOG START
        $logFile = __DIR__ . '/../debug_bancalia.log';
        $logData = "--- " . date('Y-m-d H:i:s') . " saveAssignments ---\n";
        $logData .= "ProfesorID: $profesorId, CentroID: " . var_export($centroIdDefault, true) . "\n";
        $logData .= "Inputs: " . print_r($inputs, true) . "\n";
        file_put_contents($logFile, $logData, FILE_APPEND);
        // DEBUG LOG END

        $famArr = $inputs['familias'] ?? [];
        $curArr = $inputs['cursos'] ?? [];
        $asiArr = $inputs['asignaturas'] ?? [];
        $anioArr = $inputs['anios'] ?? [];
        $hrsArr = $inputs['horas'] ?? [];
        $obsArr = $inputs['obs'] ?? [];
        $idsArr = $inputs['ids'] ?? []; // Para updates
        $delArr = $inputs['delete'] ?? []; // IDs a borrar

        $cursoToFamilia = $mappings['cursoToFamilia'];
        $asigToCurso = $mappings['asigToCurso'];

        $insAsig = $this->pdo->prepare('
          INSERT INTO profesor_asignacion (profesor_id, centro_id, familia_id, curso_id, asignatura_id, anio_academico, horas, observaciones, is_active, created_at, updated_at)
          VALUES (:p, :centro, :fam, :curso, :asig, :anio, :hrs, :obs, 1, NOW(), NOW())
        ');
        $upAsig = $this->pdo->prepare('
          UPDATE profesor_asignacion
          SET familia_id=:fam, curso_id=:curso, asignatura_id=:asig, anio_academico=:anio, horas=:hrs, observaciones=:obs, updated_at=NOW()
          WHERE id=:id AND profesor_id=:p
        ');
        $delAsig = $this->pdo->prepare('DELETE FROM profesor_asignacion WHERE id=:id AND profesor_id=:p');

        $maxLen = max(count($famArr), count($curArr), count($asiArr));

        for ($i = 0; $i < $maxLen; $i++) {
            $paId = (int) ($idsArr[$i] ?? 0);
            $fam = (int) ($famArr[$i] ?? 0);
            $cur = (int) ($curArr[$i] ?? 0);
            $asi = (int) ($asiArr[$i] ?? 0);
            $anio = trim($anioArr[$i] ?? '');
            $hrs = trim($hrsArr[$i] ?? '');
            $obs = trim($obsArr[$i] ?? '');

            // Borrado
            if ($paId > 0 && in_array((string) $i, $delArr, true)) {
                $delAsig->execute([':id' => $paId, ':p' => $profesorId]);
                continue;
            }

            // Skip vacíos
            if ($fam === 0 && $cur === 0 && $asi === 0 && $anio === '' && $hrs === '' && $obs === '')
                continue;

            // Validaciones
            if ($fam <= 0 || $cur <= 0 || $asi <= 0 || $anio === '') {
                throw new RuntimeException("Faltan datos obligatorios en la asignación #" . ($i + 1));
            }
            if (($cursoToFamilia[$cur] ?? 0) !== $fam) {
                throw new RuntimeException("Incoherencia Curso-Familia ($cur -> $fam) vs map (" . ($cursoToFamilia[$cur] ?? 'null') . ") en asig #" . ($i + 1));
            }
            if (($asigToCurso[$asi] ?? 0) !== $cur) {
                throw new RuntimeException("Incoherencia Asignatura-Curso en la asignación #" . ($i + 1));
            }

            $hrsVal = ($hrs !== '' ? max(0, (int) $hrs) : null);

            if ($paId > 0) {
                $upAsig->execute([
                    ':fam' => $fam,
                    ':curso' => $cur,
                    ':asig' => $asi,
                    ':anio' => $anio,
                    ':hrs' => $hrsVal,
                    ':obs' => ($obs !== '' ? $obs : null),
                    ':id' => $paId,
                    ':p' => $profesorId
                ]);
            } else {
                // Asegurar que centroIdDefault sea null si es 0
                $cId = ($centroIdDefault > 0) ? $centroIdDefault : null;
                $insAsig->execute([
                    ':p' => $profesorId,
                    ':centro' => $cId,
                    ':fam' => $fam,
                    ':curso' => $cur,
                    ':asig' => $asi,
                    ':anio' => $anio,
                    ':hrs' => $hrsVal,
                    ':obs' => ($obs !== '' ? $obs : null)
                ]);
            }
        }
    }

    private function validateProfesorData(array $data, ?int $ignoreId = null): void
    {
        if (empty($data['nombre']) || empty($data['apellidos'])) {
            throw new RuntimeException('Nombre y apellidos son obligatorios.');
        }
        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Email no válido.');
        }
        if (!empty($data['centro_id'])) {
            // Verificar centro... (podríamos inyectar CentroService o hacerlo raw)
            // Por simplicidad, asumimos validación previa o FK constraint, 
            // pero idealmente:
            // if (!$this->centroExists($data['centro_id'])) throw ...
        }
    }
}
