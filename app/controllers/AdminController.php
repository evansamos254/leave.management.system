<?php

class AdminController
{
    private array $roles = ['admin', 'employee', 'supervisor', 'hr', 'director', 'chief_officer'];
    private array $workerRoles = ['employee', 'supervisor', 'hr', 'director', 'chief_officer'];
    private array $statuses = ['pending', 'active', 'inactive', 'rejected'];
    private array $profileManagers = ['admin', 'supervisor', 'hr', 'director'];
    private array $monitoringRoles = ['admin', 'supervisor', 'hr', 'director'];
    private array $departmentViewRoles = ['admin', 'supervisor', 'hr', 'director', 'chief_officer'];

    public function users(): void
    {
        require_role($this->profileManagers);
        $user = current_user();

        view('admin/users', [
            'title' => 'User Managements',
            'users' => User::allWithEmployees($user),
            'approvers' => Employee::approvers($user),
            'roles' => $user['role'] === 'admin' ? $this->roles : ['employee'],
            'statuses' => $this->statuses,
        ]);
    }

    public function leaveRequests(): void
    {
        require_role($this->departmentViewRoles);
        $user = current_user();

        $status = trim($_GET['status'] ?? '');
        $search = trim($_GET['search'] ?? '');
        $allowedStatuses = [
            '',
            'pending',
            'pending_supervisor',
            'approved',
            'rejected',
            'cancelled',
            'forfeited',
        ];

        if (!in_array($status, $allowedStatuses, true)) {
            $status = '';
        }

        view('admin/leave-requests', [
            'title' => 'All Leave Requests',
            'requests' => LeaveRequest::allRequests($status ?: null, $search ?: null, $user),
            'status' => $status,
            'search' => $search,
            'statuses' => $allowedStatuses,
            'counts' => LeaveRequest::counts(null, $user),
        ]);
    }

    public function accountRequests(): void
    {
        require_role($this->monitoringRoles);
        $user = current_user();

        view('admin/account-requests', [
            'title' => 'ICT Account Requests',
            'requests' => User::pendingRegistrations($user),
            'canAct' => $user['role'] === 'admin',
        ]);
    }

    public function accountRequestView(): void
    {
        require_role($this->monitoringRoles);

        $id = (int) ($_GET['id'] ?? 0);
        $request = User::find($id);

        if (!$request || $request['status'] !== 'pending') {
            set_flash('error', 'Account request could not be found.');
            redirect('admin/account-requests');
        }

        $viewer = current_user();
        if (!AccessScopeService::canAccessUser($request, $viewer)) {
            set_flash('error', 'You cannot access account requests outside your department and directorate.');
            redirect('admin/account-requests');
        }

        $canAct = $viewer['role'] === 'admin';
        if ($canAct) {
            $_SESSION['reviewed_account_requests'][$id] = true;
        }

        $documentFile = $this->employmentDocumentFile($request);
        $documentExtension = strtolower(pathinfo((string) ($request['employment_document_path'] ?? ''), PATHINFO_EXTENSION));
        $previewExtensions = ['pdf'];

        view('admin/account-request-review', [
            'title' => 'Review Account Request',
            'request' => $request,
            'canAct' => $canAct,
            'documentUrl' => $documentFile ? url('admin/account-requests/document') . '&id=' . (int) $request['id'] : null,
            'documentExtension' => $documentExtension,
            'canPreviewDocument' => in_array($documentExtension, $previewExtensions, true),
        ]);
    }

    public function activity(): void
    {
        require_role(['admin', 'hr']);

        view('admin/activity', [
            'title' => 'System Logs',
            'logs' => AuditService::recent(),
        ]);
    }

