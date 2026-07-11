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
        $passportPhotoPath = null;
        if (!$errors) {
            $attachmentPath = $this->handleUpload($requiresAttachment, $errors);
            $passportPhotoPath = $this->handlePassportPhotoUpload($errors);
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
                'passport_photo_path' => $passportPhotoPath,
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
            if (!empty($employee['supervisor_id'])) {
                $supervisor = Employee::find((int) $employee['supervisor_id']);
                if ($supervisor && !empty($supervisor['email'])) {
                    ExternalNotificationService::leaveRequestSubmittedToSupervisor($submittedRequest, $supervisor);
                }
            } elseif (!empty($employee['department_id'])) {
                foreach (Employee::supervisorsInDepartment((int) $employee['department_id']) as $supervisor) {
                    if (!empty($supervisor['email'])) {
                        ExternalNotificationService::leaveRequestSubmittedToSupervisor($submittedRequest, $supervisor);
                    }
                }
            }
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
        $passportPhotoPath = $request['passport_photo_path'] ?: null;
        if (!$errors) {
            $attachmentPath = $this->handleUpload($requiresAttachment, $errors, $attachmentPath);
            $passportPhotoPath = $this->handlePassportPhotoUpload($errors, $passportPhotoPath);
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
                'passport_photo_path' => $passportPhotoPath,
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

        if ($passportPhotoPath && $request['passport_photo_path'] && $passportPhotoPath !== $request['passport_photo_path']) {
            $oldPath = app_config('leave_passport_photo_dir') . '/' . basename($request['passport_photo_path']);
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

        $recalledByName = null;
        if (has_official_leave_recall($request) && !empty($request['recalled_by_name'])) {
            $recalledByName = $request['recalled_by_name'];
        } elseif (has_official_leave_recall($request) && !empty($request['recalled_by_user_id'])) {
            $recalledBy = User::find((int) $request['recalled_by_user_id']);
            $recalledByName = $recalledBy['full_name'] ?? null;
        }

        view('leave/view', [
            'title' => 'Leave Request',
            'request' => $request,
            'steps' => ApprovalWorkflowService::steps($id),
            'canEdit' => $this->canEdit($request),
            'canForfeit' => $this->canForfeit($request),
            'canApprove' => $this->canApprove($request),
            'canMarkResumed' => $this->canMarkResumed($request),
            'canRecall' => $this->canRecall($request),
            'recalledByName' => $recalledByName,
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

        if (has_official_leave_recall($request)) {
            set_flash('error', 'This leave request has already been officially recalled by the supervisor.');
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
        $resumptionDate = date('Y-m-d');
        $carryoverDays = 0.0;

        try {
            db()->beginTransaction();

            LeaveRequest::markResumed($id, (int) $actor['id'], $resumptionNotes);
            $carryoverDays = $this->restoreUnusedLeaveBalance($request, $resumptionDate);

            AuditService::record(
                $selfReported ? 'mark_leave_resumed_self' : 'mark_leave_resumed',
                'leave_requests',
                $id
            );

            db()->commit();
        } catch (Throwable $throwable) {
            if (db()->inTransaction()) {
                db()->rollBack();
            }
            app_log($throwable);
            set_flash('error', 'Leave request could not be updated.');
            if ($selfReported) {
                redirect('dashboard');
            }

            header('Location: ' . url('leave/view') . '&id=' . $id);
            exit;
        }

        if ($selfReported) {
            $this->notifyReturnFromLeave($request, $actor, $carryoverDays);
            $carryoverMessage = $carryoverDays > 0
                ? ' ' . format_days($carryoverDays) . ' working day(s) were carried back to your leave balance.'
                : '';
            NotificationService::create(
                (int) $request['employee_user_id'],
                'Return from leave sent',
                'Your supervisor and HR have been notified that you are back from leave.' . $carryoverMessage,
                url('leave/view') . '&id=' . $id
            );

            set_flash(
                'success',
                'Your return from leave has been sent to your supervisor and HR.'
                . $carryoverMessage
            );
            redirect('dashboard');
        }

        $carryoverMessage = $carryoverDays > 0
            ? ' ' . format_days($carryoverDays) . ' working day(s) were carried back to the staff balance.'
            : '';
        NotificationService::create(
            (int) $request['employee_user_id'],
            'Return from leave confirmed',
            'Your return from leave has been recorded.' . $carryoverMessage,
            url('leave/view') . '&id=' . $id
        );

        set_flash('success', 'Staff return from leave recorded.' . $carryoverMessage);
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

    public function recall(): void
    {
        require_auth();
        verify_csrf();

        $id = (int) ($_POST['id'] ?? 0);
        $request = LeaveRequest::find($id);
        $actor = current_user();

        if (!$request || !$this->canRecall($request)) {
            set_flash('error', 'This leave request cannot be recalled.');
            redirect('leave/history');
        }

        $reason = trim($_POST['recall_reason'] ?? '');
        $errors = [];

        if ($reason === '') {
            $errors['recall_reason'] = 'Recall reason is required.';
        }

        $this->ensureRecallSchema();
        $recallAttachmentPath = null;
        if (!$errors) {
            $recallAttachmentPath = $this->handleRecallAttachment($errors);
        }

        if ($errors) {
            remember_form_state(['recall_reason' => $reason], $errors);
            set_flash('error', 'Please correct the highlighted fields below.');
            header('Location: ' . url('leave/view') . '&id=' . $id);
            exit;
        }

        $recallDateTime = date('Y-m-d H:i:s');
        $carryoverDays = 0.0;
        $carryoverReferenceDate = date('Y-m-d');
        $startDate = (string) ($request['start_date'] ?? '');
        if ($this->validDate($startDate) && strtotime($carryoverReferenceDate) < strtotime($startDate)) {
            $carryoverReferenceDate = date('Y-m-d', strtotime($startDate . ' -1 day'));
        }

        try {
            db()->beginTransaction();

            if (!LeaveRequest::markRecalled($id, (int) $actor['id'], $reason, $recallAttachmentPath)) {
                throw new RuntimeException('The leave request could not be recalled.');
            }

            $carryoverDays = $this->restoreUnusedLeaveBalance($request, $carryoverReferenceDate);
            AuditService::record('recall_leave_request', 'leave_requests', $id);

            db()->commit();
        } catch (Throwable $throwable) {
            if (db()->inTransaction()) {
                db()->rollBack();
            }

            if ($recallAttachmentPath) {
                $file = app_config('leave_recall_dir') . '/' . basename($recallAttachmentPath);
                if (is_file($file)) {
                    @unlink($file);
                }
            }

            app_log($throwable);
            set_flash('error', 'Leave recall could not be recorded.');
            header('Location: ' . url('leave/view') . '&id=' . $id);
            exit;
        }

        $updatedRequest = LeaveRequest::find($id) ?? $request;
        $recallData = [
            'recalled_by_name' => $actor['full_name'] ?? 'your immediate supervisor',
            'recalled_at' => $updatedRequest['recalled_at'] ?? $recallDateTime,
            'reason' => $reason,
            'carryover_days' => $carryoverDays,
        ];

        $internalMessage = $request['employee_name'] . ' has been officially recalled from leave by the immediate supervisor.';
        if ($reason !== '') {
            $internalMessage .= ' Reason: ' . $reason;
        }
        if ($carryoverDays > 0) {
            $internalMessage .= ' ' . format_days($carryoverDays) . ' working day(s) were carried back to the leave balance.';
        }

        NotificationService::create(
            (int) $request['employee_user_id'],
            'Official leave recall issued',
            $internalMessage,
            url('leave/view') . '&id=' . $id
        );
        NotificationService::notifyRoles(
            ['admin', 'hr'],
            'Official leave recall issued',
            $request['employee_name'] . ' was recalled from leave by the immediate supervisor.' . ($reason !== '' ? ' Reason: ' . $reason : ''),
            url('leave/view') . '&id=' . $id
        );

        $emailSent = ExternalNotificationService::leaveRecallIssued($updatedRequest, $recallData);
        $message = 'Leave recall recorded for ' . $request['employee_name'] . '.';
        if ($carryoverDays > 0) {
            $message .= ' ' . format_days($carryoverDays) . ' working day(s) were carried back to the leave balance.';
        }
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

    public function passportPhoto(): void
    {
        require_auth();

        $id = (int) ($_GET['id'] ?? 0);
        $request = LeaveRequest::find($id);

        if (!$request || !$request['passport_photo_path'] || !$this->canView($request)) {
            http_response_code(404);
            echo 'Passport photo not found.';
            return;
        }

        $file = app_config('leave_passport_photo_dir') . '/' . basename($request['passport_photo_path']);
        if (!is_file($file)) {
            http_response_code(404);
            echo 'Passport photo file missing.';
            return;
        }

        $mime = mime_content_type($file) ?: 'application/octet-stream';
        header('Content-Type: ' . $mime);
        header('Content-Disposition: inline; filename="' . basename($file) . '"');
        header('Content-Length: ' . filesize($file));
        readfile($file);
    }

    public function recallAttachment(): void
    {
        require_auth();

        $id = (int) ($_GET['id'] ?? 0);
        $request = LeaveRequest::find($id);

        if (!$request || !$request['recall_attachment_path'] || !$this->canView($request)) {
            http_response_code(404);
            echo 'Recall attachment not found.';
            return;
        }

        $file = app_config('leave_recall_dir') . '/' . basename($request['recall_attachment_path']);
        if (!is_file($file)) {
            http_response_code(404);
            echo 'Recall attachment file missing.';
            return;
        }

        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . basename($file) . '"');
        header('X-Content-Type-Options: nosniff');
        header('Content-Length: ' . filesize($file));
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

    private function handlePassportPhotoUpload(array &$errors, ?string $existingPhoto = null): ?string
    {
        $file = $_FILES['passport_photo'] ?? null;

        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            if (!$existingPhoto) {
                $errors['passport_photo'] = 'Passport photo is required for every leave request.';
            }

            return $existingPhoto;
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors['passport_photo'] = 'Passport photo upload failed.';
            return null;
        }

        if ($file['size'] > app_config('leave_passport_photo_max_size')) {
            $errors['passport_photo'] = 'Passport photo must not exceed 10 MB.';
            return null;
        }

        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, app_config('leave_passport_photo_extensions'), true) || !uploaded_file_is_image($file)) {
            $errors['passport_photo'] = 'Passport photo must be a JPG, PNG, or WebP image.';
            return null;
        }

        $filename = date('YmdHis') . '-' . bin2hex(random_bytes(8)) . '.' . $extension;
        $destination = app_config('leave_passport_photo_dir') . '/' . $filename;

        if (!is_dir(app_config('leave_passport_photo_dir'))) {
            mkdir(app_config('leave_passport_photo_dir'), 0775, true);
        }

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            $errors['passport_photo'] = 'Could not save the passport photo.';
            return null;
        }

        return $filename;
    }

    private function handleRecallAttachment(array &$errors, ?string $existingAttachment = null): ?string
    {
        $file = $_FILES['recall_attachment'] ?? null;

        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            if (!$existingAttachment) {
                $errors['recall_attachment'] = 'Recall attachment is required and must be a PDF.';
            }

            return $existingAttachment;
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors['recall_attachment'] = 'Recall attachment upload failed.';
            return null;
        }

        if ($file['size'] > (int) app_config('leave_recall_max_size')) {
            $errors['recall_attachment'] = 'Recall attachment must not exceed 10 MB.';
            return null;
        }

        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, app_config('leave_recall_extensions'), true) || !uploaded_file_is_pdf($file)) {
            $errors['recall_attachment'] = 'Recall attachment must be a PDF file.';
            return null;
        }

        $uploadDir = app_config('leave_recall_dir');
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        $filename = 'recall-' . date('YmdHis') . '-' . bin2hex(random_bytes(8)) . '.' . $extension;
        $destination = $uploadDir . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            $errors['recall_attachment'] = 'Could not save the recall attachment.';
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

    private function canRecall(array $request): bool
    {
        $user = current_user();
        if (!$user || ($user['role'] ?? '') !== 'supervisor') {
            return false;
        }

        if (($request['status'] ?? '') !== 'approved' || !empty($request['resumed_at']) || has_official_leave_recall($request)) {
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

        if (!empty($request['recalled_at'])) {
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

    private function ensureRecallSchema(): void
    {
        $hasColumn = static function (string $column): bool {
            $stmt = db()->prepare(
                "SELECT COUNT(*)
                 FROM INFORMATION_SCHEMA.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = 'leave_requests'
                   AND COLUMN_NAME = ?"
            );
            $stmt->execute([$column]);

            return (int) $stmt->fetchColumn() > 0;
        };

        $columnInfo = static function (string $column): ?array {
            $stmt = db()->prepare(
                "SELECT DATA_TYPE, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT, EXTRA
                 FROM INFORMATION_SCHEMA.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = 'leave_requests'
                   AND COLUMN_NAME = ?
                 LIMIT 1"
            );
            $stmt->execute([$column]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            return $row ?: null;
        };

        $hasIndex = static function (string $index): bool {
            $stmt = db()->prepare(
                "SELECT COUNT(*)
                 FROM INFORMATION_SCHEMA.STATISTICS
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = 'leave_requests'
                   AND INDEX_NAME = ?"
            );
            $stmt->execute([$index]);

            return (int) $stmt->fetchColumn() > 0;
        };

        $tableEngine = static function (): ?string {
            $stmt = db()->prepare(
                "SELECT ENGINE
                 FROM INFORMATION_SCHEMA.TABLES
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = 'leave_requests'
                 LIMIT 1"
            );
            $stmt->execute();
            $engine = $stmt->fetchColumn();

            return $engine !== false ? (string) $engine : null;
        };

        $hasForeignKey = static function (string $constraint): bool {
            $stmt = db()->prepare(
                "SELECT COUNT(*)
                 FROM INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS
                 WHERE CONSTRAINT_SCHEMA = DATABASE()
                   AND TABLE_NAME = 'leave_requests'
                 AND CONSTRAINT_NAME = ?"
            );
            $stmt->execute([$constraint]);

            return (int) $stmt->fetchColumn() > 0;
        };

        $recalledByInfo = $columnInfo('recalled_by_user_id');
        $alterParts = [];

        if (!$hasColumn('recalled_at')) {
            $alterParts[] = 'ADD COLUMN recalled_at DATETIME NULL AFTER resumed_at';
        }
        if (!$hasColumn('recalled_by_user_id')) {
            $alterParts[] = 'ADD COLUMN recalled_by_user_id INT UNSIGNED NULL AFTER recalled_at';
        } elseif ($recalledByInfo && stripos((string) ($recalledByInfo['COLUMN_TYPE'] ?? ''), 'unsigned') === false) {
            $alterParts[] = 'MODIFY COLUMN recalled_by_user_id INT UNSIGNED NULL';
        }
        if (!$hasColumn('recall_reason')) {
            $alterParts[] = 'ADD COLUMN recall_reason TEXT NULL AFTER recalled_by_user_id';
        }
        if (!$hasColumn('recall_attachment_path')) {
            $alterParts[] = 'ADD COLUMN recall_attachment_path VARCHAR(255) NULL AFTER recall_reason';
        }
        if (!$hasIndex('idx_leave_requests_recalled_at')) {
            $alterParts[] = 'ADD KEY idx_leave_requests_recalled_at (recalled_at)';
        }

        if (($recalledByInfo !== null || $hasColumn('recalled_by_user_id')) && $hasColumn('recalled_by_user_id')) {
            try {
                db()->exec(
                    'UPDATE leave_requests lr
                     LEFT JOIN users u ON u.id = lr.recalled_by_user_id
                     SET lr.recalled_by_user_id = NULL
                     WHERE lr.recalled_by_user_id IS NOT NULL
                       AND (lr.recalled_by_user_id <= 0 OR u.id IS NULL)'
                );
            } catch (Throwable $throwable) {
                app_log($throwable);
            }
        }

        if ($tableEngine() !== null && strcasecmp((string) $tableEngine(), 'InnoDB') !== 0) {
            $alterParts[] = 'ENGINE=InnoDB';
        }

        if ($alterParts) {
            try {
                db()->exec('ALTER TABLE leave_requests ' . implode(', ', $alterParts));
            } catch (Throwable $throwable) {
                app_log($throwable);
            }
        }

        if (!$hasForeignKey('fk_leave_requests_recalled_by')) {
            try {
                db()->exec('ALTER TABLE leave_requests ADD CONSTRAINT fk_leave_requests_recalled_by FOREIGN KEY (recalled_by_user_id) REFERENCES users(id) ON DELETE SET NULL');
            } catch (Throwable $throwable) {
                app_log($throwable);
            }
        }
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

    private function restoreUnusedLeaveBalance(array $request, string $resumptionDate): float
    {
        $nextBusinessDate = LeaveBalanceService::returnDateAfter($resumptionDate);
        if (strtotime((string) $request['end_date']) < strtotime($nextBusinessDate)) {
            return 0.0;
        }

        $unusedDays = (float) LeaveBalanceService::businessDays($nextBusinessDate, (string) $request['end_date']);
        if ($unusedDays <= 0) {
            return 0.0;
        }

        return LeaveBalanceService::restore(
            (int) $request['employee_id'],
            (int) $request['leave_type_id'],
            $unusedDays,
            financial_year_key($request['start_date'] ?? null)
        );
    }

    private function notifyReturnFromLeave(array $request, array $actor, float $carryoverDays = 0.0): void
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

        if ($carryoverDays > 0) {
            $message .= ' ' . format_days($carryoverDays) . ' working day(s) were carried back to the leave balance.';
        }

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
