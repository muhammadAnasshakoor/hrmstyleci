<?php

use App\Http\Controllers\API\AttendanceController;
use App\Http\Controllers\API\AttendanceReportController;
use App\Http\Controllers\API\AttendanceRosterController;
use App\Http\Controllers\API\CompanyController;
use App\Http\Controllers\API\DashboardController;
use App\Http\Controllers\API\DesignationController;
use App\Http\Controllers\API\DutyController;
use App\Http\Controllers\API\EmployeeController;
use App\Http\Controllers\API\EmployeeTransferController;
use App\Http\Controllers\API\EquipmentController;
use App\Http\Controllers\API\HolidayController;
use App\Http\Controllers\API\LeaveController;
use App\Http\Controllers\API\NotificationController;
use App\Http\Controllers\API\PermissionController;
use App\Http\Controllers\API\PolicyController;
use App\Http\Controllers\API\RegisterController;
use App\Http\Controllers\API\ResignationController;
use App\Http\Controllers\API\RoleController;
use App\Http\Controllers\API\SubscriberController;
use App\Http\Controllers\API\SubscriptionPlanController;
use App\Http\Controllers\API\TenantController;
use App\Http\Controllers\API\TenantReportController;
use App\Http\Controllers\API\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::controller(RegisterController::class)->group(function () {
    Route::post('register', 'register');
    Route::post('login', 'login');
});

Route::middleware('auth:sanctum')->group(function () {
    Route::middleware(\App\Http\Middleware\CorsMiddleware::class)->group(function () {
        Route::resource('duty', DutyController::class);
        Route::get('duty/get-rosters/{id}', [DutyController::class, 'getRosters']);
        Route::get('duty/inactive-duties', [DutyController::class, 'inactiveDuties']);
        Route::post('/duty/GetEmployee', [DutyController::class, 'GetEmployee']);
        Route::post('/duty/search-employee', [DutyController::class, 'searchEmployee']);

        Route::resource('company', CompanyController::class);
        Route::post('company/{company}', [CompanyController::class, 'update']);
        Route::get('company/inactive-companies', [CompanyController::class, 'inactiveCompanies']);

        Route::resource('tenant', TenantController::class);
        Route::Post('tenant/{tenant}', [TenantController::class, 'update']);
        Route::get('tenant/inactive-tenants', [TenantController::class, 'inactiveTenants']);

        Route::resource('employee', EmployeeController::class);
        Route::post('employee/{employee}', [EmployeeController::class, 'update']);
        Route::get('employee/inactive-employees', [EmployeeController::class, 'inactiveEmployees']);

        Route::resource('designation', DesignationController::class);
        Route::get('designation/inactive-designations', [DesignationController::class, 'inactiveDesignation']);

        Route::resource('policy', PolicyController::class);
        Route::get('policy/inactive-policies', [PolicyController::class, 'inactivePolicies']);

        Route::resource('equipment', EquipmentController::class);
        Route::get('equipment/inactive-equipments', [EquipmentController::class, 'inactiveEquipments']);

        Route::resource('permission', PermissionController::class);

        Route::resource('role', RoleController::class);

        Route::resource('resignation', ResignationController::class);
        Route::post('resignation/get-employee', [ResignationController::class, 'searchEmployee']);
        Route::get('resignation/inactive-resingnations', [ResignationController::class, 'inactiveResignations']);

        Route::resource('attendance', AttendanceController::class);
        Route::post('attendance/get-employee', [AttendanceController::class, 'getEmployee']);

        Route::resource('attendance-roster', AttendanceRosterController::class);

        Route::resource('holiday', HolidayController::class);
        Route::get('holiday/inactive-holidays', [HolidayController::class, 'inactiveHolidays']);

        Route::post('attendance-daily-report', [AttendanceReportController::class, 'dailyReport']);
        Route::post('attendance-periodic-report', [AttendanceReportController::class, 'periodicReport']);
        Route::post('attendance-periodic-report/get-employee', [AttendanceReportController::class, 'getEmployee']);

        Route::post('employee-transfer/{previous_duty}', [EmployeeTransferController::class, 'store']);
        Route::get('employee-transfer/{duty}', [EmployeeTransferController::class, 'newDutyForm']);
        Route::resource('employee-transfer', EmployeeTransferController::class);

        Route::get('/dashboard', [DashboardController::class, 'dashboard']);
        Route::post('/empoloyeetransfer/createduty', [EmployeeTransferController::class, 'createNewDuty']);

        Route::resource('tenant-report', TenantReportController::class);
        Route::get('get-tenant-report/{id}', [TenantReportController::class, 'report']);

        Route::resource('user', UserController::class);
        Route::get('user/inactive-users', [UserController::class, 'inactiveUsers']);

        Route::resource('notification', NotificationController::class);

        Route::resource('subscriber', SubscriberController::class);
        Route::get('subscriber/inactive-subscribers', [SubscriberController::class, 'inactiveSubscribers']);

        Route::resource('subscription-plan', SubscriptionPlanController::class);
        Route::get('subscription-plan/inactive-subscription-plans', [SubscriptionPlanController::class, 'inactiveSubscriptionPlans']);

        Route::resource('leave', LeaveController::class);
        Route::post('leave/filter-leaves', [LeaveController::class, 'filterLeaves']);
        Route::post('leave/update-status/{leave}', [LeaveController::class, 'updateStatus']);
    });
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
// Route::post('/give_role', [UserController::class, 'assignrole']);
