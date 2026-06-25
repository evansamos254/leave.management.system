<?php

class LeaveBalanceService
{
    public static function businessDays(string $startDate, string $endDate): int
    {
        $start = new DateTime($startDate);
        $end = new DateTime($endDate);

        if ($end < $start) {
            return 0;
        }

        $stmt = db()->prepare('SELECT holiday_date FROM holidays WHERE holiday_date BETWEEN ? AND ?');
        $stmt->execute([$startDate, $endDate]);
        $holidays = array_flip(array_column($stmt->fetchAll(), 'holiday_date'));

        $days = 0;
        while ($start <= $end) {
            $weekday = (int) $start->format('N');
            $date = $start->format('Y-m-d');

            if ($weekday < 6 && !isset($holidays[$date])) {
                $days++;
            }

            $start->modify('+1 day');
        }

        return $days;
    }

    public static function endDateForBusinessDays(string $startDate, int $businessDays): ?string
    {
        if ($businessDays < 1) {
            return null;
        }

        $cursor = new DateTime($startDate);
        $holidays = self::holidaySet();
        $countedDays = 0;
        $guard = 0;

        while ($guard < 1200) {
            if (self::isBusinessDate($cursor, $holidays)) {
                $countedDays++;

                if ($countedDays === $businessDays) {
                    return $cursor->format('Y-m-d');
                }
            }

            $cursor->modify('+1 day');
            $guard++;
        }

        return null;
    }

    public static function returnDateAfter(string $endDate): string
    {
        $cursor = (new DateTime($endDate))->modify('+1 day');
        $holidays = self::holidaySet();
        $guard = 0;

        while (!self::isBusinessDate($cursor, $holidays) && $guard < 60) {
            $cursor->modify('+1 day');
            $guard++;
        }

        return $cursor->format('Y-m-d');
    }

    public static function publicHolidays(): array
    {
        $stmt = db()->query('SELECT holiday_date, name FROM holidays ORDER BY holiday_date ASC');

        return $stmt->fetchAll();
    }

    private static function holidaySet(): array
    {
        return array_flip(array_column(self::publicHolidays(), 'holiday_date'));
    }

    private static function isBusinessDate(DateTime $date, array $holidays): bool
    {
        $weekday = (int) $date->format('N');

        return $weekday < 6 && !isset($holidays[$date->format('Y-m-d')]);
    }

    public static function ensureBalances(int $employeeId, ?int $year = null): void
    {
        $year = $year ?? (int) date('Y');
        $types = LeaveType::active();

        foreach ($types as $type) {
            if (!LeaveType::isBalanceTracked($type)) {
                continue;
            }

            $stmt = db()->prepare(
                'INSERT IGNORE INTO leave_balances (employee_id, leave_type_id, year, entitlement)
                 VALUES (?, ?, ?, ?)'
            );
            $stmt->execute([$employeeId, $type['id'], $year, $type['default_entitlement']]);
        }
    }

    public static function balancesForEmployee(int $employeeId, ?int $year = null): array
    {
        $year = $year ?? (int) date('Y');
        self::ensureBalances($employeeId, $year);

        $stmt = db()->prepare(
            'SELECT lb.*, lt.name, lt.requires_balance, lt.gender_eligibility,
                    (lb.entitlement + lb.carried_forward - lb.used_days) AS available_days
             FROM leave_balances lb
             JOIN leave_types lt ON lt.id = lb.leave_type_id
             WHERE lb.employee_id = ? AND lb.year = ? AND lt.is_active = 1
             ORDER BY lt.name'
        );
        $stmt->execute([$employeeId, $year]);

        return array_values(array_filter(
            $stmt->fetchAll(),
            fn (array $balance): bool => LeaveType::isBalanceTracked($balance)
        ));
    }

    public static function hasEnoughBalance(int $employeeId, int $leaveTypeId, float $days, ?int $year = null): bool
    {
        $year = $year ?? (int) date('Y');
        $type = LeaveType::find($leaveTypeId);
        if (!$type || !LeaveType::isBalanceTracked($type)) {
            return true;
        }

        self::ensureBalances($employeeId, $year);

        $stmt = db()->prepare(
            'SELECT lt.requires_balance,
                    (lb.entitlement + lb.carried_forward - lb.used_days) AS available_days
             FROM leave_types lt
             LEFT JOIN leave_balances lb
               ON lb.leave_type_id = lt.id AND lb.employee_id = ? AND lb.year = ?
             WHERE lt.id = ?'
        );
        $stmt->execute([$employeeId, $year, $leaveTypeId]);
        $row = $stmt->fetch();

        if (!$row) {
            return false;
        }

        return (float) $row['available_days'] >= $days;
    }

    public static function deduct(int $employeeId, int $leaveTypeId, float $days, ?int $year = null): void
    {
        $year = $year ?? (int) date('Y');
        $type = LeaveType::find($leaveTypeId);
        if (!$type || !LeaveType::isBalanceTracked($type)) {
            return;
        }

        self::ensureBalances($employeeId, $year);

        $stmt = db()->prepare(
            'UPDATE leave_balances
             SET used_days = used_days + ?
             WHERE employee_id = ? AND leave_type_id = ? AND year = ?'
        );
        $stmt->execute([$days, $employeeId, $leaveTypeId, $year]);
    }
}
