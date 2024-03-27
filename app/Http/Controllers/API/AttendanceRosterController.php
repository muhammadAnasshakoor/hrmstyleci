<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Exceptions\HttpResponseException;

use App\Models\Company;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Media;
use App\Models\Policy;
use App\Models\AttendanceRoster;
use Spatie\Permission\Models\Role;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\CreateUserRequest;
use App\Http\Requests\CreateAttendanceRequest;
use App\Http\Requests\CreateAttendanceRosterRequest;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\User_type;
use Illuminate\Support\Facades\Validator;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Carbon\Carbon;
use Illuminate\Foundation\Console\AboutCommand;

use function PHPUnit\Framework\isEmpty;

/**
 * @OA\Tag(
 *     name="Attendance Roster",
 *     description="Handling the crud of attendance roster in it."
 * )
 */
class AttendanceRosterController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    /**
     * @OA\Get(
     *      path="/api/attendance-roster",
     *      summary="Get All attendance-rosters.Permission required = attendance-roster.list",
     *      description="This endpoint retrieves information about something.",
     *      tags={"Attendance Roster"},
     *      @OA\Response(response="200", description="Successful operation"),
     *      @OA\Response(response="401", description="Unauthorized"),
     * )
     */

    public function __construct()
    {
        // Apply middleware to all methods in the controller
        $this->middleware('checkPermission:attendance-roster.list')->only('index');
        $this->middleware('checkPermission:attendance-roster.create')->only('create');
        $this->middleware('checkPermission:attendance-roster.store')->only('store');
        $this->middleware('checkPermission:attendance-roster.edit')->only('show');
        $this->middleware('checkPermission:attendance-roster.update')->only('update');
        $this->middleware('checkPermission:attendance-roster.delete')->only('delete');
    }

    public function index()
    {
        $user = Auth::user();
        $tenant = $user->tenant;
        $tenant_id = $tenant->id;

        $Rosters = AttendanceRoster::where('tenant_id', $tenant_id)
            ->with(['employee:id,name,emirates_id'])
            ->orderBy('created_at', 'desc')
            ->get();
        return response()->json([
            'message' => 'Rosters retrieved successfully',
            'Rosters' => $Rosters
        ]);
    }




    /**
     * @OA\Get(
     *      path="/api/attendance-roster/create",
     *      summary="Get All days .Permission required = attendance-roster.create",
     *      description="This endpoint retrieves all days of the current month for the logged-in tenant. It indicates which days are defined as holidays by the tenant.
     * 1. This endpoint provides all the days of the current month and indicates which days are defined as holidays by the tenant.
     * 2. For holidays, the input fields (check_in and check_out) should be disabled, and by default, the holiday flag (holiday = 1) is set, leaving check_in and check_out as null.",
     *
     *      tags={"Attendance Roster"},
     *      @OA\Response(response="200", description="Successful operation"),
     *      @OA\Response(response="401", description="Unauthorized"),
     * )
     */



    public function create()
    {
        $user = Auth::user();
        $tenant = $user->tenant;
        $tenant_id = $tenant->id;

        $employees = Employee::where('status', '1')
            ->where('tenant_id', $tenant_id)
            ->whereHas('duties', function ($query) {
                $query->where('status', 1);
            })
            ->select('id', 'name', 'emirates_id')
            ->get();

        // Returns all dates of current month
        $start_of_month = Carbon::now()->startOfMonth();
        $end_of_month = Carbon::now()->endOfMonth();

        $expected_dates = collect(Carbon::parse($start_of_month)->daysUntil($end_of_month))->map(function ($date) {
            return ['date' => $date->toDateString()]; // Wrap date in array for consistency
        });



        // Get holidays for the tenant
        $holidays = $tenant->holidays;

        // Define the Generator function for $holiday_dates
        $holiday_dates = function ($holidays) {
            $holiday_collection = collect();
            foreach ($holidays as $holiday) {
                $start_date = Carbon::parse($holiday->starting_date);
                $end_date = Carbon::parse($holiday->ending_date);
                $all_holiday_dates = collect(Carbon::parse($start_date)->daysUntil($end_date))->map(function ($date) use ($holiday) {
                    return [
                        'date' => $date->toDateString(),
                        'message' => 'Holiday: ' . $holiday->name,
                    ]; // Wrap date in array for consistency
                });
                $holiday_collection = $holiday_collection->merge($all_holiday_dates); // Merge with the main collection
            }
            return $holiday_collection; // Ensure uniqueness and reset keys
        };

        // Use the function to generate holiday dates
        $holidays = $holiday_dates($holidays);

        // Filter the $holidays collection based on the date range
        $holidays_filtered = $holidays->filter(function ($holiday) use ($start_of_month, $end_of_month) {
            $holiday_date = Carbon::parse($holiday['date']);
            return $holiday_date->between($start_of_month, $end_of_month);
        });

        // Merge all dates and holidays, ensuring no duplicates
        $all_dates = $holidays_filtered->merge($expected_dates)->sortBy('date')->unique('date')->values()->all(); // Sort by date

        return response()->json([
            'message' => "Successfully retrieved active employees having active duties.",
            'employees' => $employees,
            'all_dates' => $all_dates
        ]);
    }




    /**
     * @OA\post(
     *      path="/api/attendance-roster",
     *      summary="create a new attendance roster. Permission required = attendance-roster.store",
     *      description="You need to give the employee_id for one time. Then give all fields in the JSON form in the attendance.
     *",
     *      tags={"Attendance Roster"},
     *      @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="application/x-www-form-urlencoded",
     *             @OA\Schema(
     *                 type="object",
     *                 @OA\Property(
     *                     property="employee_id",
     *                     type="integer",
     *                     example="1",
     *                     description="The id of the employee that is needed to assign to the roster (required)"
     *                 ),
     *
     *                 @OA\Property(
     *                     property="roster_json",
     *                     type="json",
     *                     example={
     *                         "2024-12-24": {
     *                             "holiday": "1",
     *                             "check_in": null,
     *                             "check_out": null,
     *                             "date": "2024-12-24"
     *                         },
     *                         "2024-12-13": {
     *                             "holiday": "1",
     *                             "check_in": null,
     *                             "check_out": null,
     *                             "date": "2024-12-13"
     *                         },
     *                         "2024-12-26": {
     *                             "holiday": "1",
     *                             "check_in": null,
     *                             "check_out": null,
     *                             "date": "2024-12-26"
     *                         }
     *                     },
     *                     description="All the remaining fields will come under the roster_json like in the example in the json form"
     *                 ),
     *                 @OA\Property(
     *                     property="check_in",
     *                     type="time",
     *                     example="10:00 PM",
     *                     description="The check_in time of the employee"
     *                 ),
     *                 @OA\Property(
     *                     property="check_out",
     *                     type="time",
     *                     example="10:00 PM",
     *                     description="The check_out time of the employee"
     *                 ),
     *                 @OA\Property(
     *                     property="holiday",
     *                     type="check_box",
     *                     example="1",
     *                     description="If the specific day is a holiday then check this checkbox and leave the check_in and check_out fields null or empty. If the box is checked the input will be sent from the frontend as '1' if not checked then input will be sent as '0' from frontend side"
     *                 ),
     *                 @OA\Property(
     *                     property="date",
     *                     type="date",
     *                     example="2024-12-23",
     *                     description="The date for which you are defining the roster (required)"
     *                 )
     *             )
     *         )
     *     ),
     *      @OA\Response(response="200", description="Successful operation"),
     *      @OA\Response(response="401", description="Unauthorized")
     * )
     */




    public function store(CreateAttendanceRosterRequest $request)
    {
        DB::beginTransaction();
        try {
            // getting the tenant_id
            $user = Auth::user();
            $tenant = $user->tenant;
            $tenant_id = $tenant->id;
            

            $roster_data = $request->validated();
            $roster_data['tenant_id'] = $tenant_id;

            $latest_entry = AttendanceRoster::withTrashed()->latest()->first();
            if (!($latest_entry)) {
                $batch_id = 0;
            } else {
                $batch_id = $latest_entry->batch_id;
            }
            $batch_id = $batch_id + 1;

            $roster_array = json_decode($request->input('roster_json'), true);

            $all_rosters = [];

            foreach ($roster_array as $data) {
                // Check if there is a double entry for the same employee on the same date
                $double_roster = AttendanceRoster::where('employee_id', $request->input('employee_id'))
                    ->where('date', $data['date'])
                    ->first();

                if ($double_roster) {
                    return response()->json([
                        'message' => 'Double Entry Detected At ' . $data['date'] . ': An entry for this employee on the same date already exists'
                    ]);
                }


                if ($data['check_in'] !== null && $data['check_out'] !== null) {

                    // Validate the format of the date field
                    $validationRules = [
                        'date' => 'required|date_format:Y-m-d',
                        'check_in' => 'nullable|date_format:h:i A',
                        'check_out' => 'nullable|date_format:h:i A',
                        'holiday' => 'required|boolean',
                    ];
                } else {
                    $validationRules = [
                        'date' => 'required|date_format:Y-m-d',
                        'check_in' => 'nullable',
                        'check_out' => 'nullable',
                        'holiday' => 'required|boolean',
                    ];
                }
                $validator = Validator::make($data, $validationRules);

                if ($validator->fails()) {
                    return response()->json([
                        'message' => 'Validation Error: Please check your input fields at ' . $data['date'],
                        'errors' => $validator->errors()->all(),
                    ]);
                }

                $check_in = $data['check_in'];


                $new_roster = AttendanceRoster::create([
                    'tenant_id' => $tenant_id,
                    'employee_id' => $request->input('employee_id'),
                    'batch_id' => $batch_id,
                    'check_in' => $check_in,
                    'check_out' => $data['check_out'],
                    'holiday' => $data['holiday'],
                    'date' => $data['date'],
                ]);


                // check if the day is assigned to holiday with the check_in and check_out time recorded
                if ($new_roster->holiday == 1  && ($new_roster->check_in != null || $new_roster->check_out != null)) {
                    return response()->json([
                        'message' => 'Error At' . $data['date'] . ':Unable to set this date to holiday  with check-in or check-out time recorded. Please remove check-in/check-out time before setting this day to holiday.'
                    ]);
                }
                // Check if the day is not marked as a holiday and either check-in or check-out time is missing
                if ($new_roster->holiday == 0  && ($new_roster->check_in == null || $new_roster->check_out == null)) {
                    return response()->json([

                        'message' => 'Incomplete Entry At' . $data['date'] . ': Since this day is not marked as a holiday, both check-in and check-out times are required.'
                    ]);
                }

                $all_rosters[] = $new_roster;
            }

            DB::commit();
            return response()->json([
                'message' => 'Roster created successfully',
                'Roster' => $all_rosters
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => 'There was an error',
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * @OA\Get(
     *      path="/api/attendance-roster/{id}",
     *      summary="GET The attendance-roster.Permission required = attendance-roster.edit",
     *      description="This endpoint Gives a specific  attendance-roster.",
     *      tags={"Attendance Roster"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="The ID of the attendance-roster ",
     *         @OA\Schema(
     *             type="integer",
     *             format="int64"
     *         )
     *     ),
     *      @OA\Response(response="200", description="Successful operation"),
     *      @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function show(string $id)
    {
        DB::beginTransaction();
        try {
            $roster = AttendanceRoster::findOrFail($id);
            if (isEmpty($roster)) {
                return response()->json([
                    'message' => 'The roster could not be found'
                ]);
            }
            DB::commit();
            return response()->json([
                'message' => 'Roster retrieved successfully',
                'Roster' => $roster
            ]);
        }  catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => 'There was an error',
                'error' => $e->getMessage(),
            ]);
        }
    }


    /**
     * @OA\Patch(
     *     path="/api/attendance-roster/{id}",
     *     summary="Update the attendance-roster.Permission required = attendance-roster.update",
     *     description="This endpoint updates a attendance-roster.",
     *     tags={"Attendance Roster"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="The ID of the attendance-roster to be updated",
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
     *                     type="integer",
     *                     example="1",
     *                     description="The id of employee that is needed to assign to the roster  =>(required)"
     *                 ),
     *     @OA\Property(
     *                     property="check_in",
     *                     type="time",
     *                     example="10:00 PM",
     *                     description="The entry time of the employee "
     *                 ),

     *     @OA\Property(
     *                     property="check_out",
     *                     type="time",
     *                     example="10:00 PM",
     *                     description="The check_out time of the employee "
     *                 ),
     *
     *      @OA\Property(
     *                     property="holiday",
     *                     type="check_box",
     *                     example="1",
     *                     description="If the specific day is a holiday then check this check box and leave the check_in and check_out fields null or empty"
     *                 ),
     *     @OA\Property(
     *                     property="date",
     *                     type="date",
     *                     example="2024-12-23",
     *                     description="The date for which u are defining the roster => required"
     *                 ),
     *             )
     *         )
     *     ),
     *      @OA\Response(response="200", description="Successful operation"),
     *      @OA\Response(response="401", description="Unauthorized")
     * )
     */


    public function update(CreateAttendanceRosterRequest $request, string $id)
    {
        DB::beginTransaction();
        try {
            $roster = AttendanceRoster::findOrFail($id);
            $roster_data = $request->validated();
            $roster->update($roster_data);
            DB::commit();
            return response()->json([
                'message' => 'Roster updated successfully',
                'Roster' => $roster
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => 'There was an error',
                'error' => $e->getMessage(),
            ]);
        }
    }

    /** @OA\Delete(
     *      path="/api/attendance-roster/{id}",
     *      summary="Delete The attendance-roster.Permission required = attendance-roster.delete",
     *      description="This endpoint delete attendance-roster.",
     *      tags={"Attendance Roster"},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="The ID of the attendance-roster to be deleted",
     *         @OA\Schema(
     *             type="integer",
     *             format="int64"
     *         )
     *     ),
     *      @OA\Response(response="200", description="Successful operation"),
     *      @OA\Response(response="401", description="Unauthorized"),
     * )
     */

    public function destroy(AttendanceRoster $roster, string $id)
    {


        DB::beginTransaction();
        try {
            $roster = AttendanceRoster::findOrFail($id);
            $roster->delete();
            DB::commit();
            return response()->json([
                'message' => 'Roster deleted successfully',
            ]);
        }  catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => 'There was an error',
                'error' => $e->getMessage(),
            ]);
        }
    }
}
