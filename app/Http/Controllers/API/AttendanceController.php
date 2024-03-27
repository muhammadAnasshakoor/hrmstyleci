<?php

namespace App\Http\Controllers\API;

use App\Models\Company;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\AttendanceReport;
use App\Models\Media;
use App\Models\Employee;
use Spatie\Permission\Models\Role;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\CreateUserRequest;
use App\Http\Requests\CreateAttendanceRequest;
use App\Models\Attendance;
use App\Models\AttendanceRoster;
use App\Models\User_type;
use Illuminate\Support\Carbon;
use Illuminate\Http\Response;

/**
 * @OA\Tag(
 *     name="Attendance",
 *     description="Handling the CRUD operations related to attendance."
 * )
 */

class AttendanceController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    /**
     * @OA\Get(
     *      path="/api/attendance",
     *      summary="Get All attendances. Permission required = attendance.list",
     *      description="
     * This endpoint retrieves attendance records based on the user's role:
     * Tenants: View their own attendance and their company's employees.
     * Companies: View attendance of their employees only.",
     *      tags={"Attendance"},
     *      @OA\Response(response="200", description="Successful operation"),
     *      @OA\Response(response="401", description="Unauthorized"),
     * )
     */

    public function __construct()
    {
        // Apply middleware to all methods in the controller
        $this->middleware('checkPermission:attendance.list')->only('index');
        $this->middleware('checkPermission:attendance.store')->only('store', 'getEmployee');
        $this->middleware('checkPermission:attendance.edit')->only('show');
        $this->middleware('checkPermission:attendance.update')->only('update');
    }

    public function index()
    {
        $user = auth::user();
        if ($user->tenant != null) {
            $tenant_id = $user->tenant->id;
            $attendances = Attendance::orderBy('created_at', 'desc')
                ->where('tenant_id', $tenant_id)
                ->get();
        } elseif ($user->company != null) {
            $company_id = $user->company->id;
            $attendances = Attendance::orderBy('created_at', 'desc')
                ->where('company_id', $company_id)
                ->get();
        } else {
            return response()->json([
                'message' => 'You can not access it '
            ]);
        }
        return response()->json([
            'message' => 'All attendances',
            'data' => $attendances
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */

    public function create()
    {
    }

    /**
     * Store a newly created resource in storage.
     */
    /**
     * @OA\Post(
     *     path="/api/attendance",
     *     summary="Mark a new attendance. Permission required = attendance.store",
     *     description="Attendance can be marked by:
     *     Tenant: Input fields are required for tenant-marked attendance.
     *     Company: Input fields are required for company-marked attendance.
     *     Employee: If an employee is marking attendance, no input is needed. Attendance is automatically
     *     recorded twice a day: once for check-in and again for check-out, according to the current time.",
     *     tags={"Attendance"},
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="application/x-www-form-urlencoded",
     *             @OA\Schema(
     *                 type="object",
     *                 @OA\Property(
     *                     property="employee_id",
     *                     type="string",
     *                     example="newattendance",
     *                     description="The name of the attendance => required"
     *                 ),
     *                 @OA\Property(
     *                     property="check_in",
     *                     type="string",
     *                     example="10:00 PM",
     *                     description="The check_in time of the attendance => required and the accepted format is 10:00 PM"
     *                 ),
     *                 @OA\Property(
     *                     property="check_out",
     *                     type="string",
     *                     example="10:00 PM",
     *                     description="The check_out time of the attendance => required and the accepted format is 10:00 PM"
     *                 ),
     *                 @OA\Property(
     *                     property="type",
     *                     type="string",
     *                     example="late,absent,present,leave",
     *                     description="The type of the attendance => nullable, only accepted are late,absent,present,leave"
     *                 ),
     *                 @OA\Property(
     *                     property="reason",
     *                     type="string",
     *                     example="he was sick",
     *                     description="The reason of the leave,late or absend and its value should only be filled if the type is late,absent or leave  => nullable"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response="201", description="Attendance created successfully"),
     *     @OA\Response(response="401", description="Unauthorized"),
     *     @OA\Response(response="422", description="Validation failed")
     * )
     */

    public function store(CreateAttendanceRequest $request)
    {

        DB::beginTransaction();
        try {
            $attendance_data = $request->validated();
            // Get the current date
            $current_date = now()->format('Y-m-d');

            // Get the authenticated user
            $user = auth::user();
            // Initialize variables for tenant and company IDs
            $tenant_id = null;
            $company_id = null;

            // Check the role of the authenticated user
            if ($user->tenant || $user->company) {
                // checking if the employee id field is filled
                if (!($request->filled('employee_id'))) {
                    return response()->json([
                        'message' => 'Employee_id field is required'
                    ]);
                }
                if ($user->company) {

                    $company = $user->company;
                    $company_id = $company->id;
                    $tenant_id = $company->tenant_id;
                } elseif ($user->tenant) {
                    $tenant = $user->tenant;
                    $tenant_id = $tenant->id;
                }

                $attendance_data['tenant_id'] = $tenant_id;
                $attendance_data['company_id'] = $company_id;

                // entering the dummy data for now but it will be  updated to pick the exact location
                $attendance_data['check_in_location'] = 'E 73، I.T. Tower, E1 Hali Rd, Block E1 Block E 1 Gulberg III, Lahore, Punjab 54000, Pakistan';
                $attendance_data['check_out_location'] = 'E 73، I.T. Tower, E1 Hali Rd, Block E1 Block E 1 Gulberg III, Lahore, Punjab 54000, Pakistan';

                if ($request->input('check_in') === null || $request->input('check_out') === null) {
                    return response()->json([
                        'message' => 'The check_in and check_out fields are required'
                    ]);
                }
                $check_in_time = \DateTime::createFromFormat('h:i A', $attendance_data['check_in']);
                $check_out_time = \DateTime::createFromFormat('h:i A', $attendance_data['check_out']);

                // Check if check-out time is before or equal to check-in time
                if ($check_out_time <= $check_in_time) {

                    return response()->json([
                        'message' => 'The exit time cannot be before or equal to the entry time.'
                    ]);
                }

                // Calculate the interval between check-in and check-out
                $interval = $check_out_time->diff($check_in_time);
                $attendance_data['total_hours'] =  $interval->format('%H:%I');

                $employee = Employee::where('id', $request->input('employee_id'))->first();

                // Check if attendance has already been marked for the employee on the current date
                $previous_attendance = Attendance::where('employee_id', $request->input('employee_id'))
                    ->whereDate('date', $current_date)->first();

                if ($previous_attendance) {
                    return response()->json([
                        'message' => 'Attendance of this employee has already been marked ',
                    ]);
                }
                // If attendance has not been marked, create a new attendance record
                $new_attendance = Attendance::create($attendance_data);

                // storing the data in the attendance report
                $date = $new_attendance->date;
                $day_name = lcfirst(Carbon::parse($date)->englishDayOfWeek);

                //checking if the attendance roster of whole month has been marked or not
                $start_of_month = Carbon::now()->startOfMonth();
                $end_of_month = Carbon::now()->endOfMonth();

                $existing_dates = AttendanceRoster::where('employee_id', $request->input('employee_id'))
                    ->whereBetween('date', [$start_of_month, $end_of_month])
                    ->pluck('date')
                    ->map(
                        function ($date) {
                            return Carbon::parse($date)->toDateString();
                        }
                    );

                $expected_dates = collect(Carbon::parse($start_of_month)->toPeriod($end_of_month))->map(
                    function ($date) {
                        return Carbon::parse($date)->toDateString();
                        // Convert to Carbon instance
                    }
                );

                $missing_dates = $expected_dates->diff($existing_dates);
                if ($missing_dates->isNotEmpty()) {
                    $data = [];
                    foreach ($missing_dates  as $date) {
                        $data[] = $date;
                    }

                    return response()->json([
                        'message' => 'Attendance roster for the whole month is not complete.',
                        'Missiog dates' => $data
                    ]);
                }

                //on succussfully checking getting the roster and fetching the expected time
                $roster = AttendanceRoster::where('date', $current_date)
                    ->where('employee_id', $request->input('employee_id'))->first();
                if (!$roster) {
                    return response()->json([
                        'message' => 'i got it'
                    ]);
                }

                $check_in = $roster->check_in;
                $check_out = $roster->check_out;

                if ($roster->check_in == null && $roster->check_out == null && $roster->holiday == '1') {
                    $time = 'Holiday';
                } elseif ($roster->check_in != null && $roster->check_out != null && $roster->holiday == '0') {
                    $time = 'From' . $check_in . 'To' . $check_out;
                }
                AttendanceReport::create([
                    'tenant_id' => $tenant_id,
                    'attendance_id' => $new_attendance->id,
                    'employee_id' => $employee->id,
                    'employee_name' => $employee->name,
                    'checkin' => $request->input('check_in'),
                    'checkout' => $request->input('check_out'),
                    'total_hours_worked' => $new_attendance->total_hours,
                    'type' => $new_attendance->type,
                    'reason' => $new_attendance->reason,
                    'day' => $day_name,
                    'expected_time' => $time,
                    'company_id' => $new_attendance->company_id,
                    'date' => $current_date
                ]);
                DB::commit();
                return response()->json([
                    'message' => 'Attendance is marked successfully',
                    'attendance' => $new_attendance,

                ]);
            }
            // If the attendance is being marked by the employee itself
            elseif ($user->employee) {

                $employee = $user->employee;
                $employee_id = $employee->id;
                $tenant_id = $employee->tenant_id;

                //checking if the attendance roster of whole month has been marked or not
                $start_of_month = Carbon::now()->startOfMonth();
                $end_of_month = Carbon::now()->endOfMonth();

                $existing_dates = AttendanceRoster::where('employee_id', $employee_id)
                    ->whereBetween('date', [$start_of_month, $end_of_month])
                    ->pluck('date')
                    ->map(
                        function ($date) {
                            return Carbon::parse($date)->toDateString();
                        }
                    );

                $expected_dates = collect(Carbon::parse($start_of_month)->toPeriod($end_of_month))->map(
                    function ($date) {
                        return Carbon::parse($date)->toDateString();
                        // Convert to Carbon instance
                    }
                );

                $missing_dates = $existing_dates->diff($expected_dates);

                if ($missing_dates->isNotEmpty()) {
                    $data = [];
                    foreach ($missing_dates as $date) {
                        $data[] = $date;
                    }
                    return response()->json([
                        'message' => 'Attendance roster for the whole month is not complete.',
                        'Missiog dates' => $data
                    ]);
                }

                // getting the company id for the using the duty of the employe
                $duty = $employee->duties()->where('status', '1')->first();
                if (!($duty)) {
                    return response()->json([
                        'message' => 'Attendance can not be marked as there is no duty assigned to this employee.'
                    ]);
                }

                $company_id = $duty->company_id;
                $attendance_data['company_id'] = $company_id;
                $attendance_data['tenant_id'] = $tenant_id;
                $attendance_data['type'] = 'present';

                // entering the dummy data for now but it will be  updated to pick the exact location
                $attendance_data['check_in_location'] = 'E 73، I.T. Tower, E1 Hali Rd, Block E1 Block E 1 Gulberg III, Lahore, Punjab 54000, Pakistan';
                $attendance_data['check_out_location'] = 'E 73، I.T. Tower, E1 Hali Rd, Block E1 Block E 1 Gulberg III, Lahore, Punjab 54000, Pakistan';

                // Check if the employee has already marked attendance for the current date
                $check_in_attendance = Attendance::where('employee_id', $employee_id)
                    ->whereDate('date', $current_date)->first();

                // If the employee has not marked attendance yet
                if (!$check_in_attendance) {

                    $attendance_data['check_in'] = now()->format('h:i A');
                    $check_in_time = \DateTime::createFromFormat('h:i A', $attendance_data['check_in']);
                    $attendance_data['employee_id'] = $employee_id;

                    // $roster_id = $duty->roster_id;
                    // $attendance_data[ 'roster_id' ] = $roster_id;

                    $new_attendance = Attendance::create($attendance_data);

                    // storing the data in the attendance report
                    $date = $new_attendance->date;
                    $day_name = lcfirst(Carbon::parse($date)->englishDayOfWeek);

                    //on succussfully checking getting the roster and fetching the expected time
                    $roster = AttendanceRoster::where('date', $current_date)
                        ->where('employee_id', $employee_id)->first();
                    $check_in = $roster->check_in;
                    $check_out = $roster->check_out;
                    if ($roster->check_in == null && $roster->check_out == null && $roster->holiday == '1') {
                        $time = 'Holiday';
                    } elseif ($roster->check_in != null && $roster->check_out != null && $roster->holiday == '0') {
                        $time = 'From' . $check_in . 'To' . $check_out;
                    }
                    AttendanceReport::create([
                        'tenant_id' => $tenant_id,
                        'attendance_id' => $new_attendance->id,
                        'employee_id' => $employee_id,
                        'employee_name' => $employee->name,
                        'checkin' => $new_attendance->check_in,
                        'type' => $new_attendance->type,
                        'reason' => $new_attendance->reason,
                        'day' => $day_name,
                        'expected_time' => $time,
                        'company_id' => $new_attendance->company_id,
                        'date' => $current_date
                    ]);

                    DB::commit();
                    return response()->json([
                        'message' => 'attendance is marked successfully',
                        'attendance' => $new_attendance,
                    ]);
                } else {
                    // If the employee has already marked check_in attendance
                    if (is_null($check_in_attendance->check_out) && is_null($check_in_attendance->total_hours)) {
                        $attendance_data['check_out'] = now()->format('h:i A');
                        $check_out_time = \DateTime::createFromFormat('h:i A', $attendance_data['check_out']);

                        $check_in_time = \DateTime::createFromFormat('h:i A', $check_in_attendance->check_in);
                        $interval = $check_out_time->diff($check_in_time);
                        $attendance_data['total_hours'] =  $interval->format('%H:%I');

                        $check_in_attendance->update($attendance_data);

                        // storing the data in the attendance report
                        $attendance_report = $check_in_attendance->attendanceReport;
                        $attendance_report->update([
                            'checkout' => $check_in_attendance->check_out,
                            'total_hours_worked' => $check_in_attendance->total_hours,
                        ]);
                        DB::commit();
                        return response()->json([
                            'message' => 'Check-out attendance is marked'
                        ]);
                    } else {
                        // it checks if you have already marked the check_in and check_out attendance
                        return response()->json([
                            'message' => 'Attendance has already been marked for today'
                        ]);
                    }
                }
            }
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => 'There was an error',
                'error' => $e->getMessage(),
            ]);
        }
    }
    /**
     * @OA\Post(
     *      path="/api/attendance/get-employee",
     *      summary="GET The Employee. Permission required = attendance.store",
     *      description="This endpoint retrieves a specific employee by providing their Emirates ID.",
     *      tags={"Attendance"},
     *      @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="application/x-www-form-urlencoded",
     *             @OA\Schema(
     *                 type="object",
     *                 @OA\Property(
     *                     property="searchdata",
     *                     type="string",
     *                     example="12121211212 0r James",
     *                     description="The Emirates ID of the employee or the name of the employee (required)"
     *                 )
     *             )
     *         )
     *     ),
     *      @OA\Response(response="200", description="Successful operation"),
     *      @OA\Response(response="401", description="Unauthorized")
     * )
     */

    public function getEmployee(Request $request)
    {
        $search_data = $request->input('searchdata');
        $loggedin_user = auth::user();
        $tenant = $loggedin_user->tenant;
        $tenantId = $tenant->id;

        $employees = Employee::where(function ($query) use ($search_data) {
            $query->where('name', 'LIKE', '%' . $search_data . '%')
                ->orWhere('emirates_id', 'LIKE', '%' . $search_data . '%');
        })
            ->whereHas('duties', function ($subQuery) {
                $subQuery->where('status', 1);
            })
            ->where('tenant_id', $tenantId)
            ->select('id', 'name', 'emirates_id', 'profile_image_id')
            ->get();



        foreach ($employees as $employee) {
            $profile_image_id = $employee->profile_image_id;
            $profile_image = Media::where('id', $profile_image_id)->first();
            if ($profile_image) {
                $profile_image_url = asset("storage/{$profile_image->media_path}");
                $employee->profile_image_id = $profile_image_url;
            }
        }


        if ($employees->count() <= 0) {
            return response()->json([
                'message' => 'Oops! No employee found with the provided Emirates ID.',
                'status' => 'error'
            ], 404);
        }
        return response()->json([
            'message' => 'Employees on duties are  retrieved successfully',
            'Employee' => $employees
        ]);
    }

    /**
     * Display the specified resource.
     */
    /**
     * @OA\Get(
     *      path="/api/attendance/{id}",
     *      summary="GET The attendance. Permission required = attendance.edit",
     *      description="This endpoint retrieves a specific attendance by providing its ID.",
     *      tags={"Attendance"},
     *      @OA\Parameter(
     *          name="id",
     *          in="path",
     *          required=true,
     *          description="The ID of the attendance",
     *          @OA\Schema(
     *              type="integer",
     *              format="int64"
     *          )
     *      ),
     *      @OA\Response(response="200", description="Successful operation"),
     *      @OA\Response(response="401", description="Unauthorized")
     * )
     */

    public function show(Attendance $attendance)
    {

        return response()->json([
            'message' => 'Attendance information retrieved successfully.',
            'attendance' => $attendance
        ]);
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
    /**
     * @OA\Patch(
     *     path="/api/attendance/{id}",
     *     summary="Update the attendance. Permission required = attendance.update",
     *     description="Only the tenant and the company can update attendance, and they can only update attendance records that were created on the same day as the update.",
     *     tags={"Attendance"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="The ID of the attendance to be updated",
     *         @OA\Schema(
     *             type="integer",
     *             format="int64"
     *         )
     *     ),
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\MediaType(
     *             mediaType="application/x-www-form-urlencoded",
     *             @OA\Schema(
     *                 type="object",
     *                 @OA\Property(
     *                     property="employee_id",
     *                     type="string",
     *                     example="newattendance",
     *                     description="The name of the attendance => required"
     *                 ),
     *                 @OA\Property(
     *                     property="check_in",
     *                     type="string",
     *                     example="10:00 PM",
     *                     description="The check_in time of the attendance => required and the accepted format is 10:00 PM"
     *                 ),
     *                 @OA\Property(
     *                     property="check_out",
     *                     type="string",
     *                     example="10:00 PM",
     *                     description="The check_out time of the attendance => required and the accepted format is 10:00 PM"
     *                 ),
     *                 @OA\Property(
     *                     property="type",
     *                     type="string",
     *                     example="late,absent,present,leave",
     *                     description="The type of the attendance => nullable, only accepted are late,absent,present,leave"
     *                 ),
     *                 @OA\Property(
     *                     property="reason",
     *                     type="text",
     *                     example="he was sick",
     *                     description="The reason of the leave,late or absent and its value should only be filled if the type is late,absent or leave  => nullable"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response="201", description="Attendance updated successfully"),
     *     @OA\Response(response="401", description="Unauthorized"),
     *     @OA\Response(response="422", description="Validation failed")
     * )
     */
    public function update(CreateAttendanceRequest $request, string $id)
    {

        DB::beginTransaction();
        try {
            $attendance_data = $request->validated();
            // Get the current date
            $current_date = now()->format('Y-m-d');

            // Get the authenticated user
            $user = auth::user();
            // Initialize variables for tenant and company IDs

            if ($user->tenant || $user->company) {

                $previous_attendance = Attendance::where('id', $id)->first();
                if ($previous_attendance->date !== $current_date) {
                    return response()->json([
                        'message' => 'You can only update the attendance which is marked today'
                    ]);
                }
                if ($request->filled('employee_id')) {
                    if ($previous_attendance->employee_id != $attendance_data['employee_id']) {

                        // if input has employee_id then check if the attendance of this employee has already marked for today or not
                        $attendance = Attendance::where('employee_id', $request->input('employee_id'))
                            ->whereDate('date', $current_date)->first();

                        if ($attendance) {
                            return response()->json([
                                'message' => 'attendance of this employee has already been marked for today',
                                // 'attendance' => $attendance
                            ]);
                        }
                    }
                }

                if (!($request->filled('check_in'))  || !($request->filled('check_out')  || !($request->filled('employee_id')))) {
                    return response()->json([
                        'message' => 'The check_in ,check_out and employee_id fields are required'
                    ]);
                }

                $check_in_time = \DateTime::createFromFormat('h:i A', $attendance_data['check_in']);
                $check_out_time = \DateTime::createFromFormat('h:i A', $attendance_data['check_out']);

                // Check if check-out time is before or equal to check-in time
                if ($check_out_time <= $check_in_time) {

                    return response()->json([
                        'message' => 'The exit time cannot be before or equal to the entry time.'
                    ]);
                }

                // Calculate the interval between check-in and check-out
                $interval = $check_out_time->diff($check_in_time);
                $attendance_data['total_hours'] =  $interval->format('%H:%I');

                $previous_attendance->update($attendance_data);

                // updating the data in the attendance report

                // getting the employee
                $name = $previous_attendance->employee->name;
                $attendance_report = $previous_attendance->attendanceReport;
                $attendance_report->update([
                    'employee_id' => $previous_attendance->employee_id,
                    'employee_name' => $name,
                    'checkin' => $previous_attendance->check_in,
                    'checkout' => $previous_attendance->check_out,
                    'total_hours_worked' => $previous_attendance->total_hours,
                    'type' => $previous_attendance->type,
                    'reason' => $previous_attendance->reason,
                ]);

                DB::commit();
                return response()->json([
                    'message' => 'attendance is updated successfully',
                    // 'attendance' => $previous_attendance
                ]);
            }
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => 'There was an error',
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */

    public function destroy(string $id)
    {
        //
    }
}
