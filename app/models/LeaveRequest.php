<?php

class LeaveRequest
{
    public static function create(array $data): int
    {
        $stmt = db()->prepare(
            'INSERT INTO leave_requests
             (employee_id, leave_type_id, contact_number, start_date, end_date, days_requested, reason, handover_notes, attachment_path, passport_photo_path)
             VALUES
             (:employee_id, :leave_type_id, :contact_number, :start_date, :end_date, :days_requested, :reason, :handover_notes, :attachment_path, :passport_photo_path)'
        );

        $stmt->execute([
            'employee_id' => $data['employee_id'],
            'leave_type_id' => $data['leave_type_id'],
            'contact_number' => normalize_kenyan_phone_number($data['contact_number'] ?? null),
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'days_requested' => $data['days_requested'],
            'reason' => $data['reason'] ?? null,
            'handover_notes' => $data['handover_notes'] ?? null,
            'attachment_path' => $data['attachment_path'] ?? null,
            'passport_photo_path' => $data['passport_photo_path'] ?? null,
        ]);

        return (int) db()->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $stmt = db()->prepare(
            'UPDATE leave_requests
             SET leave_type_id = :leave_type_id,
                 contact_number = :contact_number,
                 start_date = :start_date,
                 end_date = :end_date,
                 days_requested = :days_requested,
                 reason = :reason,
                 handover_notes = :handover_notes,
                 attachment_path = :attachment_path,
                 passport_photo_path = :passport_photo_path
             WHERE id = :id'
        );

        $stmt->execute([
            'leave_type_id' => $data['leave_type_id'],
            'contact_number' => normalize_kenyan_phone_number($data['contact_number'] ?? null),
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'days_requested' => $data['days_requested'],
            'reason' => $data['reason'] ?? null,
            'handover_notes' => $data['handover_notes'] ?? null,
            'attachment_path' => $data['attachment_path'] ?? null,
            'passport_photo_path' => $data['passport_photo_path'] ?? null,
            'id' => $id,
        ]);
    }

    public static function find(int $id): ?array
    {
        $stmt = db()->prepare(
            'SELECT lr.*, lt.name AS leave_type_name, lt.requires_balance,
                    e.user_id AS employee_user_id, e.staff_id, e.department_id, e.designation, e.job_group, e.supervisor_id,
                    u.full_name AS employee_name, u.email AS employee_email, u.phone AS employee_phone,
                    ru.full_name AS resumed_by_name,
                    rr.full_name AS recalled_by_name,
                    lf.id AS forfeiture_id, lf.days_forfeited, lf.payout_amount, lf.notes AS forfeiture_notes,
                    lf.recorded_at AS forfeited_at, lfu.full_name AS forfeited_by_name,
                    d.directorate_id, d.name AS department_name,
                    dir.name AS directorate_name
             FROM leave_requests lr
             JOIN leave_types lt ON lt.id = lr.leave_type_id
             JOIN employees e ON e.id = lr.employee_id
             JOIN users u ON u.id = e.user_id
             LEFT JOIN users ru ON ru.id = lr.resumed_by_user_id
             LEFT JOIN users rr ON rr.id = lr.recalled_by_user_id
             LEFT JOIN leave_forfeitures lf ON lf.leave_request_id = lr.id
             LEFT JOIN users lfu ON lfu.id = lf.recorded_by_user_id
             LEFT JOIN departments d ON d.id = e.department_id
             LEFT JOIN directorates dir ON dir.id = d.directorate_id
             WHERE lr.id = ?'
        );
        $stmt->execute([$id]);
        $request = $stmt->fetch();

        return $request ?: null;
    }

    public static function forEmployee(int $employeeId): array
    {
        $stmt = db()->prepare(
            'SELECT lr.*, lt.name AS leave_type_name,
                    lf.id AS forfeiture_id, lf.days_forfeited, lf.payout_amount, lf.notes AS forfeiture_notes,
                    lf.recorded_at AS forfeited_at, lfu.full_name AS forfeited_by_name,
                    rr.full_name AS recalled_by_name
             FROM leave_requests lr
             JOIN leave_types lt ON lt.id = lr.leave_type_id
             LEFT JOIN leave_forfeitures lf ON lf.leave_request_id = lr.id
             LEFT JOIN users lfu ON lfu.id = lf.recorded_by_user_id
             LEFT JOIN users rr ON rr.id = lr.recalled_by_user_id
             WHERE lr.employee_id = ?
             ORDER BY lr.submitted_at DESC'
        );
        $stmt->execute([$employeeId]);

        return $stmt->fetchAll();
    }

