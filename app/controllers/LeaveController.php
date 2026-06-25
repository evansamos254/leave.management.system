<?php

class LeaveController
{
    public function apply(): void
    {
        require_auth();

        $user = current_user();
        $employee = Employee::findByUserId((int) $user['id']);

        if (!$employee) {
            set_flash('error', 'Only registered staff accounts can apply for leave.');
            redirect('dashboard');
        }

        $activeRequest = LeaveRequest::activeForEmployee((int) $employee['id']);
        if ($activeRequest) {
            set_flash('error', $this->activeLeaveMessage($activeRequest));
            redirect('leave/history');
        }

        $leaveTypes = LeaveType::activeForGender($user['gender'] ?? null);
        $balances = $this->balancesForGender(
            LeaveBalanceService::balancesForEmployee((int) $employee['id']),
            $user['gender'] ?? null
        );

        view('leave/apply', [
            'title' => 'Apply for Leave',
            'user' => $user,
            'employee' => $employee,
            'leaveTypes' => $leaveTypes,
            'balances' => $balances,
            'leavePlanner' => $this->leavePlannerData($leaveTypes, $balances),
        ]);
    }

    public function store(): void
    {
        require_auth();
        verify_csrf();

        $user = current_user();
        $employee = Employee::findByUserId((int) $user['id']);

        if (!$employee) {
            set_flash('error', 'Only staff accounts can submit leave requests.');
            redirect('dashboard');
        }

        $activeRequest = LeaveRequest::activeForEmployee((int) $employee['id']);
        if ($activeRequest) {
            set_flash('error', $this->activeLeaveMessage($activeRequest));
            redirect('leave/history');
        }

        $leaveTypeId = (int) ($_POST['leave_type_id'] ?? 0);
        $leaveType = LeaveType::find($leaveTypeId);
        $startDate = trim($_POST['start_date'] ?? '');
        $endDate = trim($_POST['end_date'] ?? '');
        $contactNumber = trim($_POST['contact_number'] ?? '');
        $reason = trim($_POST['reason'] ?? '');
        $handoverNotes = trim($_POST['handover_notes'] ?? '');

        $errors = [];

        if (!$leaveType || (int) $leaveType['is_active'] !== 1) {
            $errors[] = 'Please select a valid leave type.';
        } elseif (!LeaveType::isEligibleForGender($leaveType, $user['gender'] ?? null)) {
            $errors[] = 'This leave type is not available for your gender profile.';
        }

        if (!$this->validDate($startDate) || !$this->validDate($endDate)) {
            $errors[] = 'Please provide valid start and end dates.';
        }

        if ($this->validDate($startDate) && $this->validDate($endDate) && strtotime($endDate) < strtotime($startDate)) {
            $errors[] = 'End date cannot be earlier than start date.';
        }

        $days = $errors ? 0 : LeaveBalanceService::businessDays($startDate, $endDate);
        if ($days < 1) {
            $errors[] = 'The selected dates do not include a working day.';
        }

        if (!$errors && !LeaveBalanceService::hasEnoughBalance((int) $employee['id'], $leaveTypeId, $days)) {
            $errors[] = 'Insufficient leave balance for the selected leave type.';
        }

        $requiresAttachment = $leaveType
            && ((int) $leaveType['requires_attachment'] === 1
                || ($leaveType['attachment_after_days'] !== null && $days >= (float) $leaveType['attachment_after_days']));

        $attachmentPath = null;
        if (!$errors) {
            $attachmentPath = $this->handleUpload($requiresAttachment, $errors);
        }

        if ($errors) {
            set_flash('error', implode(' ', $errors));
            redirect('leave/apply');
        }

        try {
            db()->beginTransaction();

            $leaveRequestId = LeaveRequest::create([
                'employee_id' => (int) $employee['id'],
                'leave_type_id' => $leaveTypeId,
                'contact_number' => $contactNumber,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'days_requested' => $days,
                'reason' => $reason,
                'handover_notes' => $handoverNotes,
                'attachment_path' => $attachmentPath,
            ]);

            ApprovalWorkflowService::createSteps($leaveRequestId);
            AuditService::record('create_leave_request', 'leave_requests', $leaveRequestId);

            db()->commit();
        } catch (Throwable $throwable) {
            db()->rollBack();
            app_log($throwable);
            set_flash('error', 'Leave request could not be submitted.');
            redirect('leave/apply');
        }

        $message = $user['full_name'] . ' submitted a leave request for ' . $days . ' working day(s).';
        $submittedRequest = LeaveRequest::find($leaveRequestId);
        if ($submittedRequest) {
            ExternalNotificationService::leaveRequestSubmitted($submittedRequest, 'supervisor');
        }

        if (!empty($employee['supervisor_id'])) {
            $supervisor = Employee::find((int) $employee['supervisor_id']);
            if ($supervisor) {
                NotificationService::create((int) $supervisor['user_id'], 'Leave request awaiting review', $message, url('approvals'));
            }
        } else {
            NotificationService::notifyRolesInEmployeeDepartment(['supervisor'], (int) $employee['id'], 'Leave request awaiting review', $message, url('approvals'));
        }

        set_flash('success', 'Leave request submitted successfully.');
        redirect('leave/history');
    }

