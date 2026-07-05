<?php

class AccessScopeService
{
    private const DEPARTMENT_SCOPED_ROLES = ['supervisor', 'director', 'chief_officer'];

    public static function isDepartmentScoped(array $user): bool
    {
        return in_array($user['role'] ?? '', self::DEPARTMENT_SCOPED_ROLES, true);
    }

    public static function employeeScopeSql(string $employeeAlias, ?array $viewer, array &$params): string
    {
        if (!$viewer || !self::isDepartmentScoped($viewer)) {
            return '';
        }

        $departmentId = self::departmentId($viewer);
        if (!$departmentId) {
            return ' AND 1 = 0';
        }

        $params[] = $departmentId;

        return " AND {$employeeAlias}.department_id = ?";
    }

    public static function canAccessUser(array $target, array $viewer): bool
    {
        if (in_array($viewer['role'] ?? '', ['admin', 'hr'], true)) {
            return true;
        }

        if ((int) ($target['id'] ?? 0) === (int) ($viewer['id'] ?? 0)) {
            return true;
        }

        if (!self::isDepartmentScoped($viewer)) {
            return false;
        }

        return self::sameDepartment($target, $viewer);
    }

    public static function canAccessLeaveRequest(array $request, array $viewer): bool
    {
        if ((int) ($request['employee_user_id'] ?? 0) === (int) ($viewer['id'] ?? 0)) {
            return true;
        }

        if (in_array($viewer['role'] ?? '', ['admin', 'hr'], true)) {
            return true;
        }

        if (!self::isDepartmentScoped($viewer)) {
            return false;
        }

        return self::sameDepartment($request, $viewer);
    }

    public static function sameDepartment(array $record, array $viewer): bool
    {
        $viewerDepartmentId = self::departmentId($viewer);
        $recordDepartmentId = self::departmentId($record);

        return $viewerDepartmentId !== null
            && $recordDepartmentId !== null
            && $viewerDepartmentId === $recordDepartmentId;
    }

    public static function forcedFilters(array $filters, array $viewer): array
    {
        if (!self::isDepartmentScoped($viewer)) {
            return $filters;
        }

        $filters['directorate_id'] = self::directorateId($viewer) ?? 0;
        $filters['department_id'] = self::departmentId($viewer) ?? 0;

        return $filters;
    }

    public static function departmentId(array $record): ?int
    {
        if (!empty($record['department_id'])) {
            return (int) $record['department_id'];
        }

        return null;
    }

    public static function directorateId(array $record): ?int
    {
        if (!empty($record['directorate_id'])) {
            return (int) $record['directorate_id'];
        }

        return null;
    }
}
