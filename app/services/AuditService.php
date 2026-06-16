<?php

class AuditService
{
    public static function recent(int $limit = 100): array
    {
        $stmt = db()->prepare(
            'SELECT al.*, u.full_name AS actor_name, u.email AS actor_email
             FROM audit_logs al
             LEFT JOIN users u ON u.id = al.user_id
             ORDER BY al.created_at DESC, al.id DESC
             LIMIT ' . max(1, min($limit, 500))
        );
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public static function record(string $action, ?string $entityType = null, ?int $entityId = null, ?int $userId = null): void
    {
        $userId = $userId ?? ($_SESSION['user_id'] ?? null);

        $stmt = db()->prepare(
            'INSERT INTO audit_logs (user_id, action, entity_type, entity_id, ip_address, user_agent)
             VALUES (:user_id, :action, :entity_type, :entity_id, :ip_address, :user_agent)'
        );

        $stmt->execute([
            'user_id' => $userId,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
        ]);
    }
}
