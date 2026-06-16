<?php

class Directorate
{
    public static function all(): array
    {
        $stmt = db()->query('SELECT * FROM directorates ORDER BY name');

        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = db()->prepare('SELECT * FROM directorates WHERE id = ?');
        $stmt->execute([$id]);
        $directorate = $stmt->fetch();

        return $directorate ?: null;
    }
}
