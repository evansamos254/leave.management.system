<?php

class ApprovalWorkflowService
{
    public static function createSteps(int $leaveRequestId): void
    {
        $steps = [
            [1, 'supervisor'],
        ];

        foreach ($steps as [$order, $role]) {
            $stmt = db()->prepare(
                'INSERT INTO approval_steps (leave_request_id, step_order, role)
                 VALUES (?, ?, ?)'
            );
            $stmt->execute([$leaveRequestId, $order, $role]);
        }
    }

    public static function statusForRole(string $role): ?string
    {
        return [
            'supervisor' => 'pending_supervisor',
            'waziri' => 'pending_supervisor',
        ][$role] ?? null;
    }

    public static function roleForStatus(string $status): ?string
    {
        return [
            'pending_supervisor' => 'supervisor',
        ][$status] ?? null;
    }

    public static function nextStatus(string $currentStatus): string
    {
        return [
            'pending_supervisor' => 'approved',
        ][$currentStatus] ?? $currentStatus;
    }

    public static function steps(int $leaveRequestId): array
    {
        $stmt = db()->prepare(
            'SELECT aps.*, u.full_name AS approver_name
             FROM approval_steps aps
             LEFT JOIN users u ON u.id = aps.approver_user_id
             WHERE aps.leave_request_id = ?
             ORDER BY aps.step_order'
        );
        $stmt->execute([$leaveRequestId]);

        return $stmt->fetchAll();
    }

    public static function hasStarted(int $leaveRequestId): bool
    {
        $stmt = db()->prepare(
            "SELECT COUNT(*)
             FROM approval_steps
             WHERE leave_request_id = ? AND action <> 'pending'"
        );
        $stmt->execute([$leaveRequestId]);

        return (int) $stmt->fetchColumn() > 0;
    }

    public static function recordAction(
        int $leaveRequestId,
        string $role,
        int $approverUserId,
        string $action,
        ?string $comments
    ): void {
        $stmt = db()->prepare(
            'UPDATE approval_steps
             SET approver_user_id = ?, action = ?, comments = ?, acted_at = NOW()
             WHERE leave_request_id = ? AND role = ?'
        );
        $stmt->execute([$approverUserId, $action, $comments, $leaveRequestId, $role]);
    }
}
