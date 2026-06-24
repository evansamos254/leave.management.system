<?php

class NotificationService
{
    public static function create(int $userId, string $title, string $message, ?string $link = null): void
    {
        $stmt = db()->prepare(
            'INSERT INTO notifications (user_id, title, message, link)
             VALUES (:user_id, :title, :message, :link)'
        );

        $stmt->execute([
            'user_id' => $userId,
            'title' => $title,
            'message' => $message,
            'link' => $link,
        ]);
    }

    public static function notifyRoles(array $roles, string $title, string $message, ?string $link = null): void
    {
        $placeholders = implode(',', array_fill(0, count($roles), '?'));
        $stmt = db()->prepare("SELECT id FROM users WHERE role IN ($placeholders) AND status = 'active'");
        $stmt->execute($roles);

        foreach ($stmt->fetchAll() as $user) {
            self::create((int) $user['id'], $title, $message, $link);
        }
    }

    public static function notifyRolesInEmployeeDepartment(array $roles, int $employeeId, string $title, string $message, ?string $link = null): void
    {
        if (!$roles) {
            return;
        }

        $placeholders = implode(',', array_fill(0, count($roles), '?'));
        $params = array_merge([$employeeId], $roles);
        $stmt = db()->prepare(
            "SELECT u.id
             FROM employees target
             JOIN employees e ON e.department_id = target.department_id
             JOIN users u ON u.id = e.user_id
             WHERE target.id = ?
               AND target.department_id IS NOT NULL
               AND u.role IN ($placeholders)
               AND u.status = 'active'"
        );
        $stmt->execute($params);

        foreach ($stmt->fetchAll() as $user) {
            self::create((int) $user['id'], $title, $message, $link);
        }
    }

    public static function unreadCount(int $userId): int
    {
        $stmt = db()->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0');
        $stmt->execute([$userId]);

        return (int) $stmt->fetchColumn();
    }

    public static function recent(int $userId, int $limit = 5): array
    {
        $stmt = db()->prepare(
            'SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ' . (int) $limit
        );
        $stmt->execute([$userId]);

        return $stmt->fetchAll();
    }

    public static function markRead(int $userId): void
    {
        $stmt = db()->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ?');
        $stmt->execute([$userId]);
    }
}
