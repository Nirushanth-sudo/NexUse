<?php
/**
 * NexUse — base model.
 *
 * MVC layer: Core. Every model extends this and inherits the common table
 * operations, so individual models only carry queries specific to their entity.
 */

declare(strict_types=1);

namespace App\Core;

abstract class Model
{
    /** Table this model reads and writes. */
    protected static string $table = '';

    /** Primary key column. */
    protected static string $key = 'id';

    /**
     * Find one row by primary key.
     *
     * @return array<string, mixed>|null
     */
    public static function find(int $id): ?array
    {
        return Database::one(
            'SELECT * FROM ' . static::$table . ' WHERE ' . static::$key . ' = ?',
            [$id]
        );
    }

    /**
     * Every row, optionally ordered.
     *
     * @return list<array<string, mixed>>
     */
    public static function all(string $orderBy = ''): array
    {
        $sql = 'SELECT * FROM ' . static::$table;

        if ($orderBy !== '') {
            $sql .= ' ORDER BY ' . $orderBy;
        }

        return Database::all($sql);
    }

    /**
     * Insert a row and return its new id.
     *
     * @param array<string, mixed> $data Column => value
     */
    public static function create(array $data): int
    {
        $columns      = array_keys($data);
        $placeholders = array_fill(0, count($columns), '?');

        Database::run(
            'INSERT INTO ' . static::$table
            . ' (' . implode(', ', $columns) . ')'
            . ' VALUES (' . implode(', ', $placeholders) . ')',
            array_values($data)
        );

        return Database::lastId();
    }

    /**
     * Update one row by primary key.
     *
     * @param array<string, mixed> $data Column => value
     */
    public static function update(int $id, array $data): void
    {
        if (empty($data)) {
            return;
        }

        $assignments = implode(', ', array_map(
            static fn(string $column): string => $column . ' = ?',
            array_keys($data)
        ));

        Database::run(
            'UPDATE ' . static::$table . ' SET ' . $assignments
            . ' WHERE ' . static::$key . ' = ?',
            [...array_values($data), $id]
        );
    }

    /**
     * Delete one row by primary key.
     */
    public static function delete(int $id): void
    {
        Database::run(
            'DELETE FROM ' . static::$table . ' WHERE ' . static::$key . ' = ?',
            [$id]
        );
    }

    /**
     * Count rows, optionally filtered by a WHERE fragment.
     *
     * @param array<int, mixed> $params
     */
    public static function count(string $where = '', array $params = []): int
    {
        $sql = 'SELECT COUNT(*) FROM ' . static::$table;

        if ($where !== '') {
            $sql .= ' WHERE ' . $where;
        }

        return (int) Database::value($sql, $params, 0);
    }

    /**
     * Does a row exist matching this WHERE fragment?
     *
     * @param array<int, mixed> $params
     */
    public static function exists(string $where, array $params): bool
    {
        return self::count($where, $params) > 0;
    }
}
