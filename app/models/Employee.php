<?php

class Employee
{
    public static function create(array $data): int
    {
        $stmt = db()->prepare(
            'INSERT INTO employees (user_id, staff_id, department_id, designation, job_group, supervisor_id, employment_date)
             VALUES (:user_id, :staff_id, :department_id, :designation, :job_group, :supervisor_id, :employment_date)'
        );

        $stmt->execute([
            'user_id' => $data['user_id'],
            'staff_id' => $data['staff_id'],
            'department_id' => $data['department_id'] ?? null,
            'designation' => $data['designation'] ?? null,
            'job_group' => normalize_job_group($data['job_group'] ?? null),
            'supervisor_id' => $data['supervisor_id'] ?? null,
            'employment_date' => $data['employment_date'] ?: null,
        ]);

        return (int) db()->lastInsertId();
    }

    public static function find(int $id): ?array
    {
        $stmt = db()->prepare(
            'SELECT e.*, u.full_name, u.email, u.gender, u.phone, u.role,
                    d.directorate_id, d.name AS department_name, dir.name AS directorate_name
             FROM employees e
             JOIN users u ON u.id = e.user_id
             LEFT JOIN departments d ON d.id = e.department_id
             LEFT JOIN directorates dir ON dir.id = d.directorate_id
             WHERE e.id = ?'
        );
        $stmt->execute([$id]);
        $employee = $stmt->fetch();

        return $employee ?: null;
    }

    public static function findByUserId(int $userId): ?array
    {
        $stmt = db()->prepare(
            'SELECT e.*, u.full_name, u.email, u.gender, u.phone, u.role,
                    d.directorate_id, d.name AS department_name, dir.name AS directorate_name
             FROM employees e
             JOIN users u ON u.id = e.user_id
             LEFT JOIN departments d ON d.id = e.department_id
             LEFT JOIN directorates dir ON dir.id = d.directorate_id
             WHERE e.user_id = ?'
        );
        $stmt->execute([$userId]);
        $employee = $stmt->fetch();

        return $employee ?: null;
    }

    public static function findByStaffId(string $staffId): ?array
    {
        $stmt = db()->prepare('SELECT * FROM employees WHERE staff_id = ? LIMIT 1');
        $stmt->execute([$staffId]);
        $employee = $stmt->fetch();

        return $employee ?: null;
    }

    public static function approvers(?array $viewer = null): array
    {
        $params = [];
        $scope = AccessScopeService::employeeScopeSql('e', $viewer, $params);
        $stmt = db()->prepare(
            "SELECT e.id AS employee_id, u.full_name, u.role, e.staff_id
             FROM employees e
             JOIN users u ON u.id = e.user_id
             WHERE u.role IN ('supervisor', 'hr', 'director', 'admin') AND u.status = 'active'
             $scope
             ORDER BY u.full_name"
        );
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public static function updateSupervisor(int $employeeId, ?int $supervisorId): void
    {
        $stmt = db()->prepare('UPDATE employees SET supervisor_id = ? WHERE id = ?');
        $stmt->execute([$supervisorId, $employeeId]);
    }

    public static function updateDetails(int $employeeId, array $data): void
    {
        $stmt = db()->prepare(
            'UPDATE employees
             SET staff_id = :staff_id,
                 department_id = :department_id,
                 designation = :designation,
                 job_group = :job_group,
                 supervisor_id = :supervisor_id,
                 employment_date = :employment_date
             WHERE id = :id'
        );

        $stmt->execute([
            'staff_id' => $data['staff_id'],
            'department_id' => $data['department_id'] ?? null,
            'designation' => $data['designation'] ?? null,
            'job_group' => normalize_job_group($data['job_group'] ?? null),
            'supervisor_id' => $data['supervisor_id'] ?? null,
            'employment_date' => ($data['employment_date'] ?? '') !== '' ? $data['employment_date'] : null,
            'id' => $employeeId,
        ]);
    }

    public static function workersWithAccounts(): array
    {
        $stmt = db()->query(
            "SELECT u.id, u.full_name, u.email, u.national_id, u.gender, u.role, u.phone, u.status, u.created_at,
                    e.id AS employee_id, e.staff_id, e.designation, e.job_group, e.supervisor_id,
                    d.directorate_id, d.name AS department_name,
                    dir.name AS directorate_name,
                    su.full_name AS supervisor_name
             FROM employees e
             JOIN users u ON u.id = e.user_id
             LEFT JOIN departments d ON d.id = e.department_id
             LEFT JOIN directorates dir ON dir.id = d.directorate_id
             LEFT JOIN employees se ON se.id = e.supervisor_id
             LEFT JOIN users su ON su.id = se.user_id
             WHERE u.role <> 'admin'
             ORDER BY u.created_at DESC"
        );

        return $stmt->fetchAll();
    }
}