    public function edit(): void
    {
        require_auth();

        $id = (int) ($_GET['id'] ?? 0);
        $request = LeaveRequest::find($id);

        if (!$request || !$this->canEdit($request)) {
            set_flash('error', 'This leave request cannot be edited because approval has already started or you cannot access it.');
            redirect('leave/history');
        }

        $employee = Employee::find((int) $request['employee_id']);
        $user = User::find((int) $request['employee_user_id']) ?? current_user();
        $gender = $user['gender'] ?? null;

        $leaveTypes = LeaveType::activeForGender($gender);
        $balances = $this->balancesForGender(
            LeaveBalanceService::balancesForEmployee((int) $request['employee_id']),
            $gender
        );

        view('leave/edit', [
            'title' => 'Edit Leave Request',
            'request' => $request,
            'user' => $user,
            'employee' => $employee,
            'leaveTypes' => $leaveTypes,
            'balances' => $balances,
            'leavePlanner' => $this->leavePlannerData($leaveTypes, $balances),
            'editingAsSupervisor' => $this->isSupervisorEditor($request, current_user()),
        ]);
    }

    public function update(): void
    {
        require_auth();
        verify_csrf();

        $id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
        $request = LeaveRequest::find($id);
        $user = current_user();
        $requestUser = $request ? User::find((int) $request['employee_user_id']) : null;
        $requestGender = $requestUser['gender'] ?? null;

        if (!$request || !$this->canEdit($request)) {
            set_flash('error', 'This leave request cannot be edited because approval has already started or you cannot access it.');
            redirect('leave/history');
        }

        $leaveTypeId = (int) ($_POST['leave_type_id'] ?? 0);
        $leaveType = LeaveType::find($leaveTypeId);
        $startDate = trim($_POST['start_date'] ?? '');
        $endDate = trim($_POST['end_date'] ?? '');
        $contactNumber = trim($_POST['contact_number'] ?? '');
        $reason = trim($_POST['reason'] ?? '');
        $handoverNotes = trim($_POST['handover_notes'] ?? '');

        $errors = [];

        if (!$leaveType || (int) $leaveType['is_active'] !== 1) {
            $errors[] = 'Please select a valid leave type.';
        } elseif (!LeaveType::isEligibleForGender($leaveType, $requestGender)) {
            $errors[] = 'This leave type is not available for the staff gender profile.';
        }

        if (!$this->validDate($startDate) || !$this->validDate($endDate)) {
            $errors[] = 'Please provide valid start and end dates.';
        }

        if ($this->validDate($startDate) && $this->validDate($endDate) && strtotime($endDate) < strtotime($startDate)) {
            $errors[] = 'End date cannot be earlier than start date.';
        }

        $days = $errors ? 0 : LeaveBalanceService::businessDays($startDate, $endDate);
        if ($days < 1) {
            $errors[] = 'The selected dates do not include a working day.';
        }

        if (!$errors && !LeaveBalanceService::hasEnoughBalance((int) $request['employee_id'], $leaveTypeId, $days)) {
            $errors[] = 'Insufficient leave balance for the selected leave type.';
        }

        $requiresAttachment = $leaveType
            && ((int) $leaveType['requires_attachment'] === 1
                || ($leaveType['attachment_after_days'] !== null && $days >= (float) $leaveType['attachment_after_days']));

        $attachmentPath = $request['attachment_path'] ?: null;
        if (!$errors) {
            $attachmentPath = $this->handleUpload($requiresAttachment, $errors, $attachmentPath);
        }

        if ($errors) {
            set_flash('error', implode(' ', $errors));
            $this->redirectToLeaveEdit($id);
        }

        try {
            db()->beginTransaction();

            LeaveRequest::update($id, [
                'leave_type_id' => $leaveTypeId,
                'contact_number' => $contactNumber,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'days_requested' => $days,
                'reason' => $reason,
                'handover_notes' => $handoverNotes,
                'attachment_path' => $attachmentPath,
            ]);

            AuditService::record('update_leave_request', 'leave_requests', $id);
            db()->commit();
        } catch (Throwable $throwable) {
            db()->rollBack();
            app_log($throwable);
            set_flash('error', 'Leave request could not be updated.');
            $this->redirectToLeaveEdit($id);
        }

        if ($attachmentPath && $request['attachment_path'] && $attachmentPath !== $request['attachment_path']) {
            $oldPath = app_config('upload_dir') . '/' . basename($request['attachment_path']);
            if (is_file($oldPath)) {
                @unlink($oldPath);
            }
        }

        if ($this->isSupervisorEditor($request, $user)) {
            NotificationService::create(
                (int) $request['employee_user_id'],
                'Leave request updated by supervisor',
                $user['full_name'] . ' updated your pending leave request before approval.',
                url('leave/view') . '&id=' . $id
            );
            set_flash('success', 'Leave request updated. You can now approve or reject it.');
            header('Location: ' . url('leave/view') . '&id=' . $id);
            exit;
        } else {
            $message = $user['full_name'] . ' updated a pending leave request.';
            if (!empty($request['supervisor_id'])) {
                $supervisor = Employee::find((int) $request['supervisor_id']);
                if ($supervisor) {
                    NotificationService::create((int) $supervisor['user_id'], 'Leave request updated', $message, url('approvals'));
                }
            } else {
                NotificationService::notifyRolesInEmployeeDepartment(['supervisor'], (int) $request['employee_id'], 'Leave request updated', $message, url('approvals'));
            }
        }

        set_flash('success', 'Leave request updated successfully.');
        redirect('leave/history');
    }

