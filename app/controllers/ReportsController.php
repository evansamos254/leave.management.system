<?php

class ReportsController
{
    public function index(): void
    {
        require_role(['admin', 'hr', 'director', 'supervisor', 'chief_officer']);

        $user = current_user();
        $filters = $this->filtersFromRequest($user);
        $selectedDepartment = $filters['department_id'] ? Department::find($filters['department_id']) : null;
        $selectedDirectorate = $this->selectedDirectorate($filters, $selectedDepartment);
        $directorates = $this->visibleDirectorates($user);
        $departments = $this->visibleDepartments($user);

        view('reports/index', [
            'title' => 'Reports',
            'from' => $filters['from'],
            'to' => $filters['to'],
            'directorateId' => $filters['directorate_id'],
            'departmentId' => $filters['department_id'],
            'directorates' => $directorates,
            'departments' => $departments,
            'selectedDirectorate' => $selectedDirectorate,
            'selectedDepartment' => $selectedDepartment,
            'reportQuery' => http_build_query(array_filter([
                'from' => $filters['from'],
                'to' => $filters['to'],
                'directorate_id' => $filters['directorate_id'] ?: null,
                'department_id' => $filters['department_id'] ?: null,
            ], fn ($value) => $value !== null && $value !== '')),
            'summary' => LeaveRequest::reportSummary(
                $filters['from'] ?: null,
                $filters['to'] ?: null,
                $filters['directorate_id'] ?: null,
                $filters['department_id'] ?: null,
                $user
            ),
            'pending' => LeaveRequest::pendingForRole($user['role'], $user['employee_id'] ? (int) $user['employee_id'] : null, $user),
        ]);
    }

    public function csv(): void
    {
        require_role(['admin', 'hr', 'director', 'supervisor', 'chief_officer']);

        $user = current_user();
        $filters = $this->filtersFromRequest($user);
        $summary = LeaveRequest::reportSummary(
            $filters['from'] ?: null,
            $filters['to'] ?: null,
            $filters['directorate_id'] ?: null,
            $filters['department_id'] ?: null,
            $user
        );

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="leave-report.csv"');

        $output = fopen('php://output', 'w');
        fputcsv($output, ['Leave Type', 'Approved Requests', 'Total Days', 'Department', 'Directorate', 'From', 'To']);

        $department = $filters['department_id'] ? Department::find($filters['department_id']) : null;
        $directorate = $this->selectedDirectorate($filters, $department);

        foreach ($summary as $row) {
            fputcsv($output, [
                $row['leave_type_name'],
                $row['request_count'],
                format_days($row['total_days']),
                $directorate['name'] ?? 'All departments',
                $department['name'] ?? 'All directorates',
                $filters['from'] ?: 'All time',
                $filters['to'] ?: 'All time',
            ]);
        }

        fclose($output);
    }

    public function pdf(): void
    {
        require_role(['admin', 'hr', 'director', 'supervisor', 'chief_officer']);

        $user = current_user();
        $filters = $this->filtersFromRequest($user);
        $summary = LeaveRequest::reportSummary(
            $filters['from'] ?: null,
            $filters['to'] ?: null,
            $filters['directorate_id'] ?: null,
            $filters['department_id'] ?: null,
            $user
        );
        $requests = LeaveRequest::reportDetails(
            $filters['from'] ?: null,
            $filters['to'] ?: null,
            $filters['directorate_id'] ?: null,
            $filters['department_id'] ?: null,
            $user
        );
        $department = $filters['department_id'] ? Department::find($filters['department_id']) : null;
        $directorate = $this->selectedDirectorate($filters, $department);

        $pdf = PdfService::leaveReport([
            'from' => $filters['from'],
            'to' => $filters['to'],
            'directorate' => $directorate,
            'department' => $department,
            'generated_by' => $user['full_name'] ?? 'System user',
        ], $summary, $requests);

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="leave-report.pdf"');
        header('Content-Length: ' . strlen($pdf));
        echo $pdf;
    }

    private function filtersFromRequest(array $user): array
    {
        $from = trim($_GET['from'] ?? '');
        $to = trim($_GET['to'] ?? '');
        $directorateId = (int) ($_GET['directorate_id'] ?? 0);
        $departmentId = (int) ($_GET['department_id'] ?? 0);

        if ($from !== '' && strtotime($from) === false) {
            $from = '';
        }

        if ($to !== '' && strtotime($to) === false) {
            $to = '';
        }

        if ($directorateId > 0 && !Directorate::find($directorateId)) {
            $directorateId = 0;
        }

        if ($departmentId > 0 && !Department::find($departmentId)) {
            $departmentId = 0;
        }

        if ($directorateId > 0 && $departmentId > 0 && !Department::belongsToDirectorate($departmentId, $directorateId)) {
            $departmentId = 0;
        }

        return AccessScopeService::forcedFilters([
            'from' => $from,
            'to' => $to,
            'directorate_id' => $directorateId,
            'department_id' => $departmentId,
        ], $user);
    }

    private function selectedDirectorate(array $filters, ?array $department): ?array
    {
        if ($filters['directorate_id']) {
            return Directorate::find($filters['directorate_id']);
        }

        if ($department && !empty($department['directorate_id'])) {
            return [
                'id' => $department['directorate_id'],
                'name' => $department['directorate_name'] ?? 'Selected department',
            ];
        }

        return null;
    }

    private function visibleDirectorates(array $user): array
    {
        if (!AccessScopeService::isDepartmentScoped($user)) {
            return Directorate::all();
        }

        $directorateId = AccessScopeService::directorateId($user);
        if (!$directorateId) {
            return [];
        }

        $directorate = Directorate::find($directorateId);

        return $directorate ? [$directorate] : [];
    }

    private function visibleDepartments(array $user): array
    {
        if (!AccessScopeService::isDepartmentScoped($user)) {
            return Department::all();
        }

        $departmentId = AccessScopeService::departmentId($user);
        if (!$departmentId) {
            return [];
        }

        $department = Department::find($departmentId);

        return $department ? [$department] : [];
    }
}
