<?php

function app_config(?string $key = null, mixed $default = null): mixed
{
    static $config = null;

    if ($config === null) {
        $config = require dirname(__DIR__) . '/config/app.php';
    }

    if ($key === null) {
        return $config;
    }

    return $config[$key] ?? $default;
}

function db(): PDO
{
    return Database::connection();
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function route_name(): string
{
    $route = $_GET['route'] ?? '';
    $route = trim((string) $route, '/');

    return $route === '' ? 'dashboard' : $route;
}

function url(string $path = ''): string
{
    $path = trim($path, '/');
    $base = rtrim(app_config('base_url', ''), '/');
    $prefix = $base === '' ? '' : $base;
    $frontController = $prefix === '' ? 'index.php' : $prefix . '/index.php';

    return $frontController . ($path === '' ? '' : '?route=' . urlencode($path));
}

function absolute_url(string $path = ''): string
{
    $link = url($path);
    if (preg_match('#^https?://#i', $link)) {
        return $link;
    }

    $base = rtrim((string) (app_config('base_url', '') ?: getenv('APP_URL') ?: ''), '/');
    if ($base !== '') {
        return $base . '/' . ltrim($link, '/');
    }

    $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '';
    if ($host === '') {
        return $link;
    }

    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $scriptDir = trim(str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? ''))), '/.');
    $prefix = $scheme . '://' . $host;
    if ($scriptDir !== '') {
        $prefix .= '/' . $scriptDir;
    }

    return $prefix . '/' . ltrim($link, '/');
}

function asset(string $path): string
{
    $base = rtrim(app_config('base_url', ''), '/');
    $prefix = $base === '' ? 'assets' : $base . '/assets';

    return $prefix . '/' . ltrim($path, '/');
}

function redirect(string $path): never
{
    header('Location: ' . url($path));
    exit;
}

function no_cache_headers(): void
{
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0, post-check=0, pre-check=0');
    header('Pragma: no-cache');
    header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
}

function view(string $view, array $data = []): void
{
    extract($data, EXTR_SKIP);
    require dirname(__DIR__) . '/views/layouts/header.php';
    require dirname(__DIR__) . '/views/' . $view . '.php';
    require dirname(__DIR__) . '/views/layouts/footer.php';
}

function plain_view(string $view, array $data = []): void
{
    extract($data, EXTR_SKIP);
    require dirname(__DIR__) . '/views/' . $view . '.php';
}

function flash(string $key): ?string
{
    if (!isset($_SESSION['flash'][$key])) {
        return null;
    }

    $message = $_SESSION['flash'][$key];
    unset($_SESSION['flash'][$key]);

    return $message;
}

function set_flash(string $key, string $message): void
{
    $_SESSION['flash'][$key] = $message;
}

function remember_form_state(array $old = [], array $errors = []): void
{
    $_SESSION['old'] = $old;
    $_SESSION['errors'] = $errors;
}

function old(string $key, string $default = ''): string
{
    return (string) ($_SESSION['old'][$key] ?? $default);
}

function field_error(string $key): ?string
{
    $errors = $_SESSION['errors'][$key] ?? null;

    if ($errors === null) {
        return null;
    }

    if (is_array($errors)) {
        $errors = implode(' ', array_filter(array_map(static fn ($value) => trim((string) $value), $errors)));
    }

    $errors = trim((string) $errors);

    return $errors === '' ? null : $errors;
}

function has_field_error(string $key): bool
{
    return field_error($key) !== null;
}

function build_full_name(?string $firstName, ?string $lastName): string
{
    return trim(preg_replace('/\s+/', ' ', trim((string) $firstName) . ' ' . trim((string) $lastName)) ?? '');
}

function normalize_kenyan_phone_number(?string $phone): ?string
{
    $phone = trim((string) $phone);
    if ($phone === '') {
        return null;
    }

    $digits = preg_replace('/\D+/', '', $phone);
    if ($digits === null || $digits === '') {
        return null;
    }

    if (str_starts_with($digits, '254')) {
        $digits = substr($digits, 3);
    } elseif (str_starts_with($digits, '0')) {
        $digits = substr($digits, 1);
    }

    if (!preg_match('/^(?:7|1)\d{8}$/', $digits)) {
        return null;
    }

    return '+254' . $digits;
}