    public function accountRequestAction(): void
    {
        require_role('admin');
        verify_csrf();

        $id = (int) ($_POST['id'] ?? 0);
        $action = $_POST['action'] ?? '';
        $user = User::find($id);

        if (!$user || $user['status'] !== 'pending') {
            set_flash('error', 'Account request could not be found.');
            redirect('admin/account-requests');
        }

        if (empty($_SESSION['reviewed_account_requests'][$id])) {
            set_flash('error', 'Please review the account details before taking action.');
            header('Location: ' . url('admin/account-requests/view') . '&id=' . $id);
            exit;
        }

        if ($action === 'approve') {
            if (!$this->employmentDocumentFile($user)) {
                set_flash('error', 'Supporting document must be available before approving this account.');
                header('Location: ' . url('admin/account-requests/view') . '&id=' . $id);
                exit;
            }

            User::updateStatus($id, 'active');
            AuditService::record('approve_account_request', 'users', $id);
            $emailSent = ExternalNotificationService::accountRequestApproved($user);
            $message = $user['full_name'] . ' can now log in.';
            $message .= $emailSent
                ? ' Approval email sent to ' . $user['email'] . '.'
                : ' Approval email could not be sent.' . $this->emailFailureSuffix();
            unset($_SESSION['reviewed_account_requests'][$id]);
            set_flash('success', $message);
            redirect('admin/account-requests');
        }

        if ($action === 'reject') {
            $rejectionReason = trim($_POST['rejection_reason'] ?? '');
            if ($rejectionReason === '') {
                set_flash('error', 'Please write a rejection note before rejecting this account request.');
                header('Location: ' . url('admin/account-requests/view') . '&id=' . $id);
                exit;
            }

            User::rejectAccountRequest($id, $rejectionReason);
            AuditService::record('reject_account_request', 'users', $id);
            $emailSent = ExternalNotificationService::accountRequestRejected($user, $rejectionReason);
            $message = $user['full_name'] . ' account request rejected.';
            $message .= $emailSent ? ' Rejection email sent.' : ' Rejection email could not be sent.' . $this->emailFailureSuffix();
            unset($_SESSION['reviewed_account_requests'][$id]);
            set_flash('success', $message);
            redirect('admin/account-requests');
        }

        set_flash('error', 'Invalid account request action.');
        redirect('admin/account-requests');
    }

    public function accountRequestDocument(): void
    {
        require_role($this->monitoringRoles);

        $id = (int) ($_GET['id'] ?? 0);
        $user = User::find($id);

        if (!$user || empty($user['employment_document_path'])) {
            http_response_code(404);
            echo 'Supporting document not found.';
            return;
        }

        if (!AccessScopeService::canAccessUser($user, current_user())) {
            http_response_code(404);
            echo 'Supporting document not found.';
            return;
        }

        $file = $this->employmentDocumentFile($user);
        if (!$file) {
            http_response_code(404);
            echo 'Supporting document file missing.';
            return;
        }

        $mime = mime_content_type($file) ?: 'application/octet-stream';
        header('Content-Type: ' . $mime);
        header('Content-Disposition: inline; filename="' . basename($file) . '"');
        header('X-Content-Type-Options: nosniff');
        header('Content-Length: ' . filesize($file));
        readfile($file);
    }

    public function workers(): void
    {
        require_role(['admin', 'hr']);

        view('admin/workers', [
            'title' => 'Staff',
            'workers' => Employee::workersWithAccounts(),
        ]);
    }

    public function createWorker(): void
    {
        require_role(['admin', 'hr']);
        $user = current_user();

        view('admin/create-worker', [
            'title' => 'Add Staff',
            'roles' => $user['role'] === 'admin' ? $this->workerRoles : ['employee'],
            'approvers' => Employee::approvers(),
            'directorates' => Directorate::all(),
            'departments' => Department::all(),
        ]);
    }

