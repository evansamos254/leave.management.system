<?php

class ReportsController
{
    public function index(): void
    {
        require_role(['admin', 'hr', 'director', 'supervisor']);

        $filters = $this->filtersFromRequest();
        $selectedDepartment = $filters['department_id'] ? Department::find($filters['department_id']) : null;
        $selectedDirectorate = $this->selectedDirectorate($filters, $selectedDepartment);

        view('reports/index', [
            'title' => 'Reports',
            'from' => $filters['from'],
            'to' => $filters['to'],
            'directorateId' => $filters['directorate_id'],
            'departmentId' => $filters['department_id'],
            'directorates' => Directorate::all(),
            'departments' => Department::all(),
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
                $filters['department_id'] ?: null
            ),
            'pending' => LeaveRequest::pendingForRole(current_user()['role'], current_user()['employee_id'] ? (int) current_user()['employee_id'] : null),
        ]);
    }

    public function csv(): void
    {
        require_role(['admin', 'hr', 'director', 'supervisor']);

        $filters = $this->filtersFromRequest();
        $summary = LeaveRequest::reportSummary(
            $filters['from'] ?: null,
            $filters['to'] ?: null,
            $filters['directorate_id'] ?: null,
            $filters['department_id'] ?: null
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
        require_role(['admin', 'hr', 'director', 'supervisor']);

        $filters = $this->filtersFromRequest();
        $summary = LeaveRequest::reportSummary(
            $filters['from'] ?: null,
            $filters['to'] ?: null,
            $filters['directorate_id'] ?: null,
            $filters['department_id'] ?: null
        );
        $requests = LeaveRequest::reportDetails(
            $filters['from'] ?: null,
            $filters['to'] ?: null,
            $filters['directorate_id'] ?: null,
            $filters['department_id'] ?: null
        );
        $department = $filters['department_id'] ? Department::find($filters['department_id']) : null;
        $directorate = $this->selectedDirectorate($filters, $department);

        $pdf = PdfService::leaveReport([
            'from' => $filters['from'],
            'to' => $filters['to'],
            'directorate' => $directorate,
            'department' => $department,
            'generated_by' => current_user()['full_name'] ?? 'System user',
        ], $summary, $requests);

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="leave-report.pdf"');
        header('Content-Length: ' . strlen($pdf));
        echo $pdf;
    }

    private function filtersFromRequest(): array
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

        return [
            'from' => $from,
            'to' => $to,
            'directorate_id' => $directorateId,
            'department_id' => $departmentId,
        ];
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
}
