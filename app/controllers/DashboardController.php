<?php

class DashboardController
{
    public function index(): void
    {
        require_auth();

        $user = current_user();
        $employee = $user['employee_id'] ? Employee::find((int) $user['employee_id']) : null;
        $counts = match ($user['role']) {
            'admin', 'hr', 'supervisor', 'director' => LeaveRequest::counts(null, $user),
            default => $employee ? LeaveRequest::counts((int) $employee['id']) : LeaveRequest::counts(),
        };
        $leaveTypes = LeaveType::active();
        $leaveTypeStats = [
            'active' => count($leaveTypes),
            'paid' => count(array_filter($leaveTypes, fn (array $type): bool => (int) ($type['is_paid'] ?? 0) === 1)),
            'unpaid' => count(array_filter($leaveTypes, fn (array $type): bool => (int) ($type['is_paid'] ?? 0) !== 1)),
            'tracked' => count(array_filter($leaveTypes, fn (array $type): bool => LeaveType::isBalanceTracked($type))),
        ];
        $pendingApprovals = in_array($user['role'], ['admin', 'supervisor', 'hr', 'director'], true)
            ? LeaveRequest::pendingForRole($user['role'], $employee ? (int) $employee['id'] : null, $user)
            : [];
        $liveOverview = in_array($user['role'], ['admin', 'supervisor', 'hr', 'director'], true)
            ? LeaveRequest::liveOverview($user['role'], $employee ? (int) $employee['id'] : null)
            : null;

        view('dashboard/index', [
            'title' => 'Dashboard',
            'user' => $user,
            'employee' => $employee,
            'counts' => $counts,
            'leaveTypes' => $leaveTypes,
            'leaveTypeStats' => $leaveTypeStats,
            'pendingApprovals' => $pendingApprovals,
            'liveOverview' => $liveOverview,
            'notifications' => NotificationService::recent((int) $user['id']),
            'stats' => [
                'users' => User::countByRole(null, $user),
                'employees' => User::countByRole('employee', $user),
                'supervisors' => User::countByRole('supervisor', $user),
                'hr' => User::countByRole('hr', $user),
                'directors' => User::countByRole('director', $user),
                'departments' => Department::count(),
            ],
        ]);
    }

    public function markNotificationsRead(): void
    {
        require_auth();
        verify_csrf();

        NotificationService::markRead((int) $_SESSION['user_id']);
        redirect('dashboard');
    }

    public function updateProfile(): void
    {
        require_auth();
        verify_csrf();

        $redirectTo = trim($_POST['redirect_to'] ?? 'dashboard') ?: 'dashboard';
        $user = current_user();
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
        ];
        $errors = [];

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
            if ($existing && (int) $existing['id'] !== (int) $user['id']) {
                $errors[] = 'Email address is already registered.';
            }
        }

        if ($data['national_id'] !== '') {
            if (!preg_match('/^[A-Z0-9-]{4,50}$/', $data['national_id'])) {
                $errors[] = 'National ID may only contain letters, numbers, and hyphens.';
            } else {
                $existing = User::findByNationalId($data['national_id']);
                if ($existing && (int) $existing['id'] !== (int) $user['id']) {
                    $errors[] = 'National ID is already registered.';
                }
            }
        }

        if ($data['gender'] === null) {
            $errors[] = 'Gender is required.';
        }

        if ($errors) {
            set_flash('error', implode(' ', $errors));
            redirect($redirectTo);
        }

        User::updateProfile((int) $user['id'], $data);
        AuditService::record('update_profile', 'users', (int) $user['id']);
        set_flash('success', 'Profile information updated.');
        redirect($redirectTo);
    }

    public function updatePassword(): void
    {
        require_auth();
        verify_csrf();

        $redirectTo = trim($_POST['redirect_to'] ?? 'dashboard') ?: 'dashboard';
        $user = current_user();
        $currentPassword = (string) ($_POST['current_password'] ?? '');
        $newPassword = (string) ($_POST['password'] ?? '');
        $confirmation = (string) ($_POST['password_confirmation'] ?? '');
        $errors = [];

        if ($currentPassword === '' || !PasswordService::verify($currentPassword, $user['password_hash'])) {
            $errors[] = 'Current password is incorrect.';
        }

        if (strlen($newPassword) < 6) {
            $errors[] = 'New password must be at least 6 characters.';
        }

        if ($newPassword !== $confirmation) {
            $errors[] = 'Password confirmation does not match.';
        }

        if ($errors) {
            set_flash('error', implode(' ', $errors));
            redirect($redirectTo);
        }

        User::updatePassword((int) $user['id'], PasswordService::make($newPassword));
        User::setPasswordChangeRequired((int) $user['id'], false);
        AuditService::record('update_password', 'users', (int) $user['id']);
        set_flash('success', 'Password changed successfully.');

        if (!empty($user['must_change_password'])) {
            redirect('dashboard');
        }

        redirect($redirectTo);
    }

    public function passwordSetup(): void
    {
        require_auth();

        $user = current_user();
        if (!$user || empty($user['must_change_password'])) {
            redirect('dashboard');
        }

        plain_view('auth/force-password-change', [
            'title' => 'Change Password',
            'user' => $user,
        ]);
    }

    public function updateProfilePhoto(): void
    {
        require_auth();
        verify_csrf();

        $redirectTo = trim($_POST['redirect_to'] ?? 'dashboard') ?: 'dashboard';
        $user = current_user();
        $file = $_FILES['profile_photo'] ?? null;
        $errors = [];

        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            $errors[] = 'Please choose a profile picture.';
        } elseif ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Profile picture upload failed.';
        } elseif ($file['size'] > app_config('profile_photo_max_size')) {
            $errors[] = 'Profile picture must not exceed 10 MB.';
        }

        $extension = '';
        if (!$errors) {
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($extension, app_config('profile_photo_extensions'), true)) {
                $errors[] = 'Profile picture must be a JPG, PNG, or WebP image.';
            } elseif (@getimagesize($file['tmp_name']) === false) {
                $errors[] = 'The selected file is not a valid image.';
            }
        }

        if ($errors) {
            set_flash('error', implode(' ', $errors));
            redirect($redirectTo);
        }

        $uploadDir = app_config('profile_photo_dir');
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        $filename = 'profile-' . (int) $user['id'] . '-' . date('YmdHis') . '-' . bin2hex(random_bytes(6)) . '.' . $extension;
        $destination = $uploadDir . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            set_flash('error', 'Could not save the profile picture.');
            redirect($redirectTo);
        }

        $oldPhoto = $user['profile_photo_path'] ?? null;
        User::updateProfilePhoto((int) $user['id'], $filename);
        AuditService::record('update_profile_photo', 'users', (int) $user['id']);

        if ($oldPhoto) {
            $oldPath = $uploadDir . '/' . basename($oldPhoto);
            if (is_file($oldPath)) {
                @unlink($oldPath);
            }
        }

        set_flash('success', 'Profile picture updated.');
        redirect($redirectTo);
    }

    public function profilePhoto(): void
    {
        require_auth();

        $user = current_user();
        $filename = $user['profile_photo_path'] ?? '';

        if ($filename === '') {
            http_response_code(404);
            return;
        }

        $file = app_config('profile_photo_dir') . '/' . basename($filename);
        if (!is_file($file)) {
            http_response_code(404);
            return;
        }

        $mime = mime_content_type($file) ?: 'image/jpeg';
        header('Content-Type: ' . $mime);
        header('Content-Length: ' . filesize($file));
        header('Cache-Control: private, max-age=3600');
        readfile($file);
    }
}