    public function history(): void
    {
        require_auth();

        $user = current_user();
        $employee = Employee::findByUserId((int) $user['id']);

        view('leave/history', [
            'title' => 'Leave History',
            'employee' => $employee,
            'requests' => $employee ? LeaveRequest::forEmployee((int) $employee['id']) : [],
        ]);
    }

    public function view(): void
    {
        require_auth();

        $id = (int) ($_GET['id'] ?? 0);
        $request = LeaveRequest::find($id);

        if (!$request || !$this->canView($request)) {
            http_response_code(404);
            view('error', [
                'title' => 'Leave request not found',
                'message' => 'The leave request could not be found or you cannot access it.',
            ]);
            return;
        }

        view('leave/view', [
            'title' => 'Leave Request',
            'request' => $request,
            'steps' => ApprovalWorkflowService::steps($id),
            'canEdit' => $this->canEdit($request),
            'canApprove' => $this->canApprove($request),
            'canMarkResumed' => $this->canMarkResumed($request),
            'reportBackDate' => LeaveBalanceService::returnDateAfter($request['end_date']),
        ]);
    }

    public function pdf(): void
    {
        require_auth();

        $id = (int) ($_GET['id'] ?? 0);
        $request = LeaveRequest::find($id);

        if (!$request || !$this->canView($request)) {
            http_response_code(404);
            echo 'Leave request not found.';
            return;
        }

        $pdf = PdfService::leaveRequest($request, ApprovalWorkflowService::steps($id));
        $filename = 'leave-request-' . $id . '.pdf';

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($pdf));
        echo $pdf;
    }

    public function cancel(): void
    {
        require_auth();
        verify_csrf();

        $id = (int) ($_POST['id'] ?? 0);
        $request = LeaveRequest::find($id);
        $user = current_user();

        if (!$request || (int) $request['employee_user_id'] !== (int) $user['id'] || !str_starts_with($request['status'], 'pending_')) {
            set_flash('error', 'This leave request cannot be cancelled.');
            redirect('leave/history');
        }

        LeaveRequest::updateStatus($id, 'cancelled');
        AuditService::record('cancel_leave_request', 'leave_requests', $id);
        set_flash('success', 'Leave request cancelled.');
        redirect('leave/history');
    }

    public function markResumed(): void
    {
        require_auth();
        verify_csrf();

        $id = (int) ($_POST['id'] ?? 0);
        $request = LeaveRequest::find($id);
        $notes = trim($_POST['resumption_notes'] ?? '');

        if (!$request || !$this->canView($request)) {
            set_flash('error', 'Leave request could not be found or accessed.');
            redirect('leave/history');
        }

        if (!$this->canMarkResumed($request)) {
            set_flash('error', 'This leave request cannot be marked as reported back yet.');
            header('Location: ' . url('leave/view') . '&id=' . $id);
            exit;
        }

        LeaveRequest::markResumed($id, (int) current_user()['id'], $notes !== '' ? $notes : null);
        AuditService::record('mark_leave_resumed', 'leave_requests', $id);
        NotificationService::create(
            (int) $request['employee_user_id'],
            'Return from leave confirmed',
            'Your return from leave has been recorded.',
            url('leave/view') . '&id=' . $id
        );

        set_flash('success', 'Staff return from leave recorded.');
        header('Location: ' . url('leave/view') . '&id=' . $id);
        exit;
    }

    public function attachment(): void
    {
        require_auth();

        $id = (int) ($_GET['id'] ?? 0);
        $request = LeaveRequest::find($id);

        if (!$request || !$request['attachment_path'] || !$this->canView($request)) {
            http_response_code(404);
            echo 'Attachment not found.';
            return;
        }

        $file = app_config('upload_dir') . '/' . basename($request['attachment_path']);
        if (!is_file($file)) {
            http_response_code(404);
            echo 'Attachment file missing.';
            return;
        }

        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($file) . '"');
        readfile($file);
    }

    private function validDate(string $date): bool
    {
        $parsed = DateTime::createFromFormat('Y-m-d', $date);
        return $parsed && $parsed->format('Y-m-d') === $date;
    }

    private function leavePlannerData(array $leaveTypes, array $balances): array
    {
        $balancesByType = [];
        foreach ($balances as $balance) {
            $balancesByType[(int) $balance['leave_type_id']] = $balance;
        }

        $types = [];
        foreach ($leaveTypes as $type) {
            $balance = $balancesByType[(int) $type['id']] ?? null;
            $tracksBalance = LeaveType::isBalanceTracked($type);
            $types[] = [
                'id' => (int) $type['id'],
                'name' => $type['name'],
                'genderEligibility' => $type['gender_eligibility'] ?? 'any',
                'defaultEntitlement' => (float) $type['default_entitlement'],
                'requiresBalance' => $tracksBalance,
                'requiresAttachment' => (int) $type['requires_attachment'] === 1,
                'attachmentAfterDays' => $type['attachment_after_days'] !== null ? (float) $type['attachment_after_days'] : null,
                'isPaid' => (int) $type['is_paid'] === 1,
                'availableDays' => $tracksBalance && $balance ? (float) $balance['available_days'] : null,
            ];
        }

        return [
            'leaveTypes' => $types,
            'holidays' => LeaveBalanceService::publicHolidays(),
        ];
    }

    private function balancesForGender(array $balances, ?string $gender): array
    {
        return array_values(array_filter(
            $balances,
            fn (array $balance): bool => LeaveType::isEligibleForGender($balance, $gender)
        ));
    }

    private function handleUpload(bool $required, array &$errors, ?string $existingAttachment = null): ?string
    {
        $file = $_FILES['attachment'] ?? null;

        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            if ($required && !$existingAttachment) {
                $errors[] = 'Supporting attachment is required for this leave request.';
            }

            return $existingAttachment;
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Attachment upload failed.';
            return null;
        }

        if ($file['size'] > app_config('max_upload_size')) {
            $errors[] = 'Attachment must not exceed 5 MB.';
            return null;
        }

        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, app_config('allowed_upload_extensions'), true) || !uploaded_file_is_pdf($file)) {
            $errors[] = 'Supporting attachment must be a PDF file.';
            return null;
        }

        $filename = date('YmdHis') . '-' . bin2hex(random_bytes(8)) . '.' . $extension;
        $destination = app_config('upload_dir') . '/' . $filename;

        if (!is_dir(app_config('upload_dir'))) {
            mkdir(app_config('upload_dir'), 0775, true);
        }

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            $errors[] = 'Could not save the attachment.';
            return null;
        }

        return $filename;
    }

    private function canEdit(array $request): bool
    {
        $user = current_user();
        if (!$user) {
            return false;
        }

        if ($request['status'] !== 'pending_supervisor') {
            return false;
        }

        if ((int) $request['employee_user_id'] === (int) $user['id']) {
            return !ApprovalWorkflowService::hasStarted((int) $request['id']);
        }

        return $this->isSupervisorEditor($request, $user);
    }

    private function canView(array $request): bool
    {
        $user = current_user();
        if (!$user) {
            return false;
        }

        if ((int) $request['employee_user_id'] === (int) $user['id']) {
            return true;
        }

        return AccessScopeService::canAccessLeaveRequest($request, $user);
    }

    private function canApprove(array $request): bool
    {
        $user = current_user();

        return $this->isSupervisorEditor($request, $user);
    }

    private function isSupervisorEditor(array $request, ?array $user): bool
    {
        if (!$user || ($user['role'] ?? '') !== 'supervisor') {
            return false;
        }

        if (($request['status'] ?? '') !== 'pending_supervisor') {
            return false;
        }

        if (!AccessScopeService::canAccessLeaveRequest($request, $user)) {
            return false;
        }

        if (empty($request['supervisor_id'])) {
            return true;
        }

        return !empty($user['employee_id']) && (int) $request['supervisor_id'] === (int) $user['employee_id'];
    }

    private function canMarkResumed(array $request): bool
    {
        $user = current_user();
        if (!$user || !in_array($user['role'], ['admin', 'supervisor', 'hr'], true)) {
            return false;
        }

        if ($request['status'] !== 'approved' || !empty($request['resumed_at'])) {
            return false;
        }

        if ($user['role'] === 'supervisor') {
            if (!AccessScopeService::canAccessLeaveRequest($request, $user)) {
                return false;
            }

            $supervisorEmployeeId = (int) ($user['employee_id'] ?? 0);
            if (!empty($request['supervisor_id']) && (int) $request['supervisor_id'] !== $supervisorEmployeeId) {
                return false;
            }
        }

        $reportBackDate = LeaveBalanceService::returnDateAfter($request['end_date']);

        return strtotime(date('Y-m-d')) >= strtotime($reportBackDate);
    }

    private function activeLeaveMessage(array $request): string
    {
        return 'You already have an active leave request for '
            . $request['leave_type_name']
            . ' from '
            . format_date($request['start_date'])
            . ' to '
            . format_date($request['end_date'])
            . ' ('
            . status_label($request['status'])
            . '). You cannot apply for another leave until the current request is completed, cancelled, rejected, or the leave has ended.';
    }

    private function redirectToLeaveEdit(int $id): never
    {
        header('Location: ' . url('leave/edit') . '&id=' . $id);
        exit;
    }
}