    public function storeWorker(): void
    {
        require_role(['admin', 'hr']);
        verify_csrf();

        $password = trim($_POST['password'] ?? '');
        $generatedPassword = $password === '' ? PasswordService::temporaryPassword() : $password;
        $firstName = trim($_POST['first_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        $data = [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'full_name' => build_full_name($firstName, $lastName),
            'email' => strtolower(trim($_POST['email'] ?? '')),
            'national_id' => User::normalizeNationalId((string) ($_POST['national_id'] ?? '')),
            'gender' => User::normalizeGender($_POST['gender'] ?? null),
            'phone' => trim($_POST['phone'] ?? ''),
            'staff_id' => strtoupper(trim($_POST['staff_id'] ?? '')),
            'directorate_id' => (int) ($_POST['directorate_id'] ?? 0),
            'department_id' => (int) ($_POST['department_id'] ?? 0),
            'designation' => trim($_POST['designation'] ?? ''),
            'job_group' => normalize_job_group($_POST['job_group'] ?? null),
            'employment_date' => trim($_POST['employment_date'] ?? ''),
            'role' => trim($_POST['role'] ?? 'employee'),
            'supervisor_id' => ($_POST['supervisor_id'] ?? '') !== '' ? (int) $_POST['supervisor_id'] : null,
            'password' => $password,
        ];

        if ($data['role'] === 'hr') {
            $data['directorate_id'] = 0;
            $data['department_id'] = 0;
            $data['supervisor_id'] = null;
        }

        $errors = $this->validateWorker($data);
        $currentUser = current_user();

        if ($currentUser['role'] !== 'admin' && $data['role'] !== 'employee') {
            $errors[] = 'Only the admin can create Supervisor, HR, Director, or Chief Officer accounts.';
        }

        if ($errors) {
            $_SESSION['old'] = array_diff_key($data, ['password' => true]);
            set_flash('error', implode(' ', $errors));
            redirect('workers/create');
        }

        try {
            db()->beginTransaction();

            $userId = User::create([
                'full_name' => $data['full_name'],
                'email' => $data['email'],
                'national_id' => $data['national_id'],
                'gender' => $data['gender'],
                'phone' => $data['phone'],
                'password_hash' => PasswordService::make($generatedPassword),
                'role' => $data['role'],
                'status' => 'active',
                'must_change_password' => 1,
            ]);

            $employeeId = Employee::create([
                'user_id' => $userId,
                'staff_id' => $data['staff_id'],
                'department_id' => $data['department_id'] ?: null,
                'designation' => $data['designation'],
                'job_group' => $data['job_group'],
                'supervisor_id' => $data['supervisor_id'],
                'employment_date' => $data['employment_date'],
            ]);

            LeaveBalanceService::ensureBalances($employeeId);
            AuditService::record('create_worker', 'users', $userId);

            db()->commit();
        } catch (Throwable $throwable) {
            db()->rollBack();
            app_log($throwable);
            $_SESSION['old'] = array_diff_key($data, ['password' => true]);
            set_flash('error', 'Staff account could not be created.');
            redirect('workers/create');
        }

        $message = 'Staff account created. Login email: ' . $data['email'];
        if ($data['national_id'] !== '') {
            $message .= ' National ID: ' . $data['national_id'];
        }
        $message .= ' Temporary password: ' . $generatedPassword;

        $emailSent = ExternalNotificationService::workerAccountCreated([
            'full_name' => $data['full_name'],
            'email' => $data['email'],
            'national_id' => $data['national_id'],
            'phone' => $data['phone'],
        ], $generatedPassword);
        $message .= $emailSent ? ' Email sent to staff member.' : ' Email could not be sent to staff member.' . $this->emailFailureSuffix();

        set_flash('success', $message);
        redirect('workers/create');
    }

    public function updateUser(): void
    {
        require_role($this->profileManagers);
        verify_csrf();

        $id = (int) ($_POST['id'] ?? 0);
        $role = $_POST['role'] ?? 'employee';
        $status = $_POST['status'] ?? 'active';
        $employeeId = (int) ($_POST['employee_id'] ?? 0);
        $supervisorId = ($_POST['supervisor_id'] ?? '') !== '' ? (int) $_POST['supervisor_id'] : null;
        $currentUser = current_user();
        $targetUser = User::find($id);

        if (!in_array($role, $this->roles, true) || !in_array($status, $this->statuses, true)) {
            set_flash('error', 'Invalid role or status.');
            redirect('admin/users');
        }

        if (!$targetUser) {
            set_flash('error', 'User profile could not be found.');
            redirect('admin/users');
        }

        if (!AccessScopeService::canAccessUser($targetUser, $currentUser)) {
            set_flash('error', 'You cannot update users outside your department and directorate.');
            redirect('admin/users');
        }

        if ($currentUser['role'] !== 'admin' && ($targetUser['role'] === 'admin' || $role === 'admin')) {
            set_flash('error', 'Only the admin can update admin-level access.');
            redirect('admin/users');
        }

        $privilegedRoles = ['supervisor', 'hr', 'director', 'chief_officer'];
        if ($currentUser['role'] !== 'admin' && (in_array($targetUser['role'], $privilegedRoles, true) || in_array($role, $privilegedRoles, true))) {
            set_flash('error', 'Only the admin can update Supervisor, HR, Director, or Chief Officer access.');
            redirect('admin/users');
        }

        if ($id === (int) $_SESSION['user_id'] && $status !== 'active') {
            set_flash('error', 'You cannot deactivate your own account.');
            redirect('admin/users');
        }

        User::updateAccess($id, $role, $status);

        if ($employeeId > 0) {
            Employee::updateSupervisor($employeeId, $supervisorId);
        }

        AuditService::record('update_user_access', 'users', $id);
        set_flash('success', 'User access updated.');
        redirect('admin/users');
    }

    public function editUser(): void
    {
        require_role('admin');

        $id = (int) ($_GET['id'] ?? 0);
        $targetUser = User::find($id);

        if (!$targetUser || $targetUser['role'] === 'admin') {
            set_flash('error', 'Only staff accounts can be edited from this page.');
            redirect('admin/users');
        }

        view('admin/user-edit', [
            'title' => 'Edit Staff Profile',
            'account' => $targetUser,
            'nameParts' => name_parts($targetUser['full_name'] ?? ''),
            'roles' => $this->workerRoles,
            'statuses' => $this->statuses,
            'approvers' => Employee::approvers(),
            'directorates' => Directorate::all(),
            'departments' => Department::all(),
        ]);
    }

    public function userHistory(): void
    {
        require_role('admin');

        $id = (int) ($_GET['id'] ?? 0);
        $targetUser = User::find($id);

        if (!$targetUser) {
            set_flash('error', 'User profile could not be found.');
            redirect('admin/users');
        }

        $employee = Employee::findByUserId($id);

        view('admin/user-history', [
            'title' => 'Staff History',
            'account' => $targetUser,
            'employee' => $employee,
            'activity' => AuditService::historyForUser($id),
            'leaveRequests' => $employee ? LeaveRequest::forEmployee((int) $employee['id']) : [],
        ]);
    }

    public function updateUserProfile(): void
    {
        require_role('admin');
        verify_csrf();

        $id = (int) ($_POST['id'] ?? 0);
        $targetUser = User::find($id);

        if (!$targetUser || $targetUser['role'] === 'admin') {
            set_flash('error', 'Only staff accounts can be edited.');
            redirect('admin/users');
        }

        $firstName = trim($_POST['first_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        $data = [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'full_name' => build_full_name($firstName, $lastName),
            'email' => strtolower(trim($_POST['email'] ?? '')),
            'national_id' => User::normalizeNationalId((string) ($_POST['national_id'] ?? '')),
            'gender' => User::normalizeGender($_POST['gender'] ?? null),
            'phone' => trim($_POST['phone'] ?? ''),
            'staff_id' => strtoupper(trim($_POST['staff_id'] ?? '')),
            'directorate_id' => (int) ($_POST['directorate_id'] ?? 0),
            'department_id' => (int) ($_POST['department_id'] ?? 0),
            'designation' => trim($_POST['designation'] ?? ''),
            'job_group' => normalize_job_group($_POST['job_group'] ?? null),
            'employment_date' => trim($_POST['employment_date'] ?? ''),
            'role' => trim($_POST['role'] ?? $targetUser['role']),
            'status' => trim($_POST['status'] ?? $targetUser['status']),
            'supervisor_id' => ($_POST['supervisor_id'] ?? '') !== '' ? (int) $_POST['supervisor_id'] : null,
        ];

        if ($data['role'] === 'hr') {
            $data['directorate_id'] = 0;
            $data['department_id'] = 0;
            $data['supervisor_id'] = null;
        }

        $errors = $this->validateUserProfile($targetUser, $data);
        if ($errors) {
            $_SESSION['old'] = $data;
            set_flash('error', implode(' ', $errors));
            header('Location: ' . url('admin/users/edit') . '&id=' . $id);
            exit;
        }

        try {
            db()->beginTransaction();

            User::updateProfile($id, $data);
            User::updateAccess($id, $data['role'], $data['status']);
            Employee::updateDetails((int) $targetUser['employee_id'], [
                'staff_id' => $data['staff_id'],
                'department_id' => $data['department_id'] ?: null,
                'designation' => $data['designation'],
                'job_group' => $data['job_group'],
                'supervisor_id' => $data['supervisor_id'],
                'employment_date' => $data['employment_date'],
            ]);

            AuditService::record('update_staff_profile', 'users', $id);
            db()->commit();
        } catch (Throwable $throwable) {
            db()->rollBack();
            app_log($throwable);
            $_SESSION['old'] = $data;
            set_flash('error', 'Staff profile could not be updated.');
            header('Location: ' . url('admin/users/edit') . '&id=' . $id);
            exit;
        }

        set_flash('success', 'Staff profile updated.');
        redirect('admin/users');
    }

    public function toggleUserStatus(): void
    {
        require_role('admin');
        verify_csrf();

        $id = (int) ($_POST['id'] ?? 0);
        $targetUser = User::find($id);

        if (!$targetUser || $targetUser['role'] === 'admin') {
            set_flash('error', 'Admin accounts cannot be changed from this action.');
            redirect('admin/users');
        }

        if ($id === (int) current_user()['id']) {
            set_flash('error', 'You cannot deactivate or reactivate your own account here.');
            redirect('admin/users');
        }

        if (!in_array($targetUser['status'], ['active', 'inactive'], true)) {
            set_flash('error', 'Only active or inactive accounts can be toggled from this shortcut.');
            redirect('admin/users');
        }

        $newStatus = $targetUser['status'] === 'active' ? 'inactive' : 'active';
        User::updateStatus($id, $newStatus);
        AuditService::record(
            $newStatus === 'inactive' ? 'deactivate_staff_account' : 'reactivate_staff_account',
            'users',
            $id
        );

        set_flash(
            'success',
            $targetUser['full_name'] . ' account ' . ($newStatus === 'inactive' ? 'deactivated' : 'reactivated') . '.'
        );
        redirect('admin/users');
    }

    public function resetUserPassword(): void
    {
        require_role('admin');
        verify_csrf();

        $id = (int) ($_POST['id'] ?? 0);
        $targetUser = User::find($id);

        if (!$targetUser || $targetUser['role'] === 'admin') {
            set_flash('error', 'Only staff account passwords can be reset here.');
            redirect('admin/users');
        }

        $temporaryPassword = PasswordService::temporaryPassword();
        User::updatePassword($id, PasswordService::make($temporaryPassword));
        User::setPasswordChangeRequired($id, true);
        User::clearLoginLock($id);
        AuditService::record('admin_reset_staff_password', 'users', $id);

        $emailSent = ExternalNotificationService::passwordReset($targetUser, $temporaryPassword);
        $message = 'Password reset for ' . $targetUser['full_name'] . '. Temporary password: ' . $temporaryPassword . '.';
        $message .= $emailSent ? ' Email sent to staff member.' : ' Email could not be sent to staff member.' . $this->emailFailureSuffix();

        set_flash('success', $message);
        redirect('admin/users');
    }

    public function deleteUser(): void
    {
        require_role('admin');
        verify_csrf();

        $id = (int) ($_POST['id'] ?? 0);
        $targetUser = User::find($id);

        if (!$targetUser || $targetUser['role'] === 'admin') {
            set_flash('error', 'Admin accounts cannot be deleted from this action.');
            redirect('admin/users');
        }

        if ($id === (int) current_user()['id']) {
            set_flash('error', 'You cannot delete your own account.');
            redirect('admin/users');
        }

        $filesToDelete = $this->filesForDeletedUser($targetUser);

        try {
            db()->beginTransaction();
            AuditService::record('delete_staff_account', 'users', $id);
            User::delete($id);
            db()->commit();
        } catch (Throwable $throwable) {
            db()->rollBack();
            app_log($throwable);
            set_flash('error', 'Staff account could not be deleted.');
            redirect('admin/users');
        }

        $this->deleteLocalFiles($filesToDelete);

        set_flash('success', $targetUser['full_name'] . ' account deleted.');
        redirect('admin/users');
    }

    public function leaveTypes(): void
    {
        require_role('admin');

        view('admin/leave-types', [
            'title' => 'Leave Types',
            'leaveTypes' => LeaveType::all(),
        ]);
    }

    public function saveLeaveType(): void
    {
        require_role('admin');
        verify_csrf();

        $name = trim($_POST['name'] ?? '');

        if ($name === '') {
            set_flash('error', 'Leave type name is required.');
            redirect('admin/leave-types');
        }

        $defaultEntitlement = max(0, (int) round((float) ($_POST['default_entitlement'] ?? 0)));
        $attachmentAfterDays = ($_POST['attachment_after_days'] ?? '') !== ''
            ? max(0, (int) round((float) $_POST['attachment_after_days']))
            : null;

        $data = [
            'id' => ($_POST['id'] ?? '') !== '' ? (int) $_POST['id'] : null,
            'name' => $name,
            'gender_eligibility' => LeaveType::normalizeEligibility($_POST['gender_eligibility'] ?? 'any'),
            'default_entitlement' => $defaultEntitlement,
            'requires_balance' => isset($_POST['requires_balance']) ? 1 : 0,
            'requires_attachment' => isset($_POST['requires_attachment']) ? 1 : 0,
            'attachment_after_days' => $attachmentAfterDays,
            'is_paid' => isset($_POST['is_paid']) ? 1 : 0,
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
        ];

        LeaveType::save($data);
        AuditService::record('save_leave_type', 'leave_types', $data['id']);
        set_flash('success', 'Leave type saved.');
        redirect('admin/leave-types');
    }

    public function holidays(): void
    {
        require_role('admin');

        $year = (int) ($_GET['year'] ?? date('Y'));
        if ($year < 2000 || $year > 2100) {
            $year = (int) date('Y');
        }

        $stmt = db()->prepare(
            'SELECT *
             FROM holidays
             WHERE YEAR(holiday_date) = ?
             ORDER BY holiday_date ASC, name ASC'
        );
        $stmt->execute([$year]);

        view('admin/holidays', [
            'title' => 'Public Holidays',
            'year' => $year,
            'holidays' => $stmt->fetchAll(),
        ]);
    }

    public function saveHoliday(): void
    {
        require_role('admin');
        verify_csrf();

        $name = trim($_POST['name'] ?? '');
        $date = trim($_POST['holiday_date'] ?? '');

        if ($name === '' || $date === '') {
            set_flash('error', 'Holiday name and date are required.');
            redirect('admin/holidays');
        }

        $stmt = db()->prepare('INSERT INTO holidays (name, holiday_date) VALUES (?, ?)
                              ON DUPLICATE KEY UPDATE name = VALUES(name)');
        $stmt->execute([$name, $date]);

        AuditService::record('save_holiday', 'holidays');
        set_flash('success', 'Holiday saved.');
        header('Location: ' . url('admin/holidays') . '&year=' . urlencode(substr($date, 0, 4)));
        exit;
    }

    public function syncHolidays(): void
    {
        require_role('admin');
        verify_csrf();

        $year = (int) ($_POST['year'] ?? date('Y'));

        try {
            $result = HolidaySyncService::syncKenyaPublicHolidays($year);
            AuditService::record('sync_kenya_holidays', 'holidays');
            set_flash(
                'success',
                'Kenya public holidays synced for '
                    . $result['year']
                    . '. Added '
                    . $result['inserted']
                    . ', updated '
                    . $result['updated']
                    . '.'
            );
        } catch (Throwable $throwable) {
            app_log($throwable);
            set_flash('error', $throwable->getMessage());
        }

        header('Location: ' . url('admin/holidays') . '&year=' . urlencode((string) $year));
        exit;
    }

    public function deleteHoliday(): void
    {
        require_role('admin');
        verify_csrf();

        $id = (int) ($_POST['id'] ?? 0);
        $stmt = db()->prepare('DELETE FROM holidays WHERE id = ?');
        $stmt->execute([$id]);

        AuditService::record('delete_holiday', 'holidays', $id);
        set_flash('success', 'Holiday deleted.');
        header('Location: ' . url('admin/holidays') . '&year=' . urlencode((string) ($_POST['year'] ?? date('Y'))));
        exit;
    }

    private function validateWorker(array $data): array
    {
        $errors = [];

        if ($data['first_name'] === '') {
            $errors[] = 'First name is required.';
        }

        if ($data['last_name'] === '') {
            $errors[] = 'Last name is required.';
        }

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'A valid email address is required.';
        } elseif (User::findByEmail($data['email'])) {
            $errors[] = 'Email address is already registered.';
        }

        if ($data['national_id'] !== '') {
            if (!preg_match('/^[A-Z0-9-]{4,50}$/', $data['national_id'])) {
                $errors[] = 'National ID may only contain letters, numbers, and hyphens.';
            } elseif (User::findByNationalId($data['national_id'])) {
                $errors[] = 'National ID is already registered.';
            }
        }

        if ($data['gender'] === null) {
            $errors[] = 'Gender is required.';
        }

        $phoneError = kenyan_phone_number_error($data['phone']);
        if ($phoneError !== null) {
            $errors[] = $phoneError;
        }

        if ($data['staff_id'] === '') {
            $errors[] = 'Payroll or ID number is required.';
        } elseif (Employee::findByStaffId($data['staff_id'])) {
            $errors[] = 'Payroll or ID number is already registered.';
        }

        $isHrOfficeAccount = $data['role'] === 'hr';

        if (!$isHrOfficeAccount && ($data['directorate_id'] < 1 || !Directorate::find($data['directorate_id']))) {
            $errors[] = 'Department is required.';
        }

        if (!$isHrOfficeAccount && ($data['department_id'] < 1 || !Department::find($data['department_id']))) {
            $errors[] = 'Directorate is required.';
        } elseif (!$isHrOfficeAccount && $data['directorate_id'] > 0 && !Department::belongsToDirectorate($data['department_id'], $data['directorate_id'])) {
            $errors[] = 'Selected directorate does not belong to the selected department.';
        }

        if ($data['designation'] === '') {
            $errors[] = 'Designation is required.';
        }

        if (!is_valid_job_group($data['job_group'])) {
            $errors[] = 'Please select or enter a valid job group.';
        }

        if (!in_array($data['role'], $this->workerRoles, true)) {
            $errors[] = 'Please select a valid staff role.';
        }

        if ($data['password'] !== '' && strlen($data['password']) < 6) {
            $errors[] = 'Password must be at least 6 characters when manually set.';
        }

        if ($data['employment_date'] !== '' && !is_valid_past_or_today_date($data['employment_date'])) {
            $errors[] = 'Employment date is invalid or cannot be in the future.';
        }

        if ($data['supervisor_id'] !== null && !Employee::find((int) $data['supervisor_id'])) {
            $errors[] = 'Selected supervisor could not be found.';
        }

        return $errors;
    }

    private function validateUserProfile(array $targetUser, array $data): array
    {
        $errors = [];

        if (empty($targetUser['employee_id'])) {
            $errors[] = 'This account does not have a staff profile to edit.';
        }

        if ($data['first_name'] === '') {
            $errors[] = 'First name is required.';
        }

        if ($data['last_name'] === '') {
            $errors[] = 'Last name is required.';
        }

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'A valid email address is required.';
        } else {
            $existing = User::findByEmail($data['email']);
            if ($existing && (int) $existing['id'] !== (int) $targetUser['id']) {
                $errors[] = 'Email address is already registered.';
            }
        }

        if ($data['national_id'] !== '') {
            if (!preg_match('/^[A-Z0-9-]{4,50}$/', $data['national_id'])) {
                $errors[] = 'National ID may only contain letters, numbers, and hyphens.';
            } else {
                $existing = User::findByNationalId($data['national_id']);
                if ($existing && (int) $existing['id'] !== (int) $targetUser['id']) {
                    $errors[] = 'National ID is already registered.';
                }
            }
        }

        if ($data['gender'] === null) {
            $errors[] = 'Gender is required.';
        }

        $phoneError = kenyan_phone_number_error($data['phone']);
        if ($phoneError !== null) {
            $errors[] = $phoneError;
        }

        if ($data['staff_id'] === '') {
            $errors[] = 'Payroll or ID number is required.';
        } else {
            $existingEmployee = Employee::findByStaffId($data['staff_id']);
            if ($existingEmployee && (int) $existingEmployee['id'] !== (int) ($targetUser['employee_id'] ?? 0)) {
                $errors[] = 'Payroll or ID number is already registered.';
            }
        }

        if (!in_array($data['role'], $this->workerRoles, true)) {
            $errors[] = 'Please select a valid staff role.';
        }

        if (!in_array($data['status'], $this->statuses, true)) {
            $errors[] = 'Please select a valid account status.';
        }

        $isHrOfficeAccount = $data['role'] === 'hr';

        if (!$isHrOfficeAccount && ($data['directorate_id'] < 1 || !Directorate::find($data['directorate_id']))) {
            $errors[] = 'Department is required.';
        }

        if (!$isHrOfficeAccount && ($data['department_id'] < 1 || !Department::find($data['department_id']))) {
            $errors[] = 'Directorate is required.';
        } elseif (!$isHrOfficeAccount && $data['directorate_id'] > 0 && !Department::belongsToDirectorate($data['department_id'], $data['directorate_id'])) {
            $errors[] = 'Selected directorate does not belong to the selected department.';
        }

        if ($data['designation'] === '') {
            $errors[] = 'Designation is required.';
        }

        if (!is_valid_job_group($data['job_group'])) {
            $errors[] = 'Please select or enter a valid job group.';
        }

        if ($data['employment_date'] !== '' && !is_valid_past_or_today_date($data['employment_date'])) {
            $errors[] = 'Employment date is invalid or cannot be in the future.';
        }

        if ($data['supervisor_id'] !== null) {
            if ((int) $data['supervisor_id'] === (int) ($targetUser['employee_id'] ?? 0)) {
                $errors[] = 'A staff member cannot supervise their own profile.';
            } elseif (!Employee::find((int) $data['supervisor_id'])) {
                $errors[] = 'Selected supervisor could not be found.';
            }
        }

        return $errors;
    }

    private function filesForDeletedUser(array $user): array
    {
        $files = [];

        if (!empty($user['profile_photo_path'])) {
            $files[] = app_config('profile_photo_dir') . '/' . basename((string) $user['profile_photo_path']);
        }

        if (!empty($user['employment_document_path'])) {
            $files[] = app_config('employment_document_dir') . '/' . basename((string) $user['employment_document_path']);
        }

        if (!empty($user['employee_id'])) {
            $stmt = db()->prepare(
                "SELECT attachment_path, passport_photo_path
                 FROM leave_requests
                 WHERE employee_id = ? AND (
                     (attachment_path IS NOT NULL AND attachment_path <> '')
                     OR (passport_photo_path IS NOT NULL AND passport_photo_path <> '')
                 )"
            );
            $stmt->execute([(int) $user['employee_id']]);

            foreach ($stmt->fetchAll() as $request) {
                if (!empty($request['attachment_path'])) {
                    $files[] = app_config('upload_dir') . '/' . basename((string) $request['attachment_path']);
                }

                if (!empty($request['passport_photo_path'])) {
                    $files[] = app_config('leave_passport_photo_dir') . '/' . basename((string) $request['passport_photo_path']);
                }
            }
        }

        return array_values(array_unique($files));
    }

    private function deleteLocalFiles(array $files): void
    {
        foreach ($files as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
    }

    private function emailFailureSuffix(): string
    {
        $reason = trim(ExternalNotificationService::lastEmailError());

        return $reason !== ''
            ? ' Reason: ' . rtrim($reason, '.') . '.'
            : ' Check outbound notification logs.';
    }

    private function employmentDocumentFile(array $user): ?string
    {
        if (empty($user['employment_document_path'])) {
            return null;
        }

        $file = app_config('employment_document_dir') . '/' . basename((string) $user['employment_document_path']);

        return is_file($file) ? $file : null;
    }
}
