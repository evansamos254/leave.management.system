<?php

class Department
{
    public static function all(): array
    {
        $stmt = db()->query(
            'SELECT d.*, dir.name AS directorate_name
             FROM departments d
             LEFT JOIN directorates dir ON dir.id = d.directorate_id
             ORDER BY dir.name, d.name'
        );

        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = db()->prepare(
            'SELECT d.*, dir.name AS directorate_name
             FROM departments d
             LEFT JOIN directorates dir ON dir.id = d.directorate_id
             WHERE d.id = ?'
        );
        $stmt->execute([$id]);
        $department = $stmt->fetch();

        return $department ?: null;
    }

    public static function belongsToDirectorate(int $departmentId, int $directorateId): bool
    {
        $department = self::find($departmentId);

        return $department && (int) ($department['directorate_id'] ?? 0) === $directorateId;
    }

    public static function firstOrCreate(string $name, ?int $directorateId = null): int
    {
        $name = trim($name);
        $stmt = db()->prepare('SELECT id FROM departments WHERE name = ? LIMIT 1');
        $stmt->execute([$name]);
        $id = $stmt->fetchColumn();

        if ($id) {
            return (int) $id;
        }

        $stmt = db()->prepare('INSERT INTO departments (directorate_id, name) VALUES (?, ?)');
        $stmt->execute([$directorateId, $name]);

        return (int) db()->lastInsertId();
    }
}
