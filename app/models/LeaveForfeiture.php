<?php

class LeaveForfeiture
{
    public static function create(array $data): int
    {
        $stmt = db()->prepare(
            'INSERT INTO leave_forfeitures
             (leave_request_id, days_forfeited, payout_amount, notes, recorded_by_user_id)
             VALUES
             (:leave_request_id, :days_forfeited, :payout_amount, :notes, :recorded_by_user_id)'
        );

        $stmt->execute([
            'leave_request_id' => $data['leave_request_id'],
            'days_forfeited' => $data['days_forfeited'],
            'payout_amount' => $data['payout_amount'],
            'notes' => $data['notes'] ?? null,
            'recorded_by_user_id' => $data['recorded_by_user_id'] ?? null,
        ]);

        return (int) db()->lastInsertId();
    }

    public static function findByRequestId(int $leaveRequestId): ?array
    {
        $stmt = db()->prepare(
            'SELECT lf.*, u.full_name AS recorded_by_name, u.email AS recorded_by_email
             FROM leave_forfeitures lf
             LEFT JOIN users u ON u.id = lf.recorded_by_user_id
             WHERE lf.leave_request_id = ?
             LIMIT 1'
        );
        $stmt->execute([$leaveRequestId]);
        $record = $stmt->fetch();

        return $record ?: null;
    }
}
