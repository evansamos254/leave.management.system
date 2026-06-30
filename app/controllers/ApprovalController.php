<?php

class ApprovalController
{
    public function index(): void
    {
        require_role(['admin', 'supervisor', 'hr', 'director']);

        $user = current_user();
        $employee = Employee::findByUserId((int) $user['id']);

        view('approvals/index', [
            'title' => $user['role'] === 'admin' ? 'Approval Progress' : 'Approvals',
            'requests' => LeaveRequest::pendingForRole($user['role'], $employee ? (int) $employee['id'] : null, $user),
            'user' => $user,
        ]);
    }

    public function action(): void
    {
        require_role('supervisor');
        verify_csrf();

        $id = (int) ($_POST['id'] ?? 0);
        $action = $_POST['action'] ?? '';
        $comments = trim($_POST['comments'] ?? '');
        $request = LeaveRequest::find($id);
        $user = current_user();

        if (!$request || !in_array($action, ['approve', 'reject'], true)) {
            set_flash('error', 'Invalid approval action.');
            redirect('approvals');
        }

        $requiredRole = ApprovalWorkflowService::roleForStatus($request['status']);
        if (!$requiredRole) {
            set_flash('error', 'This request is not awaiting approval.');
            redirect('approvals');
        }

        if ($user['role'] !== $requiredRole) {
            set_flash('error', 'This request is not assigned to your approval stage.');
            redirect('approvals');
        }

        if ($requiredRole === 'supervisor' && !$this->canSupervisorActOnRequest($request, $user)) {
            set_flash('error', 'This request is not assigned to your supervisor queue.');
            redirect('approvals');
        }

        if ($action === 'reject' && $comments === '') {
            set_flash('error', 'A rejection comment is required.');
            redirect('approvals');
        }

        $nextStatus = null;
        $approvalWish = ExternalNotificationService::leaveApprovalWish($request);

        try {
            db()->beginTransaction();

            ApprovalWorkflowService::recordAction(
                $id,
                $requiredRole,
                (int) $user['id'],
                $action === 'approve' ? 'approved' : 'rejected',
                $comments
            );

            if ($action === 'reject') {
                LeaveRequest::updateStatus($id, 'rejected', $comments);
                NotificationService::create(
                    (int) $request['employee_user_id'],
                    'Leave request rejected',
                    'Your leave request was rejected at the ' . role_label($requiredRole) . ' stage.',
                    url('leave/view') . '&id=' . $id
                );
                AuditService::record('reject_leave_request', 'leave_requests', $id);
                db()->commit();
                ExternalNotificationService::leaveRequestRejected($request, $requiredRole, $comments);
                set_flash('success', 'Leave request rejected.');
                redirect('approvals');
            }

            $nextStatus = ApprovalWorkflowService::nextStatus($request['status']);
            LeaveRequest::updateStatus($id, $nextStatus);

            LeaveBalanceService::deduct(
                (int) $request['employee_id'],
                (int) $request['leave_type_id'],
                (float) $request['days_requested'],
                financial_year_key($request['start_date'] ?? null)
            );

            NotificationService::create(
                (int) $request['employee_user_id'],
                'Leave request approved',
                'Your leave request has received final approval. ' . $approvalWish,
                url('leave/view') . '&id=' . $id
            );

            NotificationService::notifyRoles(
                ['hr'],
                'Approved leave record ready',
                $request['employee_name'] . "'s leave request has been approved by the supervisor. Download the PDF form for record keeping.",
                url('leave/view') . '&id=' . $id
            );

            AuditService::record('approve_leave_request', 'leave_requests', $id);
            db()->commit();
        } catch (Throwable $throwable) {
            db()->rollBack();
            app_log($throwable);
            set_flash('error', 'Approval action failed.');
            redirect('approvals');
        }

        ExternalNotificationService::leaveRequestApproved($request);

        set_flash('success', 'Approval action saved.');
        redirect('approvals');
    }

    private function canSupervisorActOnRequest(array $request, array $user): bool
    {
        if (($user['role'] ?? '') !== 'supervisor') {
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
}
