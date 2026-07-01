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
        $formState = [
            'leave_type_id' => (string) $leaveTypeId,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'contact_number' => $contactNumber,
            'reason' => $reason,
            'handover_notes' => $handoverNotes,
        ];

        $errors = [];

        if (!$leaveType || (int) $leaveType['is_active'] !== 1) {
            $errors['leave_type_id'] = 'Please select a valid leave type.';
        } elseif (!LeaveType::isEligibleForGender($leaveType, $user['gender'] ?? null)) {
            $errors['leave_type_id'] = 'This leave type is not available for your gender profile.';
        }

        if (!$this->validDate($startDate)) {
            $errors['start_date'] = 'Please provide a valid start date.';
        } elseif (!is_valid_today_or_future_date($startDate)) {
            $errors['start_date'] = 'Start date cannot be in the past.';
        }

        if (!$this->validDate($endDate)) {
            $errors['end_date'] = 'Please provide a valid end date.';
        } elseif (!is_valid_today_or_future_date($endDate)) {
            $errors['end_date'] = 'End date cannot be in the past.';
        }

        $phoneError = kenyan_phone_number_error($contactNumber, 'Contact number');
        if ($phoneError !== null) {
            $errors['contact_number'] = $phoneError;
        }

        if ($this->validDate($startDate) && $this->validDate($endDate) && strtotime($endDate) < strtotime($startDate)) {
            $errors['end_date'] = 'End date cannot be earlier than start date.';
        }

        $days = $errors ? 0 : LeaveBalanceService::businessDays($startDate, $endDate);
        if ($days < 1) {
            $errors['end_date'] = 'The selected dates do not include a working day.';
        }

        $balanceYear = $this->financialYearForDate($startDate);

        if (!$errors && !LeaveBalanceService::hasEnoughBalance((int) $employee['id'], $leaveTypeId, $days, $balanceYear)) {
            $errors['end_date'] = 'Insufficient leave balance for the selected leave type.';
        }

        $requiresAttachment = $leaveType
            && ((int) $leaveType['requires_attachment'] === 1
                || ($leaveType['attachment_after_days'] !== null && $days >= (float) $leaveType['attachment_after_days']));

        $attachmentPath = null;
        if (!$errors) {
            $attachmentPath = $this->handleUpload($requiresAttachment, $errors);
        }

        if ($errors) {
            remember_form_state($formState, $errors);
            set_flash('error', 'Please correct the highlighted fields below.');
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
            remember_form_state($formState, $errors);
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
        $formState = [
            'leave_type_id' => (string) $leaveTypeId,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'contact_number' => $contactNumber,
            'reason' => $reason,
            'handover_notes' => $handoverNotes,
        ];

        $errors = [];

        if (!$leaveType || (int) $leaveType['is_active'] !== 1) {
            $errors['leave_type_id'] = 'Please select a valid leave type.';
        } elseif (!LeaveType::isEligibleForGender($leaveType, $requestGender)) {
            $errors['leave_type_id'] = 'This leave type is not available for the staff gender profile.';
        }

        if (!$this->validDate($startDate)) {
            $errors['start_date'] = 'Please provide a valid start date.';
        }

        if (!$this->validDate($endDate)) {
            $errors['end_date'] = 'Please provide a valid end date.';
        }

        $phoneError = kenyan_phone_number_error($contactNumber, 'Contact number');
        if ($phoneError !== null) {
            $errors['contact_number'] = $phoneError;
        }

        if ($this->validDate($startDate) && $this->validDate($endDate) && strtotime($endDate) < strtotime($startDate)) {
            $errors['end_date'] = 'End date cannot be earlier than start date.';
        }

        $days = $errors ? 0 : LeaveBalanceService::businessDays($startDate, $endDate);
        if ($days < 1) {
            $errors['end_date'] = 'The selected dates do not include a working day.';
        }

        $balanceYear = $this->financialYearForDate($startDate);

        if (!$errors && !LeaveBalanceService::hasEnoughBalance((int) $request['employee_id'], $leaveTypeId, $days, $balanceYear)) {
            $errors['end_date'] = 'Insufficient leave balance for the selected leave type.';
        }

        $requiresAttachment = $leaveType
            && ((int) $leaveType['requires_attachment'] === 1
                || ($leaveType['attachment_after_days'] !== null && $days >= (float) $leaveType['attachment_after_days']));

        $attachmentPath = $request['attachment_path'] ?: null;
        if (!$errors) {
            $attachmentPath = $this->handleUpload($requiresAttachment, $errors, $attachmentPath);
        }

        if ($errors) {
            remember_form_state($formState, $errors);
            set_flash('error', 'Please correct the highlighted fields below.');
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
            remember_form_state($formState, $errors);
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
        $balances = $employee ? array_values(array_filter(
            LeaveBalanceService::balancesForEmployee((int) $employee['id']),
            fn (array $balance): bool => LeaveType::isEligibleForGender($balance, $user['gender'] ?? null)
        )) : [];

        view('leave/history', [
            'title' => 'Leave History',
            'employee' => $employee,
            'requests' => $employee ? LeaveRequest::forEmployee((int) $employee['id']) : [],
            'balances' => $balances,
            'financialYearLabel' => financial_year_label(),
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
            'canForfeit' => $this->canForfeit($request),
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
        $actor = current_user();
        $selfReported = $request && (int) $request['employee_user_id'] === (int) $actor['id'];

        if (!$request || !$this->canView($request)) {
            set_flash('error', 'Leave request could not be found or accessed.');
            redirect('leave/history');
        }

        if (!$selfReported && !$this->canMarkResumed($request)) {
            set_flash('error', 'This leave request cannot be marked as reported back yet.');
            header('Location: ' . url('leave/view') . '&id=' . $id);
            exit;
        }

        if ($request['status'] !== 'approved' || !empty($request['resumed_at'])) {
            set_flash('error', 'This leave request has already been reported back or is not yet approved.');
            if ($selfReported) {
                redirect('dashboard');
            }

            header('Location: ' . url('leave/view') . '&id=' . $id);
            exit;
        }

        if ($selfReported && strtotime(date('Y-m-d')) < strtotime((string) $request['start_date'])) {
            set_flash('error', 'You can only report back after your leave has started.');
            redirect('dashboard');
        }

        $resumptionNotes = $notes !== ''
            ? $notes
            : ($selfReported ? 'Self-reported from the dashboard.' : null);

        LeaveRequest::markResumed($id, (int) $actor['id'], $resumptionNotes);

        if ($selfReported) {
            AuditService::record('mark_leave_resumed_self', 'leave_requests', $id);
            $this->notifyReturnFromLeave($request, $actor);
            NotificationService::create(
                (int) $request['employee_user_id'],
                'Return from leave sent',
                'Your supervisor and HR have been notified that you are back from leave.',
                url('leave/view') . '&id=' . $id
            );

            set_flash('success', 'Your return from leave has been sent to your supervisor and HR.');
            redirect('dashboard');
        }

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

    public function forfeit(): void
    {
        require_role(['admin', 'hr']);
        verify_csrf();

        $id = (int) ($_POST['id'] ?? 0);
        $request = LeaveRequest::find($id);

        if (!$request || !$this->canView($request)) {
            set_flash('error', 'Only approved leave requests can be forfeited.');
            redirect('leave/history');
        }

        if ($request['status'] !== 'approved') {
            set_flash('error', 'Only approved leave requests can be forfeited.');
            header('Location: ' . url('leave/view') . '&id=' . $id);
            exit;
        }

        if (!empty($request['forfeiture_id'])) {
            set_flash('error', 'This leave request has already been marked as forfeited.');
            header('Location: ' . url('leave/view') . '&id=' . $id);
            exit;
        }

        if (!empty($request['resumed_at'])) {
            set_flash('error', 'This leave request has already been reported back and cannot be forfeited.');
            header('Location: ' . url('leave/view') . '&id=' . $id);
            exit;
        }

        $daysForfeited = (float) str_replace(',', '', trim((string) ($_POST['days_forfeited'] ?? '')));
        $payoutAmount = (float) str_replace(',', '', trim((string) ($_POST['payout_amount'] ?? '')));
        $notes = trim($_POST['notes'] ?? '');
        $approvedDays = (float) ($request['days_requested'] ?? 0);
        $errors = [];

        if ($daysForfeited <= 0) {
            $errors[] = 'Forfeited days must be greater than zero.';
        } elseif (abs($daysForfeited - $approvedDays) > 0.01) {
            $errors[] = 'Forfeited days must match the approved leave days.';
        }

        if ($payoutAmount <= 0) {
            $errors[] = 'Payout amount must be greater than zero.';
        }

        if ($errors) {
            set_flash('error', implode(' ', $errors));
            header('Location: ' . url('leave/view') . '&id=' . $id);
            exit;
        }

        $forfeitureData = [
            'leave_request_id' => $id,
            'days_forfeited' => $daysForfeited,
            'payout_amount' => $payoutAmount,
            'notes' => $notes !== '' ? $notes : null,
            'recorded_by_user_id' => (int) current_user()['id'],
        ];

        try {
            db()->beginTransaction();

            LeaveForfeiture::create($forfeitureData);
            LeaveRequest::updateStatus($id, 'forfeited');
            AuditService::record('record_leave_forfeiture', 'leave_requests', $id);

            db()->commit();
        } catch (Throwable $throwable) {
            db()->rollBack();
            app_log($throwable);
            set_flash('error', 'Leave forfeiture could not be recorded.');
            header('Location: ' . url('leave/view') . '&id=' . $id);
            exit;
        }

        $forfeiture = LeaveForfeiture::findByRequestId($id) ?? $forfeitureData;

        NotificationService::create(
            (int) $request['employee_user_id'],
            'Leave forfeiture payout recorded',
            'Your leave forfeiture payout of ' . format_currency($payoutAmount) . ' has been recorded for ' . $request['leave_type_name'] . '.',
            url('leave/view') . '&id=' . $id
        );

        $emailSent = ExternalNotificationService::leaveForfeitureRecorded($request, $forfeiture);
        $message = 'Leave forfeiture recorded for ' . $request['employee_name'] . '. Payout ' . format_currency($payoutAmount) . ' saved.';
        $message .= $emailSent
            ? ' Email sent to staff member.'
            : ' Email could not be sent to staff member.' . $this->emailFailureSuffix();

        set_flash('success', $message);
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

    private function financialYearForDate(string $date): int
    {
        return $this->validDate($date) ? financial_year_key($date) : financial_year_key();
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
                $errors['attachment'] = 'Supporting attachment is required for this leave request.';
            }

            return $existingAttachment;
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors['attachment'] = 'Attachment upload failed.';
            return null;
        }

        if ($file['size'] > app_config('max_upload_size')) {
            $errors['attachment'] = 'Attachment must not exceed 5 MB.';
            return null;
        }

        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, app_config('allowed_upload_extensions'), true) || !uploaded_file_is_pdf($file)) {
            $errors['attachment'] = 'Supporting attachment must be a PDF file.';
            return null;
        }

        $filename = date('YmdHis') . '-' . bin2hex(random_bytes(8)) . '.' . $extension;
        $destination = app_config('upload_dir') . '/' . $filename;

        if (!is_dir(app_config('upload_dir'))) {
            mkdir(app_config('upload_dir'), 0775, true);
        }

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            $errors['attachment'] = 'Could not save the attachment.';
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

    private function canForfeit(array $request): bool
    {
        $user = current_user();
        if (!$user || !in_array($user['role'], ['admin', 'hr'], true)) {
            return false;
        }

        return ($request['status'] ?? '') === 'approved'
            && empty($request['forfeiture_id'])
            && empty($request['resumed_at']);
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

    private function emailFailureSuffix(): string
    {
        $reason = trim(ExternalNotificationService::lastEmailError());

        return $reason !== ''
            ? ' Reason: ' . rtrim($reason, '.') . '.'
            : ' Check outbound notification logs.';
    }

    private function notifyReturnFromLeave(array $request, array $actor): void
    {
        $link = url('leave/view') . '&id=' . (int) $request['id'];
        $title = 'Staff reported back from leave';
        $message = $request['employee_name'] . ' has indicated they are back from '
            . ($request['leave_type_name'] ?? 'leave')
            . '. Leave period: '
            . format_date($request['start_date'])
            . ' to '
            . format_date($request['end_date'])
            . '. Report-back date: '
            . format_date(LeaveBalanceService::returnDateAfter((string) $request['end_date']))
            . '.';

        $notifiedSupervisor = false;
        if (!empty($request['supervisor_id'])) {
            $supervisor = Employee::find((int) $request['supervisor_id']);
            if ($supervisor && !empty($supervisor['user_id']) && (int) $supervisor['user_id'] !== (int) $actor['id']) {
                NotificationService::create((int) $supervisor['user_id'], $title, $message, $link);
                $notifiedSupervisor = true;
            }
        }

        if (!$notifiedSupervisor) {
            NotificationService::notifyRolesInEmployeeDepartment(['supervisor'], (int) $request['employee_id'], $title, $message, $link);
        }

        NotificationService::notifyRoles(['hr'], $title, $message, $link);
    }
}
