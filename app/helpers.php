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

function old(string $key, string $default = ''): string
{
    return (string) ($_SESSION['old'][$key] ?? $default);
}

function build_full_name(?string $firstName, ?string $lastName): string
{
    return trim(preg_replace('/\s+/', ' ', trim((string) $firstName) . ' ' . trim((string) $lastName)) ?? '');
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

function role_label(string $role): string
{
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
    return $date ? date('d M Y', strtotime($date)) : '-';
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

function app_log(Throwable $throwable): void
{
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $throwable->getMessage() . PHP_EOL . $throwable->getTraceAsString() . PHP_EOL;
    file_put_contents(dirname(__DIR__) . '/storage/logs/app.log', $line, FILE_APPEND);
}