function is_valid_kenyan_phone_number(?string $phone): bool
{
    return normalize_kenyan_phone_number($phone) !== null;
}

function format_kenyan_phone_number(?string $phone): string
{
    $normalized = normalize_kenyan_phone_number($phone);

    if ($normalized === null) {
        return trim((string) $phone);
    }

    $subscriber = substr($normalized, 4);

    return '+254 ' . substr($subscriber, 0, 3) . ' ' . substr($subscriber, 3, 3) . ' ' . substr($subscriber, 6);
}

function kenyan_phone_number_error(?string $phone, string $label = 'Phone number'): ?string
{
    $phone = trim((string) $phone);

    if ($phone === '') {
        return null;
    }

    return is_valid_kenyan_phone_number($phone)
        ? null
        : $label . ' must be a Kenyan mobile number, for example +254 700 000 000.';
}

function job_group_options(): array
{
    $groups = range('E', 'R');

    return array_combine($groups, $groups) ?: [];
}

function is_valid_job_group(?string $jobGroup): bool
{
    $jobGroup = strtoupper(trim((string) $jobGroup));

    if ($jobGroup === '') {
        return false;
    }

    if (array_key_exists($jobGroup, job_group_options())) {
        return true;
    }

    return preg_match('/^[A-Z0-9][A-Z0-9\s\-\/&().]{0,39}$/', $jobGroup) === 1;
}

function normalize_job_group(?string $jobGroup): ?string
{
    $jobGroup = strtoupper(trim(preg_replace('/\s+/', ' ', (string) $jobGroup) ?? ''));

    return is_valid_job_group($jobGroup) ? $jobGroup : null;
}