    public static function activeForEmployee(int $employeeId, ?string $date = null): ?array
    {
        $date = $date ?: date('Y-m-d');
        $stmt = db()->prepare(
            "SELECT lr.*, lt.name AS leave_type_name
             FROM leave_requests lr
             JOIN leave_types lt ON lt.id = lr.leave_type_id
                WHERE lr.employee_id = ?
                  AND (
                      lr.status = 'pending_supervisor'
                      OR (
                          lr.status = 'approved'
                          AND lr.resumed_at IS NULL
                          AND lr.recalled_at IS NULL
                          AND lr.end_date >= ?
                      )
                  )
             ORDER BY lr.submitted_at ASC, lr.id ASC
             LIMIT 1"
        );
        $stmt->execute([$employeeId, $date]);
        $request = $stmt->fetch();

        return $request ?: null;
    }

    public static function awaitingResumptionForEmployee(int $employeeId, ?string $date = null): ?array
    {
        $date = $date ?: date('Y-m-d');
        $stmt = db()->prepare(
            "SELECT lr.*, lt.name AS leave_type_name,
                    e.user_id AS employee_user_id, e.staff_id, e.department_id, e.designation, e.job_group, e.supervisor_id,
                    u.full_name AS employee_name, u.email AS employee_email, u.phone AS employee_phone,
                    d.directorate_id, d.name AS department_name,
                    dir.name AS directorate_name
             FROM leave_requests lr
             JOIN leave_types lt ON lt.id = lr.leave_type_id
             JOIN employees e ON e.id = lr.employee_id
             JOIN users u ON u.id = e.user_id
             LEFT JOIN departments d ON d.id = e.department_id
             LEFT JOIN directorates dir ON dir.id = d.directorate_id
             WHERE lr.employee_id = ?
               AND lr.status = 'approved'
               AND lr.resumed_at IS NULL
               AND lr.recalled_at IS NULL
               AND lr.start_date <= ?
             ORDER BY lr.end_date DESC, lr.start_date DESC, lr.id DESC
             LIMIT 1"
        );
        $stmt->execute([$employeeId, $date]);
        $request = $stmt->fetch();

        return $request ?: null;
    }

