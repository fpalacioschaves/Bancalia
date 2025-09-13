<?php
declare(strict_types=1);

namespace Src\Core;

use Src\Config\DB;
use PDO;
use Throwable;

final class Crud
{
    /** Listado simple con búsqueda opcional */
    public static function index(array $cfg, array $q): array
    {
        $pdo = DB::pdo();
        $table  = $cfg['table'];
        $pk     = $cfg['pk'];
        $fields = $cfg['fields']; // [name => opts]
        $searchCols = $cfg['search'] ?? [];
        $order  = $cfg['order'] ?? ($pk . ' DESC');
        $limit  = max(1, min((int)($q['limit'] ?? 100), 500));
        $offset = max(0, (int)($q['offset'] ?? 0));

        $where = [];
        $args  = [];

        if (!empty($q['q']) && $searchCols) {
            $term = '%' . $q['q'] . '%';
            $parts = [];
            foreach ($searchCols as $c) { $parts[] = "$c LIKE ?"; $args[] = $term; }
            $where[] = '(' . implode(' OR ', $parts) . ')';
        }

        // Filtros directos por columnas whitelisted
        foreach ($fields as $name => $opts) {
            if (isset($q[$name])) {
                $where[] = "$name = ?";
                $args[]  = $q[$name];
            }
        }

        $sql = "SELECT * FROM {$table}";
        if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
        $sql .= " ORDER BY {$order} LIMIT {$limit} OFFSET {$offset}";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($args);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Obtener por id */
    public static function show(array $cfg, int $id): ?array
    {
        $pdo = DB::pdo();
        $table = $cfg['table']; $pk = $cfg['pk'];
        $st = $pdo->prepare("SELECT * FROM {$table} WHERE {$pk}=?");
        $st->execute([$id]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /** Crear */
    public static function create(array $cfg, array $data): int
    {
        $pdo = DB::pdo();
        $table = $cfg['table'];
        $fields = self::whitelistData($cfg, $data, true);
        if (!$fields) throw new \RuntimeException('Sin datos válidos');

        $cols = array_keys($fields);
        $marks = implode(',', array_fill(0, count($cols), '?'));
        $colSql = implode(',', $cols);
        $st = $pdo->prepare("INSERT INTO {$table} ({$colSql}) VALUES ({$marks})");
        $st->execute(array_values($fields));
        return (int)$pdo->lastInsertId();
    }

    /** Actualizar */
    public static function update(array $cfg, int $id, array $data): void
    {
        $pdo = DB::pdo();
        $table = $cfg['table']; $pk = $cfg['pk'];
        $fields = self::whitelistData($cfg, $data, false);
        if (!$fields) return; // nada que actualizar

        $sets = [];
        $args = [];
        foreach ($fields as $k=>$v) { $sets[] = "{$k}=?"; $args[] = $v; }
        $args[] = $id;

        $sql = "UPDATE {$table} SET ".implode(',', $sets)." WHERE {$pk}=?";
        $st = $pdo->prepare($sql);
        $st->execute($args);
    }

    /** Borrar */
    public static function destroy(array $cfg, int $id): void
    {
        $pdo = DB::pdo();
        $table = $cfg['table']; $pk = $cfg['pk'];
        $st = $pdo->prepare("DELETE FROM {$table} WHERE {$pk}=?");
        $st->execute([$id]);
    }

    /** Limpia el payload con columnas permitidas; gestiona transforms */
    private static function whitelistData(array $cfg, array $data, bool $isCreate): array
    {
        $out = [];
        $fields = $cfg['fields']; // name => ['w'=>bool,'r'=>bool,'transform'=>callable]
        foreach ($fields as $name => $opts) {
            $writable = $opts['w'] ?? true;
            $required = ($opts['r'] ?? false) && $isCreate;
            if (!$writable) continue;

            if (array_key_exists($name, $data)) {
                $val = $data[$name];
                if (isset($opts['transform']) && is_callable($opts['transform'])) {
                    $val = $opts['transform']($val, $data);
                }
                $out[$name] = $val;
            } elseif ($required) {
                throw new \InvalidArgumentException("Falta campo requerido: {$name}");
            }
        }
        return $out;
    }
}
