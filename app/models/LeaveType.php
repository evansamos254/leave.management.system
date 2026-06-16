<?php

class LeaveType
{
    public static function active(): array
    {
        $stmt = db()->query('SELECT * FROM leave_types WHERE is_active = 1 ORDER BY name');

        return $stmt->fetchAll();
    }

    public static function activeForGender(?string $gender): array
    {
        $gender = User::normalizeGender($gender);
        $eligibilities = ['any'];

        if (in_array($gender, ['male', 'female'], true)) {
            $eligibilities[] = $gender;
        }

        $placeholders = implode(',', array_fill(0, count($eligibilities), '?'));
        $stmt = db()->prepare(
            "SELECT * FROM leave_types
             WHERE is_active = 1 AND gender_eligibility IN ($placeholders)
             ORDER BY name"
        );
        $stmt->execute($eligibilities);

        return $stmt->fetchAll();
    }

    public static function all(): array
    {
        $stmt = db()->query('SELECT * FROM leave_types ORDER BY name');

        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = db()->prepare('SELECT * FROM leave_types WHERE id = ?');
        $stmt->execute([$id]);
        $type = $stmt->fetch();

        return $type ?: null;
    }

    public static function save(array $data): void
    {
        if (!empty($data['id'])) {
            $stmt = db()->prepare(
                'UPDATE leave_types
                 SET name = :name,
                     default_entitlement = :default_entitlement,
                     gender_eligibility = :gender_eligibility,
                     requires_balance = :requires_balance,
                     requires_attachment = :requires_attachment,
                     attachment_after_days = :attachment_after_days,
                     is_paid = :is_paid,
                     is_active = :is_active
                 WHERE id = :id'
            );
            $stmt->execute($data);
            return;
        }

        unset($data['id']);
        $stmt = db()->prepare(
            'INSERT INTO leave_types
             (name, gender_eligibility, default_entitlement, requires_balance, requires_attachment, attachment_after_days, is_paid, is_active)
             VALUES
             (:name, :gender_eligibility, :default_entitlement, :requires_balance, :requires_attachment, :attachment_after_days, :is_paid, :is_active)'
        );
        $stmt->execute($data);
    }

    public static function isEligibleForGender(array $type, ?string $gender): bool
    {
        $eligibility = self::normalizeEligibility($type['gender_eligibility'] ?? 'any');
        if ($eligibility === 'any') {
            return true;
        }

        return User::normalizeGender($gender) === $eligibility;
    }

    public static function normalizeEligibility(?string $eligibility): string
    {
        $eligibility = strtolower(trim((string) $eligibility));

        return array_key_exists($eligibility, leave_gender_options()) ? $eligibility : 'any';
    }
}
