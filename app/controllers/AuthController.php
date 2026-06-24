<?php

class AuthController
{
    public function showLogin(): void
    {
        require_guest();
        plain_view('auth/login', ['title' => 'Login']);
    }

    public function showForgotPassword(): void
    {
        require_guest();
        plain_view('auth/forgot-password', ['title' => 'Reset Password']);
    }

    public function login(): void
    {
        require_guest();
        verify_csrf();

        $identifier = trim($_POST['identifier'] ?? $_POST['email'] ?? '');
        $password = (string) ($_POST['password'] ?? '');

        if ($identifier === '' || $password === '') {
            set_flash('error', 'Email/National ID and password are required.');
            redirect('login');
        }

        $user = User::findByLoginIdentifier($identifier);
        if ($user && $this->isLoginLocked($user)) {
            set_flash('error', $this->loginLockedMessage($user));
            redirect('login');
        }

        if (!$user || !PasswordService::verify($password, $user['password_hash'])) {
            if ($user) {
                $this->recordFailedLogin($user);
            }
            set_flash('error', 'Invalid email/National ID or password.');
            redirect('login');
        }

        if ($user['status'] === 'pending') {
            set_flash('error', 'Your account request is awaiting ICT approval.');
            redirect('login');
        }

        if ($user['status'] === 'rejected') {
            $message = 'Your account request was rejected.';
            if (!empty($user['rejection_reason'])) {
                $message .= ' Reason: ' . $user['rejection_reason'];
            }
            $message .= ' Please contact ICT.';
            set_flash('error', $message);
            redirect('login');
        }

        if ($user['status'] !== 'active') {
            set_flash('error', 'Your account is inactive. Please contact ICT.');
            redirect('login');
        }

        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['last_activity_at'] = time();
        User::updateLastLogin((int) $user['id']);
        AuditService::record('login', 'users', (int) $user['id'], (int) $user['id']);

        redirect('dashboard');
    }

    public function forgotPassword(): void
    {
        require_guest();
        verify_csrf();

        $identifier = trim($_POST['identifier'] ?? '');

        if ($identifier === '') {
            set_flash('error', 'Email/National ID is required.');
            redirect('forgot-password');
        }

        $user = User::findByLoginIdentifier($identifier);

        if ($user && $user['status'] === 'active') {
            $temporaryPassword = PasswordService::temporaryPassword();

            try {
                db()->beginTransaction();
                User::updatePassword((int) $user['id'], PasswordService::make($temporaryPassword));

                if (ExternalNotificationService::passwordReset($user, $temporaryPassword)) {
                    AuditService::record('forgot_password_reset', 'users', (int) $user['id'], (int) $user['id']);
                    db()->commit();
                } else {
                    db()->rollBack();
                }
            } catch (Throwable $throwable) {
                db()->rollBack();
                app_log($throwable);
            }
        }

        set_flash('success', 'If the account exists and is active, a temporary password has been sent to the registered email address.');
        redirect('login');
    }

    public function showRegister(): void
    {
        require_guest();
        plain_view('auth/register', [
            'title' => 'Request Account',
            'directorates' => Directorate::all(),
            'departments' => Department::all(),
        ]);
    }

    public function register(): void
    {
        require_guest();
        verify_csrf();

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
            'employment_date' => trim($_POST['employment_date'] ?? ''),
            'password' => (string) ($_POST['password'] ?? ''),
            'password_confirmation' => (string) ($_POST['password_confirmation'] ?? ''),
        ];

        $errors = $this->validateRegistration($data);
        $documentPath = $this->handleEmploymentDocument($errors);

        if ($errors) {
            $_SESSION['old'] = $data;
            set_flash('error', implode(' ', $errors));
            redirect('register');
        }

        try {
            db()->beginTransaction();

            $userId = User::create([
                'full_name' => $data['full_name'],
                'email' => $data['email'],
                'national_id' => $data['national_id'],
                'gender' => $data['gender'],
                'phone' => $data['phone'],
                'password_hash' => PasswordService::make($data['password']),
                'role' => 'employee',
                'status' => 'pending',
                'employment_document_path' => $documentPath,
            ]);

            $employeeId = Employee::create([
                'user_id' => $userId,
                'staff_id' => $data['staff_id'],
                'department_id' => $data['department_id'],
                'designation' => $data['designation'],
                'employment_date' => $data['employment_date'],
            ]);

            LeaveBalanceService::ensureBalances($employeeId);
            AuditService::record('request_account', 'users', $userId, $userId);

            db()->commit();
        } catch (Throwable $throwable) {
            db()->rollBack();
            if ($documentPath) {
                $documentFile = app_config('employment_document_dir') . '/' . basename($documentPath);
                if (is_file($documentFile)) {
                    @unlink($documentFile);
                }
            }
            app_log($throwable);
            $_SESSION['old'] = $data;
            set_flash('error', 'Account request could not be submitted. Please try again.');
            redirect('register');
        }