    public static function allRequests(?string $status = null, ?string $search = null, ?array $viewer = null): array
    {
        $params = [];
        $scope = AccessScopeService::employeeScopeSql('e', $viewer, $params);
        $sql = "SELECT lr.*, lt.name AS leave_type_name,
                       e.staff_id, e.department_id, e.job_group,
                       u.full_name AS employee_name,
                       u.email AS employee_email,
                       rr.full_name AS recalled_by_name,
                       d.directorate_id, d.name AS department_name,
                       dir.name AS directorate_name
                FROM leave_requests lr
                JOIN leave_types lt ON lt.id = lr.leave_type_id
                JOIN employees e ON e.id = lr.employee_id
                JOIN users u ON u.id = e.user_id
                LEFT JOIN users rr ON rr.id = lr.recalled_by_user_id
                LEFT JOIN departments d ON d.id = e.department_id
                LEFT JOIN directorates dir ON dir.id = d.directorate_id
                WHERE 1 = 1
                $scope";

        if ($status !== null && $status !== '') {
            if ($status === 'pending') {
                $sql .= " AND lr.status = 'pending_supervisor'";
            } elseif ($status === 'recalled') {
                $sql .= ' AND lr.recalled_at IS NOT NULL';
            } elseif ($status === 'approved') {
                $sql .= " AND lr.status = 'approved' AND lr.recalled_at IS NULL";
            } else {
                $sql .= ' AND lr.status = ?';
                $params[] = $status;
            }
        }

        if ($search !== null && trim($search) !== '') {
            $term = '%' . trim($search) . '%';
            $sql .= ' AND (u.full_name LIKE ? OR u.email LIKE ? OR e.staff_id LIKE ? OR e.job_group LIKE ? OR lt.name LIKE ?)';
            array_push($params, $term, $term, $term, $term, $term);
        }

        $sql .= ' ORDER BY lr.submitted_at DESC';

        $stmt = db()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public static function pendingForRole(string $role, ?int $employeeId = null, ?array $viewer = null): array
    {
        $status = ApprovalWorkflowService::statusForRole($role);
        if (!$status && $role !== 'admin') {
            return [];
        }

        $sql = "SELECT lr.*, lt.name AS leave_type_name,
                       e.staff_id, e.department_id, e.job_group, e.supervisor_id,
                       u.full_name AS employee_name,
                       d.directorate_id, d.name AS department_name,
                       dir.name AS directorate_name
                FROM leave_requests lr
                JOIN leave_types lt ON lt.id = lr.leave_type_id
                JOIN employees e ON e.id = lr.employee_id
                JOIN users u ON u.id = e.user_id
                LEFT JOIN departments d ON d.id = e.department_id
                LEFT JOIN directorates dir ON dir.id = d.directorate_id
                WHERE ";

        $params = [];

        if ($role === 'admin') {
            $sql .= "lr.status = 'pending_supervisor'";
        } else {
            $sql .= 'lr.status = ?';
            $params[] = $status;
        }

        if ($role === 'supervisor' && $employeeId) {
            $sql .= ' AND (e.supervisor_id = ? OR e.supervisor_id IS NULL)';
            $params[] = $employeeId;
        }

        $sql .= AccessScopeService::employeeScopeSql('e', $viewer, $params);

        $sql .= ' ORDER BY lr.submitted_at ASC';

        $stmt = db()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public static function approvedBetween(string $from, string $to, string $role = 'admin', ?int $employeeId = null): array
    {
        $params = [$to, $from];
        $scope = self::visibilityScope('e', $role, $employeeId, $params);

        $stmt = db()->prepare(
            "SELECT lr.*, lt.name AS leave_type_name,
                    e.staff_id, e.department_id, e.job_group, e.supervisor_id,
                    u.full_name AS employee_name,
                    rr.full_name AS recalled_by_name,
                    d.directorate_id, d.name AS department_name,
                    dir.name AS directorate_name
             FROM leave_requests lr
             JOIN leave_types lt ON lt.id = lr.leave_type_id
             JOIN employees e ON e.id = lr.employee_id
             JOIN users u ON u.id = e.user_id
             LEFT JOIN users rr ON rr.id = lr.recalled_by_user_id
             LEFT JOIN departments d ON d.id = e.department_id
             LEFT JOIN directorates dir ON dir.id = d.directorate_id
             WHERE lr.status = 'approved'
               AND lr.recalled_at IS NULL
               AND lr.start_date <= ?
               AND lr.end_date >= ?
               $scope
             ORDER BY lr.start_date ASC, u.full_name ASC"
        );
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public static function liveOverview(string $role, ?int $employeeId = null, ?string $date = null): array
    {
        $date = $date ?: date('Y-m-d');

        return [
            'today' => $date,
            'on_leave' => self::approvedForDate($date, $role, $employeeId, 200),
            'returning_today' => self::returningOn($date, $role, $employeeId, 200),
            'upcoming' => self::upcomingApproved($date, $role, $employeeId, 200),
            'pending_by_stage' => self::pendingStageCounts($role, $employeeId),
        ];
    }

    public static function approvedForDate(string $date, string $role, ?int $employeeId = null, int $limit = 8): array
    {
        $params = [$date, $date];
        $scope = self::visibilityScope('e', $role, $employeeId, $params);
        $limit = max(1, min($limit, 200));

        $stmt = db()->prepare(
            "SELECT lr.*, lt.name AS leave_type_name,
                    e.department_id,
                    u.full_name AS employee_name,
                    rr.full_name AS recalled_by_name,
                    d.directorate_id, d.name AS department_name,
                    dir.name AS directorate_name
             FROM leave_requests lr
             JOIN leave_types lt ON lt.id = lr.leave_type_id
             JOIN employees e ON e.id = lr.employee_id
             JOIN users u ON u.id = e.user_id
             LEFT JOIN users rr ON rr.id = lr.recalled_by_user_id
             LEFT JOIN departments d ON d.id = e.department_id
             LEFT JOIN directorates dir ON dir.id = d.directorate_id
             WHERE lr.status = 'approved'
               AND lr.resumed_at IS NULL
               AND lr.recalled_at IS NULL
               AND lr.start_date <= ?
               AND lr.end_date >= ?
               $scope
             ORDER BY lr.end_date ASC, u.full_name ASC
             LIMIT $limit"
        );
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public static function returningOn(string $date, string $role, ?int $employeeId = null, int $limit = 8): array
    {
        $params = [$date];
        $scope = self::visibilityScope('e', $role, $employeeId, $params);
        $limit = max(1, min($limit, 200));

        $stmt = db()->prepare(
            "SELECT lr.*, lt.name AS leave_type_name,
                    e.department_id,
                    u.full_name AS employee_name,
                    rr.full_name AS recalled_by_name,
                    d.directorate_id, d.name AS department_name,
                    dir.name AS directorate_name
             FROM leave_requests lr
             JOIN leave_types lt ON lt.id = lr.leave_type_id
             JOIN employees e ON e.id = lr.employee_id
             JOIN users u ON u.id = e.user_id
             LEFT JOIN users rr ON rr.id = lr.recalled_by_user_id
             LEFT JOIN departments d ON d.id = e.department_id
             LEFT JOIN directorates dir ON dir.id = d.directorate_id
             WHERE lr.status = 'approved'
               AND lr.resumed_at IS NULL
               AND lr.recalled_at IS NULL
               AND lr.end_date = ?
               $scope
             ORDER BY u.full_name ASC
             LIMIT $limit"
        );
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public static function upcomingApproved(string $date, string $role, ?int $employeeId = null, int $limit = 8): array
    {
        $params = [$date];
        $scope = self::visibilityScope('e', $role, $employeeId, $params);
        $limit = max(1, min($limit, 200));

        $stmt = db()->prepare(
            "SELECT lr.*, lt.name AS leave_type_name,
                    e.department_id,
                    u.full_name AS employee_name,
                    rr.full_name AS recalled_by_name,
                    d.directorate_id, d.name AS department_name,
                    dir.name AS directorate_name
             FROM leave_requests lr
             JOIN leave_types lt ON lt.id = lr.leave_type_id
             JOIN employees e ON e.id = lr.employee_id
             JOIN users u ON u.id = e.user_id
             LEFT JOIN users rr ON rr.id = lr.recalled_by_user_id
             LEFT JOIN departments d ON d.id = e.department_id
             LEFT JOIN directorates dir ON dir.id = d.directorate_id
             WHERE lr.status = 'approved'
               AND lr.resumed_at IS NULL
               AND lr.recalled_at IS NULL
               AND lr.start_date > ?
               $scope
             ORDER BY lr.start_date ASC, u.full_name ASC
             LIMIT $limit"
        );
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public static function endingSoonReminders(int $windowDays = 1): array
    {
        $windowDays = max(0, $windowDays);
        $boundaryDate = date('Y-m-d', strtotime('+' . $windowDays . ' day'));

        $stmt = db()->prepare(
            "SELECT lr.*, lt.name AS leave_type_name,
                    e.user_id AS employee_user_id,
                    e.staff_id, e.department_id, e.designation, e.job_group, e.supervisor_id,
                    u.full_name AS employee_name, u.email AS employee_email, u.phone AS employee_phone,
                    rr.full_name AS recalled_by_name,
                    d.directorate_id, d.name AS department_name,
                    dir.name AS directorate_name,
                    DATEDIFF(lr.end_date, CURDATE()) AS days_until_end
             FROM leave_requests lr
             JOIN leave_types lt ON lt.id = lr.leave_type_id
             JOIN employees e ON e.id = lr.employee_id
             JOIN users u ON u.id = e.user_id
             LEFT JOIN users rr ON rr.id = lr.recalled_by_user_id
             LEFT JOIN departments d ON d.id = e.department_id
             LEFT JOIN directorates dir ON dir.id = d.directorate_id
             WHERE lr.status = 'approved'
               AND lr.resumed_at IS NULL
               AND lr.recalled_at IS NULL
               AND lr.start_date <= CURDATE()
               AND lr.end_date BETWEEN CURDATE() AND ?
               AND lr.end_reminder_sent_at IS NULL
             ORDER BY lr.end_date ASC, u.full_name ASC"
        );
        $stmt->execute([$boundaryDate]);

        return $stmt->fetchAll();
    }

    public static function pendingStageCounts(string $role = 'admin', ?int $employeeId = null): array
    {
        $counts = [
            'pending_supervisor' => 0,
        ];

        $params = [];
        $statusFilter = "lr.status = 'pending_supervisor'";

        if ($role === 'supervisor') {
            $status = ApprovalWorkflowService::statusForRole($role);
            $statusFilter = 'lr.status = ?';
            $params[] = $status;
        }

        $scope = self::visibilityScope('e', $role, $employeeId, $params);

        $stmt = db()->prepare(
            "SELECT lr.status, COUNT(*) AS total
             FROM leave_requests lr
             JOIN employees e ON e.id = lr.employee_id
             WHERE $statusFilter
             $scope
             GROUP BY lr.status"
        );
        $stmt->execute($params);

        foreach ($stmt->fetchAll() as $row) {
            $counts[$row['status']] = (int) $row['total'];
        }

        return $counts;
    }

    public static function updateStatus(int $id, string $status, ?string $rejectionReason = null): void
    {
        $finalized = in_array($status, ['approved', 'rejected', 'cancelled', 'forfeited'], true) ? ', finalized_at = NOW()' : '';
        $stmt = db()->prepare(
            "UPDATE leave_requests
             SET status = ?, rejection_reason = ? $finalized
             WHERE id = ?"
        );
        $stmt->execute([$status, $rejectionReason, $id]);
    }

    public static function markResumed(int $id, int $userId, ?string $notes = null): void
    {
        $stmt = db()->prepare(
            "UPDATE leave_requests
             SET resumed_at = NOW(),
                 resumed_by_user_id = ?,
                 resumption_notes = ?
             WHERE id = ? AND status = 'approved' AND resumed_at IS NULL"
        );
        $stmt->execute([$userId, $notes, $id]);
    }

    public static function markRecalled(int $id, int $userId, ?string $reason = null, ?string $attachmentPath = null): bool
    {
        $notes = trim((string) $reason);
        $recallNotes = $notes !== ''
            ? 'Recalled from leave by immediate supervisor. Reason: ' . $notes
            : 'Recalled from leave by immediate supervisor.';

        $stmt = db()->prepare(
            "UPDATE leave_requests
             SET recalled_at = NOW(),
                 recalled_by_user_id = ?,
                 recall_reason = ?,
                 recall_attachment_path = ?,
                 resumed_at = NOW(),
                 resumed_by_user_id = ?,
                 resumption_notes = ?
             WHERE id = ? AND status = 'approved' AND resumed_at IS NULL AND recalled_at IS NULL"
        );
        $stmt->execute([$userId, $notes !== '' ? $notes : null, $attachmentPath, $userId, $recallNotes, $id]);

        return $stmt->rowCount() > 0;
    }

    public static function markEndReminderSent(int $id): void
    {
        $stmt = db()->prepare(
            'UPDATE leave_requests
             SET end_reminder_sent_at = NOW()
             WHERE id = ?'
        );
        $stmt->execute([$id]);
    }

    public static function counts(?int $employeeId = null, ?array $viewer = null): array
    {
        $params = [];
        $where = 'WHERE 1 = 1';

        if ($employeeId) {
            $where .= ' AND lr.employee_id = ?';
            $params[] = $employeeId;
        }

        $where .= AccessScopeService::employeeScopeSql('e', $viewer, $params);

        $stmt = db()->prepare(
            "SELECT lr.status, COUNT(*) AS total
             FROM leave_requests lr
             JOIN employees e ON e.id = lr.employee_id
             $where
             GROUP BY lr.status"
        );
        $stmt->execute($params);

        $counts = [
            'pending' => 0,
            'approved' => 0,
            'rejected' => 0,
            'cancelled' => 0,
            'forfeited' => 0,
            'recalled' => 0,
            'total' => 0,
        ];

        foreach ($stmt->fetchAll() as $row) {
            $status = $row['status'];
            $total = (int) $row['total'];
            $counts['total'] += $total;

            if (str_starts_with($status, 'pending_')) {
                $counts['pending'] += $total;
            } elseif (isset($counts[$status])) {
                $counts[$status] += $total;
            }
        }

        $recalledStmt = db()->prepare(
            "SELECT COUNT(*)
             FROM leave_requests lr
             JOIN employees e ON e.id = lr.employee_id
             $where
               AND lr.recalled_at IS NOT NULL"
        );
        $recalledStmt->execute($params);
        $counts['recalled'] = (int) $recalledStmt->fetchColumn();
        $counts['approved'] = max(0, $counts['approved'] - $counts['recalled']);

        return $counts;
    }

    public static function reportSummary(?string $from = null, ?string $to = null, ?int $directorateId = null, ?int $departmentId = null, ?array $viewer = null): array
    {
        [$where, $params] = self::reportWhere($from, $to, $directorateId, $departmentId, $viewer);

        $stmt = db()->prepare(
            "SELECT lt.name AS leave_type_name,
                    COUNT(*) AS request_count,
                    SUM(lr.days_requested) AS total_days
             FROM leave_requests lr
             JOIN leave_types lt ON lt.id = lr.leave_type_id
             JOIN employees e ON e.id = lr.employee_id
             LEFT JOIN departments d ON d.id = e.department_id
             LEFT JOIN directorates dir ON dir.id = d.directorate_id
             $where
             GROUP BY lt.name
             ORDER BY lt.name"
        );
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public static function reportDetails(?string $from = null, ?string $to = null, ?int $directorateId = null, ?int $departmentId = null, ?array $viewer = null): array
    {
        [$where, $params] = self::reportWhere($from, $to, $directorateId, $departmentId, $viewer);

        $stmt = db()->prepare(
            "SELECT lr.*, lt.name AS leave_type_name,
                    e.staff_id, e.department_id, e.designation, e.job_group,
                    u.full_name AS employee_name,
                    d.directorate_id, d.name AS department_name,
                    dir.name AS directorate_name
             FROM leave_requests lr
             JOIN leave_types lt ON lt.id = lr.leave_type_id
             JOIN employees e ON e.id = lr.employee_id
             JOIN users u ON u.id = e.user_id
             LEFT JOIN departments d ON d.id = e.department_id
             LEFT JOIN directorates dir ON dir.id = d.directorate_id
             $where
             ORDER BY dir.name, d.name, u.full_name, lr.start_date"
        );
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    private static function reportWhere(?string $from, ?string $to, ?int $directorateId, ?int $departmentId, ?array $viewer = null): array
    {
        $params = [];
        $where = "WHERE lr.status = 'approved' AND lr.recalled_at IS NULL";

        if ($from) {
            $where .= ' AND lr.start_date >= ?';
            $params[] = $from;
        }

        if ($to) {
            $where .= ' AND lr.end_date <= ?';
            $params[] = $to;
        }

        if ($directorateId !== null && $directorateId > 0) {
            $where .= ' AND d.directorate_id = ?';
            $params[] = $directorateId;
        }

        if ($departmentId !== null && $departmentId > 0) {
            $where .= ' AND e.department_id = ?';
            $params[] = $departmentId;
        }

        $where .= AccessScopeService::employeeScopeSql('e', $viewer, $params);

        return [$where, $params];
    }

    private static function visibilityScope(string $employeeAlias, string $role, ?int $employeeId, array &$params): string
    {
        if ($role === 'employee' && $employeeId) {
            $params[] = $employeeId;

            return " AND $employeeAlias.id = ?";
        }

        $viewer = function_exists('current_user') ? current_user() : null;
        if ($viewer && ($viewer['role'] ?? '') === $role) {
            return AccessScopeService::employeeScopeSql($employeeAlias, $viewer, $params);
        }

        return '';
    }
}
