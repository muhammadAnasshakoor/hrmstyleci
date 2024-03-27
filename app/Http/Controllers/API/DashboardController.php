<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\AttendanceReport;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * @OA\Tag(
 *     name="Dashboard",
 *     description="Handles the report of the tenant"
 * )
 */
class DashboardController extends Controller
{
    /**
     * @OA\Get(
     *      path="/api/dashboard",
     *      summary="Get the tenant report.Permission required = dashboard.list",
     *      description="This endpoint retrieves all the companies and their duties.",
     *      tags={"Dashboard"},
     *      @OA\Response(response="200", description="Successful operation"),
     *      @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function __construct()
    {
        // Apply middleware to all methods in the controller
        $this->middleware('checkPermission:dashboard.list')->only('dashboard');
    }
    public function dashboard()
    {

        $current_date = now()->format('Y-m-d');
        $user = auth::user();

        if ($user->tenant) {
            $tenant = $user->tenant;
            $tenant_id = $tenant->id;

            if (!($tenant)) {
                return response()->json([
                    'message' => 'Oops! no tenant could be found'
                ]);
            }
            $total_companies = $tenant->companies()->count();
            $active_companies = $tenant->companies()
                ->where('status', '1')
                ->count();
            $inactive_companies = $tenant->companies()
                ->where('status', '0')
                ->count();

            $total_employees = $tenant->employees()->count();
            $total_employees_data = $tenant->employees()->get();
            $working_employees = $total_employees_data->filter(function ($employee) {
                return $employee->duties()
                    ->where('status', '1')
                    ->count() > 0;
            })->count();

            $reserved_employees = $total_employees - $working_employees;

            //calculating the attendance
            $absent_employees = AttendanceReport::whereDate('date', $current_date)
                ->where('tenant_id', $tenant_id)
                ->where('type', 'absent')
                ->count();
            $present_employees = AttendanceReport::whereDate('date', $current_date)
                ->where('tenant_id', $tenant_id)
                ->where('type', 'present')
                ->count();
            $late_employees = AttendanceReport::whereDate('date', $current_date)
                ->where('tenant_id', $tenant_id)
                ->where('type', 'late')
                ->count();
            $leave_employees = AttendanceReport::whereDate('date', $current_date)
                ->where('tenant_id', $tenant_id)
                ->where('type', 'leave')
                ->count();

            return response()->json([
                'data' => [
                    'user' => $user,
                    'Total companies' => $total_companies,
                    'Active companies' => $active_companies,
                    'Inactive Companies' => $inactive_companies,
                    'Total Employees' =>  $total_employees,
                    'working employee' => $working_employees,
                    'reserved employees' => $reserved_employees,
                    'Present Employees' => $present_employees,
                    'Absent Employees' => $absent_employees,
                    'Late Employees' => $late_employees,
                    'leave Employees' => $leave_employees,
                ]
            ], 200);
        }
        if ($user->company) {
            $company = $user->company;


            $active_duties = $company->duties()->where('status', '1')->count();
            $inactive_duties = $company->duties()->where('status', '0')->count();
            // fetching the attendances of all employees
            $company_id = $user->company->id;
            $current_date = now()->format('Y-m-d');
            $absent_employees = AttendanceReport::whereDate('date', $current_date)
                ->where('company_id', $company_id)
                ->where('type', 'absent')
                ->count();
            $present_employees = AttendanceReport::whereDate('date', $current_date)
                ->where('company_id', $company_id)
                ->where('type', 'present')
                ->count();
            $late_employees = AttendanceReport::whereDate('date', $current_date)
                ->where('company_id', $company_id)
                ->where('type', 'late')
                ->count();
            $leave_employees = AttendanceReport::whereDate('date', $current_date)
                ->where('company_id', $company_id)
                ->where('type', 'leave')
                ->count();
            return response()->json([
                'message' => 'Data fetched successfully',
                'Active Duties' => $active_duties,
                'Inactive Duties' => $inactive_duties,
                'Present Employees' => $present_employees,
                'Absent Employees' => $absent_employees,
                'Late Employees' => $late_employees,
                'leave Employees' => $leave_employees,

            ]);
        }
        if ($user->employee) {
            $employee = $user->employee;
            $employee_id = $employee->id;
            $start_month = now()->startOfMonth(); // Get the first day of the current month
            $end_month = now()->endOfMonth(); // Get the last day of the current month
            $attendances = AttendanceReport::whereBetween('date', [$start_month, $end_month])
                ->where('employee_id', $employee_id)
                ->count();
            $present_attendances = AttendanceReport::whereBetween('date', [$start_month, $end_month])
                ->where('employee_id', $employee_id)
                ->where('type', 'present')
                ->count();
            $absent_attendances = AttendanceReport::whereBetween('date', [$start_month, $end_month])
                ->where('employee_id', $employee_id)
                ->where('type', 'absent')
                ->count();
            $late_attendances = AttendanceReport::whereBetween('date', [$start_month, $end_month])
                ->where('employee_id', $employee_id)
                ->where('type', 'late')
                ->count();
            $leave_attendances = AttendanceReport::whereBetween('date', [$start_month, $end_month])
                ->where('employee_id', $employee_id)
                ->where('type', 'leave')
                ->count();
            return response()->json([
                'message' => 'Data retrived successfully',
                'Total attendance marked' => $attendances,
                'Present' => $present_attendances,
                'Absent' => $absent_attendances,
                'Late' => $late_attendances,
                'Leave' => $leave_attendances
            ]);
        }
    }
}