        NotificationService::notifyRoles(
            ['admin'],
            'Account request awaiting ICT approval',
            $data['full_name'] . ' submitted a new account request.',
            url('admin/account-requests')
        );
        ExternalNotificationService::accountRequestReceived($data['full_name'], $data['email'], $data['phone']);

        set_flash('success', 'Account request submitted successfully. Please wait for ICT approval before logging in.');
        redirect('login');
    }

    public function logout(): void
    {
        require_auth();
        verify_csrf();
        no_cache_headers();

        AuditService::record('logout', 'users', (int) $_SESSION['user_id']);
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }

        session_destroy();
        redirect('login');
    }

    private function validateRegistration(array $data): array
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

        if ($data['national_id'] === '') {
            $errors[] = 'National ID is required.';
        } elseif (!preg_match('/^[A-Z0-9-]{4,50}$/', $data['national_id'])) {
            $errors[] = 'National ID may only contain letters, numbers, and hyphens.';
        } elseif (User::findByNationalId($data['national_id'])) {
            $errors[] = 'National ID is already registered.';
        }

        if ($data['gender'] === null) {
            $errors[] = 'Gender is required.';
        }

        if ($data['staff_id'] === '') {
            $errors[] = 'Payroll or ID number is required.';
        } elseif (Employee::findByStaffId($data['staff_id'])) {
            $errors[] = 'Payroll or ID number is already registered.';
        }

        if ($data['directorate_id'] < 1 || !Directorate::find($data['directorate_id'])) {
            $errors[] = 'Department is required.';
        }

        if ($data['department_id'] < 1 || !Department::find($data['department_id'])) {
            $errors[] = 'Directorate is required.';
        } elseif ($data['directorate_id'] > 0 && !Department::belongsToDirectorate($data['department_id'], $data['directorate_id'])) {
            $errors[] = 'Selected directorate does not belong to the selected department.';
        }

        if ($data['designation'] === '') {
            $errors[] = 'Designation is required.';
        }

        if (strlen($data['password']) < 6) {
            $errors[] = 'Password must be at least 6 characters.';
        }

        if ($data['password'] !== $data['password_confirmation']) {
            $errors[] = 'Password confirmation does not match.';
        }

        if ($data['employment_date'] !== '' && !is_valid_past_or_today_date($data['employment_date'])) {
            $errors[] = 'Employment date is invalid or cannot be in the future.';
        }

        return $errors;
    }

    private function recordFailedLogin(array $user): void
    {
        $security = app_config('security', []);
        $maxAttempts = max(1, (int) ($security['max_login_attempts'] ?? 3));
        $lockoutMinutes = max(1, (int) ($security['login_lockout_minutes'] ?? 15));
        $updatedUser = User::recordFailedLogin((int) $user['id'], $maxAttempts, $lockoutMinutes);
        AuditService::record('failed_login', 'users', (int) $user['id'], (int) $user['id']);

        if ($updatedUser && $this->isLoginLocked($updatedUser)) {
            set_flash('error', $this->loginLockedMessage($updatedUser));
            redirect('login');
        }
    }

    private function isLoginLocked(array $user): bool
    {
        if (empty($user['locked_until'])) {
            return false;
        }

        if (strtotime((string) $user['locked_until']) <= time()) {
            User::clearLoginLock((int) $user['id']);
            return false;
        }

        return true;
    }

    private function loginLockedMessage(array $user): string
    {
        $unlockTime = date('d M Y H:i', strtotime((string) $user['locked_until']));

        return 'Too many failed login attempts. This account is locked until ' . $unlockTime . '.';
    }

    private function handleEmploymentDocument(array &$errors): ?string
    {
        $file = $_FILES['employment_document'] ?? null;

        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            $errors[] = 'Supporting document proving county employment is required.';
            return null;
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Supporting document upload failed.';
            return null;
        }

        if ($file['size'] > app_config('employment_document_max_size')) {
            $errors[] = 'Supporting document must not exceed 10 MB.';
            return null;
        }

        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, app_config('employment_document_extensions'), true) || !uploaded_file_is_pdf($file)) {
            $errors[] = 'Supporting document must be a PDF file.';
            return null;
        }

        if ($errors) {
            return null;
        }

        $uploadDir = app_config('employment_document_dir');
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        $filename = 'employment-' . date('YmdHis') . '-' . bin2hex(random_bytes(8)) . '.' . $extension;
        $destination = $uploadDir . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            $errors[] = 'Could not save the supporting document.';
            return null;
        }

        return $filename;
    }
}
