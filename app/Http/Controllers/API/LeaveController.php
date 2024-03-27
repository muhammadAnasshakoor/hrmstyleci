<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateLeaveRequest;
use App\Models\Attendance;
use App\Models\AttendanceReport;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Leave;
use App\Models\Media;
use Carbon\Carbon;
use Illuminate\Validation\Rule;

/**
 * @OA\Tag(
 *     name="leave",
 *     description="Handling the crud of leave in it."
 * )
 */
class LeaveController extends Controller
{
    // public function __construct()
    // {
    //     // Apply middleware to all methods in the controller
    //     $this->middleware('checkPermission:leave.list')->only('index', 'inactiveleave');
    //     $this->middleware('checkPermission:leave.create')->only('create');
    //     $this->middleware('checkPermission:leave.store')->only('store');
    //     $this->middleware('checkPermission:leave.edit')->only('show');
    //     $this->middleware('checkPermission:leave.update')->only('update');
    //     $this->middleware('checkPermission:leave.delete')->only('delete');
    // }


    /**
     * Display a listing of the resource.
     */

    /**
     * @OA\Get(
     *      path="/api/leave",
     *      summary="Get All leaves.Permission required = leave.list",
     *      description="This endpoint retrieves information about something.",
     *      tags={"leave"},
     *      @OA\Response(response="200", description="Successful operation"),
     *      @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function index()
    {

        $user = auth()->user();
        if ($user->employee) {
            $employee_id  = $user->employee->id;
            $leaves = Leave::where('employee_id', $employee_id)
                ->with('employee:id,name,emirates_id')
                ->get();
        }
        if ($user->tenant) {
            $tenant_id = $user->tenant->id;
            $leaves = Leave::where('tenant_id', $tenant_id)
                ->with('employee:id,name,emirates_id')
                ->get();
        }

        return response()->json([
            'message' => 'Leaves retrieved successfully',
            'Leaves' => $leaves
        ]);
    }


    public function filterLeaves(Request $request)
    {

        $user = auth()->user();
        $request->validate([
            'status' => 'required'
        ]);
        if ($user->employee) {
            $employee_id = $user->employee->id;
            $leaves = Leave::where('employee_id', $employee_id)
                ->with('employee:id,name,emirates_id')
                ->where('status', $request->input('status'))
                ->get();
        }

        if ($user->tenant) {
            $tenant_id = $user->tenant->id;
            $leaves = Leave::where('tenant_id', $tenant_id)
                ->with('employee:id,name,emirates_id')
                ->where('status', $request->input('status'))
                ->get();
        }
        return response()->json([
            'message' => 'Data retrieved successfully',
            'data' => $leaves
        ]);
    }
    /**
     * @OA\Post(
     *      path="/api/leave/get-employee",
     *      summary="GET The Employee. Permission required = leave.store",
     *      description="This endpoint retrieves a specific employee by providing their Emirates ID.",
     *      tags={"leave"},
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
        $loggedin_user = auth()->user();
        $tenant = $loggedin_user->tenant;
        $tenantId = $tenant->id;

        $employees = Employee::where(function ($query) use ($search_data) {
            $query->where('name', 'LIKE', '%' . $search_data . '%')
                ->orWhere('emirates_id', 'LIKE', '%' . $search_data . '%');
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
     * @OA\Post(
     *     path="/api/leave",
     *     summary="Create a new leave.Permission required = leave.store",
     *     description="This endpoint creates a new leave.",
     *     tags={"leave"},
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="application/x-www-form-urlencoded",
     *             @OA\Schema(
     *                 type="object",
     *
     *                 @OA\Property(
     *                     property="title",
     *                     type="string",
     *                     example="Monthly Plan",
     *                     description="The title of the plan => required"
     *                 ),
     *
     *                 @OA\Property(
     *                     property="price",
     *                     type="string",
     *                     example="10000 PKR",
     *                     description="The price of the plan => required"
     *                 ),
     *
     *                 @OA\Property(
     *                     property="discounted_price",
     *                     type="string",
     *                     example="10000 PKR",
     *                     description="The discounted_price of the plan => nullable"
     *                 ),
     * *
     *                 @OA\Property(
     *                     property="description",
     *                     type="string",
     *                     example="Any description related to plan",
     *                     description="The description of the plan => nullable"
     *                 ),
     *             )
     *         )
     *     ),
     *     @OA\Response(response="201", description="leave created successfully"),
     *     @OA\Response(response="401", description="Unauthorized"),
     *     @OA\Response(response="422", description="Validation failed")
     * )fd
     */