function name_parts(?string $fullName): array
{
    $name = trim(preg_replace('/\s+/', ' ', (string) $fullName) ?? '');
    if ($name === '') {
        return ['first_name' => '', 'last_name' => ''];
    }

    $parts = explode(' ', $name);
    $firstName = array_shift($parts) ?? '';

    return [
        'first_name' => $firstName,
        'last_name' => implode(' ', $parts),
    ];
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $token = $_POST['csrf_token'] ?? '';

    if (!is_string($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        set_flash('error', 'Security token mismatch. Please try again.');
        redirect('login');
    }
}

function current_user(): ?array
{
    static $loadedUserId = null;
    static $loadedUser = null;

    if (empty($_SESSION['user_id'])) {
        $loadedUserId = null;
        $loadedUser = null;
        return null;
    }

    $userId = (int) $_SESSION['user_id'];
    if ($loadedUserId !== $userId) {
        $loadedUserId = $userId;
        $loadedUser = User::find($userId);

        if ($loadedUser !== null && ($loadedUser['status'] ?? 'active') !== 'active') {
            clear_auth_session();
            $loadedUserId = null;
            $loadedUser = null;
            return null;
        }
    }

    return $loadedUser;
}

function auth_check(): bool
{
    return current_user() !== null;
}

function auth_session_expired(): bool
{
    if (empty($_SESSION['user_id']) || empty($_SESSION['last_activity_at'])) {
        return false;
    }

    $security = app_config('security', []);
    $timeoutMinutes = (int) ($security['session_timeout_minutes'] ?? 30);
    if ($timeoutMinutes < 1) {
        return false;
    }

    return (time() - (int) $_SESSION['last_activity_at']) > ($timeoutMinutes * 60);
}

function clear_auth_session(): void
{
    unset($_SESSION['user_id'], $_SESSION['last_activity_at']);
    session_regenerate_id(true);
}

function require_auth(): void
{
    no_cache_headers();

    if (auth_session_expired()) {
        clear_auth_session();
        set_flash('error', 'Your session expired because of inactivity. Please log in again.');
        redirect('login');
    }

    if (!auth_check()) {
        set_flash('error', 'Please log in to continue.');
        redirect('login');
    }

    $user = current_user();
    if ($user && !empty($user['must_change_password'])) {
        $route = route_name();
        $allowedRoutes = ['profile/password/setup', 'profile/password', 'logout'];

        if (!in_array($route, $allowedRoutes, true)) {
            redirect('profile/password/setup');
        }
    }

    $_SESSION['last_activity_at'] = time();
}

function require_guest(): void
{
    no_cache_headers();

    if (auth_session_expired()) {
        clear_auth_session();
    }

    if (auth_check()) {
        redirect('dashboard');
    }
}

function require_role(array|string $roles): void
{
    require_auth();

    $roles = is_array($roles) ? $roles : [$roles];
    $user = current_user();

    if (!$user || !in_array($user['role'], $roles, true)) {
        http_response_code(403);
        view('error', [
            'title' => 'Access denied',
            'message' => 'You do not have permission to access this page.',
        ]);
        exit;
    }
}

function status_label(string $status): string
{
    return str_replace(['Hr', 'Ict'], ['HR', 'ICT'], ucwords(str_replace('_', ' ', $status)));
}

function audit_action_label(string $action): string
{
    $labels = [
        'login' => 'Logged in',
        'logout' => 'Logged out',
        'failed_login' => 'Failed login attempt',
        'request_account' => 'Requested account',
        'forgot_password_reset' => 'Reset password request',
        'update_profile' => 'Updated profile',
        'update_password' => 'Changed password',
        'update_profile_photo' => 'Updated profile photo',
        'create_leave_request' => 'Submitted leave request',
        'update_leave_request' => 'Updated leave request',
        'cancel_leave_request' => 'Cancelled leave request',
        'approve_leave_request' => 'Approved leave request',
        'reject_leave_request' => 'Rejected leave request',
        'mark_resumed' => 'Marked resumed duty',
        'recall_leave_request' => 'Recalled leave request',
        'record_leave_forfeiture' => 'Recorded leave forfeiture',
        'create_worker' => 'Created staff account',
        'update_user_access' => 'Updated user access',
        'update_staff_profile' => 'Updated staff profile',
        'admin_reset_staff_password' => 'Reset staff password',
        'deactivate_staff_account' => 'Deactivated staff account',
        'reactivate_staff_account' => 'Reactivated staff account',
        'delete_staff_account' => 'Deleted staff account',
        'approve_account_request' => 'Approved account request',
        'reject_account_request' => 'Rejected account request',
        'save_leave_type' => 'Saved leave type',
        'save_holiday' => 'Saved holiday',
        'sync_kenya_holidays' => 'Synced public holidays',
        'delete_holiday' => 'Deleted holiday',
    ];

    if (isset($labels[$action])) {
        return $labels[$action];
    }

    return str_replace('_', ' ', ucwords($action, '_'));
}

function status_badge_class(string $status): string
{
    if (str_starts_with($status, 'pending_')) {
        return 'warning';
    }

    return match ($status) {
        'approved' => 'success',
        'recalled' => 'warning',
        'rejected', 'cancelled', 'forfeited' => 'danger',
        default => $status,
    };
}

function role_label(string $role): string
{
    if ($role === 'waziri') {
        return 'Waziri';
    }

    if ($role === 'employee') {
        return 'Staff';
    }

    return str_replace(['Hr', 'Ict'], ['HR', 'ICT'], ucwords(str_replace('_', ' ', $role)));
}

function gender_options(): array
{
    return [
        'male' => 'Male',
        'female' => 'Female',
    ];
}

function gender_label(?string $gender): string
{
    return gender_options()[$gender ?? ''] ?? 'Not set';
}

function designation_label(?string $designation, ?string $role = null): string
{
    if ($role === 'admin') {
        return 'Admin';
    }

    if ($role === 'waziri') {
        return 'Waziri';
    }

    $designation = trim((string) $designation);
    if ($designation === '') {
        return 'N/A';
    }

    if (strcasecmp($designation, 'admin/director') === 0) {
        return 'Admin';
    }

    return $designation;
}

function designation_form_value(?string $designation, ?string $role = null): string
{
    if ($role === 'admin') {
        return 'Admin';
    }

    if ($role === 'waziri') {
        return 'Waziri';
    }

    $designation = trim((string) $designation);
    if ($designation === '') {
        return '';
    }

    if (strcasecmp($designation, 'admin/director') === 0) {
        return 'Admin';
    }

    return $designation;
}

function financial_year_config(): array
{
    $config = app_config('financial_year', []);

    return [
        'start_month' => max(1, min(12, (int) ($config['start_month'] ?? 7))),
        'start_day' => max(1, min(31, (int) ($config['start_day'] ?? 1))),
    ];
}

function financial_year_key(?string $date = null): int
{
    $date = $date && trim($date) !== '' ? $date : date('Y-m-d');
    $parsed = DateTime::createFromFormat('Y-m-d', $date);
    if (!$parsed || $parsed->format('Y-m-d') !== $date) {
        $parsed = new DateTime($date);
    }

    $config = financial_year_config();
    $year = (int) $parsed->format('Y');
    $month = (int) $parsed->format('n');
    $day = (int) $parsed->format('j');

    if ($month < $config['start_month'] || ($month === $config['start_month'] && $day < $config['start_day'])) {
        return $year - 1;
    }

    return $year;
}

function financial_year_label(?string $date = null): string
{
    $startYear = financial_year_key($date);

    return $startYear . '/' . ($startYear + 1);
}

function leave_gender_options(): array
{
    return [
        'any' => 'All staffs',
        'male' => 'Male staffs only',
        'female' => 'Female staffs only',
    ];
}

function leave_gender_label(?string $eligibility): string
{
    return leave_gender_options()[$eligibility ?? 'any'] ?? 'All staffs';
}

function format_date(?string $date): string
{
    $date = trim((string) $date);
    if ($date === '' || preg_match('/^0{4}-0{2}-0{2}(?:\s0{2}:0{2}:0{2})?$/', $date) === 1) {
        return '-';
    }

    $timestamp = strtotime($date);
    if ($timestamp === false || $timestamp <= 0) {
        return '-';
    }

    return date('d M Y', $timestamp);
}

function has_official_leave_recall(array $request): bool
{
    $recalledAt = trim((string) ($request['recalled_at'] ?? ''));
    if ($recalledAt === '' || preg_match('/^0{4}-0{2}-0{2}(?:\s0{2}:0{2}:0{2})?$/', $recalledAt) === 1) {
        return false;
    }

    return !empty($request['recalled_by_user_id'])
        || trim((string) ($request['recall_reason'] ?? '')) !== ''
        || trim((string) ($request['recall_attachment_path'] ?? '')) !== '';
}

function is_valid_past_or_today_date(string $date): bool
{
    $parsed = DateTime::createFromFormat('Y-m-d', $date);
    if (!$parsed || $parsed->format('Y-m-d') !== $date) {
        return false;
    }

    return $parsed <= new DateTime('today');
}

function is_valid_today_or_future_date(string $date): bool
{
    $parsed = DateTime::createFromFormat('Y-m-d', $date);
    if (!$parsed || $parsed->format('Y-m-d') !== $date) {
        return false;
    }

    return $parsed >= new DateTime('today');
}

function uploaded_file_is_pdf(array $file): bool
{
    $extension = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
    if ($extension !== 'pdf') {
        return false;
    }

    $tmpName = (string) ($file['tmp_name'] ?? '');
    if ($tmpName === '' || !is_file($tmpName) || !is_readable($tmpName)) {
        return false;
    }

    $handle = @fopen($tmpName, 'rb');
    if (!$handle) {
        return false;
    }

    $header = fread($handle, 5);
    fclose($handle);

    return $header === '%PDF-';
}

function uploaded_file_is_image(array $file): bool
{
    $extension = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
    if (!in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
        return false;
    }

    $tmpName = (string) ($file['tmp_name'] ?? '');
    if ($tmpName === '' || !is_file($tmpName) || !is_readable($tmpName)) {
        return false;
    }

    $size = @getimagesize($tmpName);
    if (!$size) {
        return false;
    }

    return in_array(($size['mime'] ?? ''), ['image/jpeg', 'image/png', 'image/webp'], true);
}

function format_days(mixed $value, string $default = '-'): string
{
    if ($value === null || $value === '') {
        return $default;
    }

    if (!is_numeric($value)) {
        return (string) $value;
    }

    return number_format((float) $value, 0, '.', ',');
}

function format_currency(mixed $value, string $currency = 'KSh', string $default = '-'): string
{
    if ($value === null || $value === '') {
        return $default;
    }

    if (!is_numeric($value)) {
        return (string) $value;
    }

    return $currency . ' ' . number_format((float) $value, 2, '.', ',');
}

function app_log(Throwable $throwable): void
{
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $throwable->getMessage() . PHP_EOL . $throwable->getTraceAsString() . PHP_EOL;
    $logDir = dirname(__DIR__) . '/storage/logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0775, true);
    }

    if (is_dir($logDir) && is_writable($logDir)) {
        @file_put_contents($logDir . '/app.log', $line, FILE_APPEND | LOCK_EX);
    }
}
