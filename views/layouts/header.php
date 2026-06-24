<?php
$pageTitle = $title ?? app_config('name');
$authUser = auth_check() ? current_user() : null;
$unreadNotifications = $authUser ? NotificationService::unreadCount((int) $authUser['id']) : 0;
$activeRoute = route_name();
$profilePhotoUrl = $authUser && !empty($authUser['profile_photo_path'])
    ? url('profile/photo') . '&v=' . urlencode((string) ($authUser['updated_at'] ?? time()))
    : null;
$authNameParts = $authUser ? name_parts($authUser['full_name'] ?? '') : ['first_name' => '', 'last_name' => ''];
$isAuthHrOffice = $authUser && ($authUser['role'] ?? '') === 'hr' && empty($authUser['department_name']);
$authDirectorateLabel = $isAuthHrOffice ? 'HR Office' : ($authUser['directorate_name'] ?? 'Not assigned');
$authDepartmentLabel = $isAuthHrOffice ? 'Office-level account' : ($authUser['department_name'] ?? 'Not assigned');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> | <?= e(app_config('name')) ?></title>
    <link rel="stylesheet" href="<?= e(asset('css/styles.css')) ?>">
</head>
<body>
<div class="app-shell">
    <aside class="sidebar" id="site-navigation" data-mobile-sidebar>
        <div class="sidebar-header">
            <a class="brand" href="<?= e(url('dashboard')) ?>">
                <span class="brand-mark">BC</span>
                <span>
                    <strong>Busia County</strong>
                    <small>Staff Online Leave</small>
                </span>
            </a>
            <button class="sidebar-close" type="button" aria-label="Close dashboard menu" data-menu-close>
                <svg aria-hidden="true" viewBox="0 0 24 24" focusable="false">
                    <path d="M18 6 6 18"></path>
                    <path d="m6 6 12 12"></path>
                </svg>
            </button>
        </div>

        <nav class="nav">
            <a class="<?= $activeRoute === 'dashboard' ? 'active' : '' ?>" href="<?= e(url('dashboard')) ?>">Dashboard</a>
            <?php if ($authUser && $authUser['employee_id']): ?>
                <a class="<?= $activeRoute === 'leave/apply' ? 'active' : '' ?>" href="<?= e(url('leave/apply')) ?>">Apply Leave</a>
                <a class="<?= $activeRoute === 'leave/history' ? 'active' : '' ?>" href="<?= e(url('leave/history')) ?>">My Leave History</a>
            <?php endif; ?>
            <?php if ($authUser && in_array($authUser['role'], ['admin', 'supervisor'], true)): ?>
                <a class="<?= $activeRoute === 'approvals' ? 'active' : '' ?>" href="<?= e(url('approvals')) ?>"><?= $authUser['role'] === 'admin' ? 'Approval Progress' : 'Approvals' ?></a>
            <?php endif; ?>
            <?php if ($authUser && in_array($authUser['role'], ['admin', 'supervisor', 'hr', 'director'], true)): ?>
                <a class="<?= $activeRoute === 'leave/calendar' ? 'active' : '' ?>" href="<?= e(url('leave/calendar')) ?>">Leave Calendar</a>
                <a class="<?= $activeRoute === 'reports' ? 'active' : '' ?>" href="<?= e(url('reports')) ?>">Reports</a>
            <?php endif; ?>
            <?php if ($authUser && in_array($authUser['role'], ['admin', 'hr'], true)): ?>
                <a class="<?= in_array($activeRoute, ['workers', 'workers/create'], true) ? 'active' : '' ?>" href="<?= e(url('workers')) ?>">Staff</a>
            <?php endif; ?>
            <?php if ($authUser && in_array($authUser['role'], ['admin', 'supervisor', 'hr', 'director'], true)): ?>
                <a class="<?= $activeRoute === 'admin/users' ? 'active' : '' ?>" href="<?= e(url('admin/users')) ?>">User Managements</a>
                <a class="<?= str_starts_with($activeRoute, 'admin/account-requests') ? 'active' : '' ?>" href="<?= e(url('admin/account-requests')) ?>">Account Requests</a>
                <a class="<?= $activeRoute === 'admin/leave-requests' ? 'active' : '' ?>" href="<?= e(url('admin/leave-requests')) ?>">All Requests</a>
            <?php endif; ?>
            <?php if ($authUser && in_array($authUser['role'], ['admin', 'hr'], true)): ?>
                <a class="<?= $activeRoute === 'admin/activity' ? 'active' : '' ?>" href="<?= e(url('admin/activity')) ?>">System Activity</a>
            <?php endif; ?>
            <?php if ($authUser && $authUser['role'] === 'admin'): ?>
                <a class="<?= $activeRoute === 'admin/leave-types' ? 'active' : '' ?>" href="<?= e(url('admin/leave-types')) ?>">Leave Types</a>
                <a class="<?= $activeRoute === 'admin/holidays' ? 'active' : '' ?>" href="<?= e(url('admin/holidays')) ?>">Holidays</a>
            <?php endif; ?>
        </nav>
    </aside>
    <button class="sidebar-overlay" type="button" aria-label="Close dashboard menu" data-menu-overlay hidden></button>

    <main class="main">
        <header class="topbar">
            <div class="topbar-title">
                <button class="menu-toggle" type="button" aria-label="Open dashboard menu" aria-controls="site-navigation" aria-expanded="false" data-menu-toggle>
                    <svg aria-hidden="true" viewBox="0 0 24 24" focusable="false">
                        <path d="M4 6h16"></path>
                        <path d="M4 12h16"></path>
                        <path d="M4 18h16"></path>
                    </svg>
                </button>
                <div>
                    <p class="eyebrow"><?= e(role_label($authUser['role'] ?? 'guest')) ?></p>
                    <h1><?= e($pageTitle) ?></h1>
                </div>
            </div>
            <?php if ($authUser): ?>
                <div class="topbar-actions">
                    <span class="notification-pill"><?= $unreadNotifications ?> unread</span>
                    <div class="account-menu-wrap">
                        <button class="account-icon-btn" type="button" aria-label="Open account profile" aria-expanded="false" aria-controls="account-profile-menu" data-account-toggle>
                            <?php if ($profilePhotoUrl): ?>
                                <img src="<?= e($profilePhotoUrl) ?>" alt="">
                            <?php else: ?>
                                <svg aria-hidden="true" viewBox="0 0 24 24" focusable="false">
                                    <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4Z"></path>
                                    <path d="M20 21a8 8 0 0 0-16 0"></path>
                                </svg>
                            <?php endif; ?>
                        </button>
                        <div class="account-popover" id="account-profile-menu" hidden>
                            <div class="account-popover-heading">
                                <h2>Profile Summary</h2>
                                <span class="badge"><?= e(role_label($authUser['role'])) ?></span>
                            </div>
                            <div class="profile-photo-panel">
                                <div class="profile-photo-preview">
                                    <?php if ($profilePhotoUrl): ?>
                                        <img src="<?= e($profilePhotoUrl) ?>" alt="">
                                    <?php else: ?>
                                        <span><?= e(strtoupper(substr($authUser['full_name'], 0, 1))) ?></span>
                                    <?php endif; ?>
                                </div>
                                <form class="profile-photo-form" method="post" action="<?= e(url('profile/photo/update')) ?>" enctype="multipart/form-data">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="redirect_to" value="<?= e($activeRoute) ?>">
                                    <input type="file" name="profile_photo" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" required>
                                    <button class="btn btn-small btn-primary" type="submit">Upload Photo</button>
                                </form>
                            </div>
                            <details class="profile-editor">
                                <summary>Edit Information</summary>
                                <form class="profile-editor-form" method="post" action="<?= e(url('profile/update')) ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="redirect_to" value="<?= e($activeRoute) ?>">
                                    <label>
                                        <span>First name</span>
                                        <input type="text" name="first_name" value="<?= e($authNameParts['first_name']) ?>" required>
                                    </label>
                                    <label>
                                        <span>Last name</span>
                                        <input type="text" name="last_name" value="<?= e($authNameParts['last_name']) ?>" required>
                                    </label>
                                    <label>
                                        <span>Email address</span>
                                        <input type="email" name="email" value="<?= e($authUser['email']) ?>" required>
                                    </label>
                                    <label>
                                        <span>National ID</span>
                                        <input type="text" name="national_id" value="<?= e($authUser['national_id'] ?? '') ?>">
                                    </label>
                                    <label>
                                        <span>Gender</span>
                                        <select name="gender" required>
                                            <option value="">Select gender</option>
                                            <?php foreach (gender_options() as $value => $label): ?>
                                                <option value="<?= e($value) ?>" <?= ($authUser['gender'] ?? '') === $value ? 'selected' : '' ?>>
                                                    <?= e($label) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </label>
                                    <label>
                                        <span>Phone number</span>
                                        <input type="text" name="phone" value="<?= e($authUser['phone'] ?? '') ?>">
                                    </label>
                                    <button class="btn btn-small btn-primary" type="submit">Save Profile</button>
                                </form>
                            </details>
                            <details class="profile-editor">
                                <summary>Change Password</summary>
                                <form class="profile-editor-form" method="post" action="<?= e(url('profile/password')) ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="redirect_to" value="<?= e($activeRoute) ?>">
                                    <label>
                                        <span>Current password</span>
                                        <input type="password" name="current_password" required autocomplete="current-password">
                                    </label>
                                    <label>
                                        <span>New password</span>
                                        <input type="password" name="password" required minlength="6" autocomplete="new-password">
                                    </label>
                                    <label>
                                        <span>Confirm password</span>
                                        <input type="password" name="password_confirmation" required minlength="6" autocomplete="new-password">
                                    </label>
                                    <button class="btn btn-small btn-primary" type="submit">Change Password</button>
                                </form>
                            </details>
                            <div class="account-summary">
                                <div>
                                    <span>Name</span>
                                    <strong><?= e($authUser['full_name']) ?></strong>
                                </div>
                                <div>
                                    <span>Email</span>
                                    <strong><?= e($authUser['email']) ?></strong>
                                </div>
                                <div>
                                    <span>National ID</span>
                                    <strong><?= e($authUser['national_id'] ?? 'N/A') ?></strong>
                                </div>
                                <div>
                                    <span>Gender</span>
                                    <strong><?= e(gender_label($authUser['gender'] ?? null)) ?></strong>
                                </div>
                                <div>
                                    <span>Phone</span>
                                    <strong><?= e($authUser['phone'] ?? 'N/A') ?></strong>
                                </div>
                                <div>
                                    <span>Payroll / ID number</span>
                                    <strong><?= e($authUser['staff_id'] ?? 'N/A') ?></strong>
                                </div>
                                <div>
                                    <span>Department</span>
                                    <strong><?= e($authDirectorateLabel) ?></strong>
                                </div>
                                <div>
                                    <span>Directorate</span>
                                    <strong><?= e($authDepartmentLabel) ?></strong>
                                </div>
                                <div>
                                    <span>Designation</span>
                                    <strong><?= e($authUser['designation'] ?? 'N/A') ?></strong>
                                </div>
                            </div>
                            <form class="account-logout-form" method="post" action="<?= e(url('logout')) ?>">
                                <?= csrf_field() ?>
                                <button class="btn btn-ghost btn-block" type="submit">Logout</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </header>

        <?php if ($message = flash('success')): ?>
            <div class="alert alert-success"><?= e($message) ?></div>
        <?php endif; ?>

        <?php if ($message = flash('error')): ?>
            <div class="alert alert-error"><?= e($message) ?></div>
        <?php endif; ?>