    public function store(CreateLeaveRequest $request)
    {
        DB::beginTransaction();
        try {
            $leave_data = $request->validated();

            //getting the tenant and the employee
            $user = auth()->user();
            $user_id = $user->id;

            if (($user->employee)) {
                $employee_id = $user->employee->id;
                $tenant_id = $user->employee->tenant_id;
                $leave_data['employee_id'] = $employee_id;
            }
            if ($user->tenant) {


                $request->validate([
                    'employee_id' => 'required|exists:employees,id'
                ]);
                $tenant_id = $user->tenant->id;
            }


            $leave_data['tenant_id'] = $tenant_id;
            $leave_data['user_id'] = $user_id;


            $start_date = Carbon::parse($request->input('start_date'));
            $end_date = Carbon::parse($request->input('end_date'));

            // Calculate the difference in days
            $total_days = $end_date->diffInDays($start_date) + 1;

            $leave_data['total_days'] = $total_days;


            $existing_leave = Leave::where('tenant_id', $tenant_id)
                ->where('employee_id', $request->input('employee_id'))
                ->WhereBetween('start_date', [$start_date, $end_date])
                ->orWhereBetween('end_date', [$start_date, $end_date])
                ->exists();

            if ($existing_leave) {
                return response()->json([
                    'message' => 'The provided date range overlaps with an existing leave. Please choose different dates.',
                ]);
            }

            $leave_request = Leave::create($leave_data);

            DB::commit();
            return response()->json([
                'message' => 'Leave request has been successfully submitted.',
                'Leave Request' => $leave_request
            ], 200);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => 'Failed to create leave request',
                'error' => $e->getMessage()
            ], 500);
        }
    }



    /**
     * @OA\Get(
     *      path="/api/leave/{id}",
     *      summary="GET The leave.Permission required = leave.edit",
     *      description="This endpoint Gives a specific  leave.",
     *      tags={"leave"},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="The ID of the leave ",
     *         @OA\Schema(
     *             type="integer",
     *             format="int64"
     *         )
     *     ),
     *      @OA\Response(response="200", description="Successful operation"),
     *      @OA\Response(response="401", description="Unauthorized"),
     * )
     */

    public function show(Leave $leave)

    {
        return response()->json([
            'message' => 'Leave data retrieved successfully',
            'data' => $leave
        ]);
    }

    public function edit($id)
    {
        // Implement edit method logic here if needed
    }

    /**
     * @OA\Patch(
     *     path="/api/leave/{id}",
     *     summary="Update the leave.Permission required = leave.update",
     *     description="This endpoint updates a leave.",
     *     tags={"leave"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="The ID of the leave to be updated",
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
     *                     property="title",
     *                     type="string",
     *                     example="Monthly Plan",
     *                     description="The title of the plan => required"
     *                 ),
     *                 @OA\Property(
     *                     property="price",
     *                     type="string",
     *                     example="10000 PKR",
     *                     description="The price of the plan => required"
     *                 ),
     *
     *                 @OA\Property(
     *                     property="discounted_price",
     *                     type="string",
     *                     example="10000 PKR",
     *                     description="The discounted_price of the plan => nullable"
     *                 ),
     * *
     *                 @OA\Property(
     *                     property="description",
     *                     type="string",
     *                     example="Any description related to plan",
     *                     description="The description of the plan => nullable"
     *                 ),
     *  @OA\Property(
     *                     property="status",
     *                     type="number",
     *                     example="1",
     *                     description="The status of the leave =>required"
     *                 ),
     *             )
     *         )
     *     ),
     *     @OA\Response(response="201", description="leave created successfully"),
     *     @OA\Response(response="401", description="Unauthorized"),
     *     @OA\Response(response="422", description="Validation failed")
     * )fd
     */

    public function update(CreateLeaveRequest $request, Leave $leave)
    {
        DB::beginTransaction();
        try {

            $user = auth()->user();
            $user_id = $user->id;
            // Check if the authenticated user is the owner of the leave
            if ($user->employee) {
                if ($leave->user_id != $user_id) {
                    return response()->json([
                        'message' => 'You are not authorized to update this leave request as you did not request it.',
                    ], 403); // HTTP status code 403 indicates Forbidden
                }
            }


            $leave_data = $request->validated();
            $leave->update($leave_data);
            DB::commit();

            return response()->json([
                'message' => 'The Leave request has been updated successfully',
                'data' => $leave
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => 'Failed to update leave request',
                'error' => $e->getMessage()
            ], 500);
        }
    }



    public function updateStatus(Request $request, Leave $leave)
    {

        DB::beginTransaction();
        try {
            // $leave = Leave::findOrFail($id);
            $user = auth()->user();

            $current_date = now()->format('Y-m-d');
            if ($leave->start_date  <= $current_date) {
                return response()->json([
                    'message' => 'The leave request cannot be updated as its start date has already passed.'
                ]);
            }
            if (!($user->tenant)) {
                return response()->json([
                    'message' => 'Only tenants can update the status of leave requests.'
                ]);
            }

            $leave_data = $request->validate([
                'status' => ['required', Rule::in(
                    ['pending', 'approved', 'rejected']
                )]
            ]);

            $start_date = Carbon::parse($leave->start_date);
            $end_date = Carbon::parse($leave->end_date);

            if ($leave->status == 'pending' || $leave->status == 'rejected') {
                if ($request->input('status') == 'approved') {

                    for ($date = $start_date; $date->lte($end_date); $date->addDay()) {
                        $attendance =    Attendance::create([
                            'employee_id' => $leave->employee_id,
                            'tenant_id' => $leave->tenant_id,
                            'date' => $date->format('Y-m-d'),
                            'type' => 'leave',
                            'reason' => $leave->description
                        ]);

                        $employee_name  = Employee::where('id', $attendance->employee_id)->first()->name;
                        AttendanceReport::create([
                            'employee_id' => $attendance->employee_id,
                            'tenant_id' => $attendance->tenant_id,
                            'attendance_id' => $attendance->id,
                            'employee_name' => $employee_name,
                            'date' => $attendance->date,
                            'type' => 'leave',
                            'reason' => $leave->description,
                            'day' => ucfirst($date->format('l'))
                        ]);
                    }
                }
            }
            if ($leave->status == 'approved' && $request->input('status') == 'rejected') {

                for ($date = $start_date; $date->lte($end_date); $date->addDay()) {
                    $attendance =  Attendance::where('tenant_id', $leave->tenant_id)
                        ->where('date', $date->format('Y-m-d'))
                        ->where('employee_id', $leave->employee_id)
                        ->first();
                    $attendance->delete();

                    $attendance_report = $attendance->attendanceReport;
                    $attendance_report->delete();
                }
            }
            $leave->update($leave_data);
            DB::commit();
            return response()->json([
                'message' => 'Stutus Updated successfully.',
                'data' => $leave
            ], 200);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => 'Failed to update leave request status.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Delete(
     *      path="/api/leave/{id}",
     *      summary="Delete The leave.Permission required = leave.delete",
     *      description="This endpoint delete leave.",
     *      tags={"leave"},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="The ID of the leave to be deleted",
     *         @OA\Schema(
     *             type="integer",
     *             format="int64"
     *         )
     *     ),
     *      @OA\Response(response="200", description="Successful operation"),
     *      @OA\Response(response="401", description="Unauthorized"),
     * )
     */

    public function destroy(Leave $leave)
    {
        DB::beginTransaction();
        try {
            $leave->delete();

            DB::commit();

            return response()->json([
                'message' => 'Leave request deleted successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => 'Failed to delete leave request',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
