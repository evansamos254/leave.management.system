<?php

class User
{
    public static function find(int $id): ?array
    {
        $stmt = db()->prepare(
            'SELECT u.*, e.id AS employee_id, e.staff_id, e.department_id, e.designation, e.supervisor_id, e.employment_date,
                    d.directorate_id, d.name AS department_name, dir.name AS directorate_name
             FROM users u
             LEFT JOIN employees e ON e.user_id = u.id
             LEFT JOIN departments d ON d.id = e.department_id
             LEFT JOIN directorates dir ON dir.id = d.directorate_id
             WHERE u.id = ?'
        );
        $stmt->execute([$id]);
        $user = $stmt->fetch();

        return $user ?: null;
    }

    public static function findByEmail(string $email): ?array
    {
        $stmt = db()->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        return $user ?: null;
    }

    public static function findByNationalId(string $nationalId): ?array
    {
        $stmt = db()->prepare('SELECT * FROM users WHERE national_id = ? LIMIT 1');
        $stmt->execute([self::normalizeNationalId($nationalId)]);
        $user = $stmt->fetch();

        return $user ?: null;
    }

    public static function findByLoginIdentifier(string $identifier): ?array
    {
        $identifier = trim($identifier);

        if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            return self::findByEmail(strtolower($identifier));
        }

