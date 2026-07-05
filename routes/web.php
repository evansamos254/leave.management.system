<?php

$route = route_name();

try {
    match ($route) {
        'login' => is_post()
            ? (new AuthController())->login()
            : (new AuthController())->showLogin(),
        'login/otp' => is_post()
            ? (new AuthController())->verifyOtp()
            : (new AuthController())->showOtp(),
        'login/otp/resend' => (new AuthController())->resendOtp(),
        'login/otp/cancel' => (new AuthController())->cancelOtp(),
        'forgot-password' => is_post()
            ? (new AuthController())->forgotPassword()
            : (new AuthController())->showForgotPassword(),
        'register' => is_post()
            ? (new AuthController())->register()
            : (new AuthController())->showRegister(),
        'logout' => (new AuthController())->logout(),
        'dashboard' => (new DashboardController())->index(),
        'notifications/read' => (new DashboardController())->markNotificationsRead(),
        'profile/photo' => (new DashboardController())->profilePhoto(),
        'profile/photo/update' => is_post()
            ? (new DashboardController())->updateProfilePhoto()
            : (new DashboardController())->index(),
        'profile/update' => is_post()
            ? (new DashboardController())->updateProfile()
            : (new DashboardController())->index(),
        'profile/password/setup' => (new DashboardController())->passwordSetup(),
        'profile/password' => is_post()
            ? (new DashboardController())->updatePassword()
            : (new DashboardController())->index(),
        'leave/apply' => is_post()
            ? (new LeaveController())->store()
            : (new LeaveController())->apply(),
        'leave/edit' => is_post()
            ? (new LeaveController())->update()
            : (new LeaveController())->edit(),
        'leave/history' => (new LeaveController())->history(),
        'leave/view' => (new LeaveController())->view(),
        'leave/pdf' => (new LeaveController())->pdf(),
        'leave/calendar' => (new CalendarController())->index(),
        'leave/cancel' => (new LeaveController())->cancel(),
        'leave/resume' => is_post()
            ? (new LeaveController())->markResumed()
            : (new LeaveController())->history(),
        'leave/forfeit' => is_post()
            ? (new LeaveController())->forfeit()
            : (new LeaveController())->history(),
        'leave/attachment' => (new LeaveController())->attachment(),
        'leave/passport-photo' => (new LeaveController())->passportPhoto(),
        'approvals' => (new ApprovalController())->index(),
        'approvals/action' => (new ApprovalController())->action(),
        'workers' => (new AdminController())->workers(),
        'workers/create' => is_post()
            ? (new AdminController())->storeWorker()
            : (new AdminController())->createWorker(),
        'admin/account-requests' => (new AdminController())->accountRequests(),
        'admin/account-requests/view' => (new AdminController())->accountRequestView(),
        'admin/account-requests/action' => (new AdminController())->accountRequestAction(),
        'admin/account-requests/document' => (new AdminController())->accountRequestDocument(),
        'admin/activity' => (new AdminController())->activity(),
        'admin/leave-requests' => (new AdminController())->leaveRequests(),
        'admin/users' => (new AdminController())->users(),
        'admin/users/history' => (new AdminController())->userHistory(),
        'admin/users/edit' => is_post()
            ? (new AdminController())->updateUserProfile()
            : (new AdminController())->editUser(),
        'admin/users/toggle-status' => is_post()
            ? (new AdminController())->toggleUserStatus()
            : (new AdminController())->users(),
        'admin/users/update' => (new AdminController())->updateUser(),
        'admin/users/reset-password' => is_post()
            ? (new AdminController())->resetUserPassword()
            : (new AdminController())->users(),
        'admin/users/delete' => is_post()
            ? (new AdminController())->deleteUser()
            : (new AdminController())->users(),
        'admin/leave-types' => is_post()
            ? (new AdminController())->saveLeaveType()
            : (new AdminController())->leaveTypes(),
        'admin/holidays' => is_post()
            ? (new AdminController())->saveHoliday()
            : (new AdminController())->holidays(),
        'admin/holidays/sync' => is_post()
            ? (new AdminController())->syncHolidays()
            : (new AdminController())->holidays(),
        'admin/holidays/delete' => (new AdminController())->deleteHoliday(),
        'reports' => (new ReportsController())->index(),
        'reports/csv' => (new ReportsController())->csv(),
        'reports/pdf' => (new ReportsController())->pdf(),
        default => (function (): void {
            http_response_code(404);
            view('error', [
                'title' => 'Page not found',
                'message' => 'The page you requested does not exist.',
            ]);
        })(),
    };
} catch (Throwable $throwable) {
    app_log($throwable);
    http_response_code(500);
    view('error', [
        'title' => 'System error',
        'message' => 'Something went wrong. Check storage/logs/app.log for details.',
    ]);
}
