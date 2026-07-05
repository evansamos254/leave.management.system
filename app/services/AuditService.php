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

    public static function historyForUser(int $userId, int $limit = 200): array
    {
        $limit = max(1, min($limit, 500));
        $stmt = db()->prepare(
            'SELECT al.*,
                    actor.full_name AS actor_name,
                    actor.email AS actor_email,
                    lr.id AS leave_request_id,
                    lr.status AS leave_status,
                    lr.start_date AS leave_start_date,
                    lr.end_date AS leave_end_date,
                    lr.days_requested AS leave_days_requested,
                    lt.name AS leave_type_name,
                    owner.id AS leave_owner_id,
                    owner.full_name AS leave_owner_name,
                    owner.email AS leave_owner_email
             FROM audit_logs al
             LEFT JOIN users actor ON actor.id = al.user_id
             LEFT JOIN leave_requests lr ON al.entity_type = "leave_requests" AND lr.id = al.entity_id
             LEFT JOIN leave_types lt ON lt.id = lr.leave_type_id
             LEFT JOIN employees owner_emp ON owner_emp.id = lr.employee_id
             LEFT JOIN users owner ON owner.id = owner_emp.user_id
             WHERE al.user_id = :user_id
                OR (al.entity_type = "users" AND al.entity_id = :user_id)
                OR (owner.id = :user_id)
             ORDER BY al.created_at DESC, al.id DESC
             LIMIT ' . $limit
        );
        $stmt->execute(['user_id' => $userId]);

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