        return self::findByNationalId($identifier);
    }

    public static function normalizeNationalId(string $nationalId): string
    {
        return strtoupper(preg_replace('/\s+/', '', trim($nationalId)) ?? '');
    }

    public static function create(array $data): int
    {
        $stmt = db()->prepare(
            'INSERT INTO users (full_name, email, national_id, gender, password_hash, role, phone, employment_document_path, status, must_change_password)
             VALUES (:full_name, :email, :national_id, :gender, :password_hash, :role, :phone, :employment_document_path, :status, :must_change_password)'
        );

        $stmt->execute([
            'full_name' => $data['full_name'],
            'email' => $data['email'],
            'national_id' => ($data['national_id'] ?? '') !== '' ? self::normalizeNationalId($data['national_id']) : null,
            'gender' => self::normalizeGender($data['gender'] ?? null),
            'password_hash' => $data['password_hash'],
            'role' => $data['role'] ?? 'employee',
            'phone' => $data['phone'] ?? null,
            'employment_document_path' => $data['employment_document_path'] ?? null,
            'status' => $data['status'] ?? 'active',
            'must_change_password' => !empty($data['must_change_password']) ? 1 : 0,
        ]);

        return (int) db()->lastInsertId();
    }

    public static function updateLastLogin(int $id): void
    {
        $stmt = db()->prepare('UPDATE users SET last_login_at = NOW(), failed_login_attempts = 0, locked_until = NULL WHERE id = ?');
        $stmt->execute([$id]);
    }

    public static function recordFailedLogin(int $id, int $maxAttempts, int $lockoutMinutes): ?array
    {
        $user = self::findByIdOnly($id);
        if (!$user) {
            return null;
        }

        $attempts = (int) ($user['failed_login_attempts'] ?? 0) + 1;
        $lockedUntil = $attempts >= $maxAttempts
            ? date('Y-m-d H:i:s', time() + ($lockoutMinutes * 60))
            : ($user['locked_until'] ?? null);

        $stmt = db()->prepare(
            'UPDATE users
             SET failed_login_attempts = :failed_login_attempts,
                 locked_until = :locked_until
             WHERE id = :id'
        );
        $stmt->execute([
            'failed_login_attempts' => $attempts,
            'locked_until' => $lockedUntil,
            'id' => $id,
        ]);

        return self::findByIdOnly($id);
    }

    public static function clearLoginLock(int $id): void
    {
        $stmt = db()->prepare('UPDATE users SET failed_login_attempts = 0, locked_until = NULL WHERE id = ?');
        $stmt->execute([$id]);
    }

    public static function findByIdOnly(int $id): ?array
    {
        $stmt = db()->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $user = $stmt->fetch();

        return $user ?: null;
    }

    public static function updateProfile(int $id, array $data): void
    {
        $stmt = db()->prepare(
            'UPDATE users
             SET full_name = :full_name,
                 email = :email,
                 national_id = :national_id,
                 gender = :gender,
                 phone = :phone
             WHERE id = :id'
        );

        $stmt->execute([
            'full_name' => $data['full_name'],
            'email' => $data['email'],
            'national_id' => ($data['national_id'] ?? '') !== '' ? self::normalizeNationalId($data['national_id']) : null,
            'gender' => self::normalizeGender($data['gender'] ?? null),
            'phone' => $data['phone'] !== '' ? $data['phone'] : null,
            'id' => $id,
        ]);
    }

    public static function updatePassword(int $id, string $passwordHash): void
    {
        $stmt = db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
        $stmt->execute([$passwordHash, $id]);
    }

    public static function setPasswordChangeRequired(int $id, bool $required): void
    {
        $stmt = db()->prepare('UPDATE users SET must_change_password = ? WHERE id = ?');
        $stmt->execute([$required ? 1 : 0, $id]);
    }

    public static function delete(int $id): void
    {
        $stmt = db()->prepare('DELETE FROM users WHERE id = ?');
        $stmt->execute([$id]);
    }

    public static function updateProfilePhoto(int $id, string $filename): void
    {
        $stmt = db()->prepare('UPDATE users SET profile_photo_path = ? WHERE id = ?');
        $stmt->execute([$filename, $id]);
    }

    public static function allWithEmployees(?array $viewer = null): array
    {
        $params = [];
        $scope = AccessScopeService::employeeScopeSql('e', $viewer, $params);
        $sql =
            'SELECT u.id, u.full_name, u.email, u.national_id, u.gender, u.role, u.phone, u.profile_photo_path, u.employment_document_path, u.status, u.created_at,
                    e.id AS employee_id, e.staff_id, e.department_id, e.designation, e.supervisor_id, e.employment_date,
                    d.directorate_id, d.name AS department_name,
                    dir.name AS directorate_name,
                    su.full_name AS supervisor_name
             FROM users u
             LEFT JOIN employees e ON e.user_id = u.id
             LEFT JOIN departments d ON d.id = e.department_id
             LEFT JOIN directorates dir ON dir.id = d.directorate_id
             LEFT JOIN employees se ON se.id = e.supervisor_id
             LEFT JOIN users su ON su.id = se.user_id
             WHERE 1 = 1'
            . $scope
            . ' ORDER BY u.created_at DESC';

        $stmt = db()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public static function updateAccess(int $id, string $role, string $status): void
    {
        $stmt = db()->prepare('UPDATE users SET role = ?, status = ? WHERE id = ?');
        $stmt->execute([$role, $status, $id]);
    }

    public static function updateStatus(int $id, string $status): void
    {
        $stmt = db()->prepare('UPDATE users SET status = ? WHERE id = ?');
        $stmt->execute([$status, $id]);
    }

    public static function rejectAccountRequest(int $id, string $reason): void
    {
        $stmt = db()->prepare(
            "UPDATE users
             SET status = 'rejected',
                 rejection_reason = ?
             WHERE id = ?"
        );
        $stmt->execute([$reason, $id]);
    }

    public static function pendingRegistrations(?array $viewer = null): array
    {
        $params = [];
        $scope = AccessScopeService::employeeScopeSql('e', $viewer, $params);
        $stmt = db()->prepare(
            "SELECT u.id, u.full_name, u.email, u.national_id, u.gender, u.phone, u.profile_photo_path, u.employment_document_path, u.role, u.status, u.created_at,
                    e.id AS employee_id, e.staff_id, e.designation, e.employment_date,
                    e.department_id,
                    d.directorate_id, d.name AS department_name,
                    dir.name AS directorate_name
             FROM users u
             JOIN employees e ON e.user_id = u.id
             LEFT JOIN departments d ON d.id = e.department_id
             LEFT JOIN directorates dir ON dir.id = d.directorate_id
             WHERE u.status = 'pending'
             $scope
             ORDER BY u.created_at ASC"
        );
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public static function countByRole(?string $role = null, ?array $viewer = null): int
    {
        $params = [];
        $scope = AccessScopeService::employeeScopeSql('e', $viewer, $params);
        $where = 'WHERE 1 = 1' . $scope;

        if ($role !== null) {
            $where .= ' AND u.role = ?';
            $params[] = $role;
        }

        $stmt = db()->prepare(
            "SELECT COUNT(*)
             FROM users u
             LEFT JOIN employees e ON e.user_id = u.id
             $where"
        );
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    public static function firstActiveByRole(string $role): ?array
    {
        $stmt = db()->prepare(
            "SELECT *
             FROM users
             WHERE role = ? AND status = 'active'
             ORDER BY created_at ASC, id ASC
             LIMIT 1"
        );
        $stmt->execute([$role]);
        $user = $stmt->fetch();

        return $user ?: null;
    }

    public static function normalizeGender(?string $gender): ?string
    {
        $gender = strtolower(trim((string) $gender));

        return array_key_exists($gender, gender_options()) ? $gender : null;
    }
}
