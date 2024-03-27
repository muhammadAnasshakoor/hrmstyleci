<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\AttendanceReport;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

/**
 * @OA\Tag(
 *     name="Attendance Report",
 *     description="Handling the crud of attendance Report in it."
 * )
 */
class AttendanceReportController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function __construct()
    {
        // Apply middleware to all methods in the controller
        $this->middleware('checkPermission:attendance-report.list')->only('periodicReport', 'dailyReport', 'GetEmployee');
        // $this->middleware('checkPermission:designation.create')->only('create');
        // $this->middleware('checkPermission:designation.store')->only('store');
        // $this->middleware('checkPermission:designation.edit')->only('show');
        // $this->middleware('checkPermission:designation.update')->only('update');
        // $this->middleware('checkPermission:designation.delete')->only('delete');
    }

    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
    }

    /**
     * @OA\post(
     *      path="/api/attendance-periodic-report/get-employee",
     *      summary="GET The Employee.Permission required = attendance-report.list",
     *      description="This endpoint gives a specific employee. You just need to enter the emirates id of the employee, and it will return you the employee.",
     *      tags={"Attendance Report"},
     *
     *      @OA\RequestBody(
     *
     *         @OA\MediaType(
     *             mediaType="application/x-www-form-urlencoded",
     *
     *             @OA\Schema(
     *                 type="object",
     *
     *                 @OA\Property(
     *                     property="emirates_id",
     *                     type="number",
     *                     example="12121211212",
     *                     description="The emirates_id of the employee (required)"
     *                 )
     *             )
     *         )
     *     ),
     *
     *      @OA\Response(response="200", description="Successful operation"),
     *      @OA\Response(response="401", description="Unauthorized")
     * )
     */
    public function GetEmployee(Request $request)
    {
        $emirates_id = $request->input('emirates_id');
        $loggedin_user = auth::user();

        $loggedInTenant = $loggedin_user->tenant;
        $loggedInTenantId = $loggedInTenant->id;

        $employee = Employee::where('emirates_id', $emirates_id)
            ->where('status', '1')
            ->where('tenant_id', $loggedInTenantId)->first();
        if ($employee == null) {
            return response()->json([
                'message' => 'Oops! No employee found with the provided Emirates ID.',
                'status'  => 'error',
            ], 404);
        } else {
            return response()->json([
                'message'  => 'This is your required employee',
                'Employee' => $employee,
            ]);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/attendance-periodic-report",
     *     summary="GET attendance-periodic-report.Permission required = attendance-report.list",
     *     description="This endpoint get  attendance-periodic-report.",
     *     tags={"Attendance Report"},
     *
     *     @OA\RequestBody(
     *
     *         @OA\MediaType(
     *             mediaType="application/x-www-form-urlencoded",
     *
     *             @OA\Schema(
     *                 type="object",
     *
     *                 @OA\Property(
     *                     property="from_date",
     *                     type="date",
     *                     example="2024-12-11",
     *                     description="The date from which you want to start the report => required and its format should be yyyy-mm-dd"
     *                 ),
     *                 @OA\Property(
     *                     property="to_date",
     *                     type="date",
     *                     example="2024-12-11",
     *                     description="The date to which you want to end the report => required and its format should be yyyy-mm-dd"
     *                 ),
     *                       @OA\Property(
     *                     property="employee_id",
     *                     type="number",
     *                     example="1",
     *                     description="the id of the employee for which you want to get the report"
     *                 ),
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response="201", description="attendance-daily-report created successfully"),
     *     @OA\Response(response="401", description="Unauthorized"),
     *     @OA\Response(response="422", description="Validation failed")
     * )fd
     */
    public function periodicReport(Request $request)
    {
        try {
            $user = auth::user();
            $data = $request->validate([
                'from_date'   => 'required|date_format:Y-m-d',
                'to_date'     => 'required|date_format:Y-m-d',
                'employee_id' => 'nullable|numeric',
            ]);

            $start_date = $data['from_date'];
            $end_date = $data['to_date'];

            $employee_id = $data['employee_id'];
            if ($user->employee) {
                $employee_id = $user->employee->id;
            }
            // Retrieve attendance record for the current date and employee
            $attendance = AttendanceReport::where('employee_id', $employee_id)
                ->whereBetween('date', [$start_date, $end_date])
                ->get();

            return response()->json([
                'message' => 'Employee report generated successfully',
                'report'  => $attendance,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error generating report',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/attendance-daily-report",
     *     summary="GET attendance-daily-report.Permission required = attendance-report.list",
     *     description="This endpoint gets attendance-daily-report.",
     *     tags={"Attendance Report"},
     *
     *     @OA\RequestBody(
     *
     *         @OA\MediaType(
     *             mediaType="application/x-www-form-urlencoded",
     *
     *             @OA\Schema(
     *                 type="object",
     *
     *                 @OA\Property(
     *                     property="date",
     *                     type="date",
     *                     example="2024-12-11",
     *                     description="The date of the attendance-daily-report => required and its format should be yyyy-mm-dd"
     *                 ),
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response="201", description="attendance-daily-report created successfully"),
     *     @OA\Response(response="401", description="Unauthorized"),
     *     @OA\Response(response="422", description="Validation failed")
     * )fd
     */
    public function dailyReport(Request $request)
    {
        try {
            $date = $request->validate([
                'date' => 'required|date_format:Y-m-d',
            ]);

            $user = Auth::user();
            if ($user->company) {
                $company_id = $user->company->id;
                $attendances = AttendanceReport::whereDate('date', $date['date'])
                    ->where('company_id', $company_id)->get();
            }
            if ($user->tenant) {
                $tenant_id = $user->tenant->id;
                $attendances = AttendanceReport::whereDate('date', $date['date'])
                    ->where('tenant_id', $tenant_id)->get();
            } elseif ($user->employee) {
                $employee_id = $user->employee->id;
                $attendances = AttendanceReport::whereDate('date', $date['date'])
                    ->where('employee_id', $employee_id)->get();
            } else {
                return response()->json([
                    'message' => 'You can not access it',
                ]);
            }
            // Retrieve all employees with their associated rosters, duties, companies, and holidays

            // Return the report
            return response()->json([
                'message' => 'Employee report generated successfully',
                'report'  => $attendances,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error generating report',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function searchEmployee()
    {
    }

    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
