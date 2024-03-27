<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateDutyRequest;
use App\Models\Company;
use App\Models\Duty;
use App\Models\Employee;
use App\Models\Media;
use App\Models\Policy;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * @OA\Tag(
 *     name="Duty",
 *     description="Handling the crud of Duty in it."
 * )
 */
class DutyController extends Controller
{
    public function __construct()
    {
        // Apply middleware to all methods in the controller
        $this->middleware('checkPermission:duty.list')->only('index', 'inactiveDuties');
        $this->middleware('checkPermission:duty.create')->only('create');
        $this->middleware('checkPermission:duty.store')->only('store', 'GetEmployee', 'searchEmployee');
        $this->middleware('checkPermission:duty.edit')->only('show');
        $this->middleware('checkPermission:duty.update')->only('update');
        $this->middleware('checkPermission:duty.delete')->only('delete');
    }

    /**
     * Display a listing of the resource.
     */
    /**
     * @OA\Get(
     *      path="/api/duty",
     *      summary="Get All active duties.Permission required = duty.list",
     *      description="This endpoint retrieves information about something.",
     *      tags={"Duty"},
     *
     *      @OA\Response(response="200", description="Successful operation"),
     *      @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function index()
    {
        $LoggedInUser = auth::user();
        $loggedInTenant = $LoggedInUser->tenant;
        $loggedInTenantId = $loggedInTenant->id;
        $duties = Duty::where('tenant_id', $loggedInTenantId)
            ->where('status', '1')
            ->get();
        foreach ($duties as $duty) {
            $duty->load(['tenant:id,name', 'company:id,name', 'employee:id,name,emirates_id',  'policy:id,name', 'equipments:id,title']);
        }

        return response()->json([
            'message' => 'All the active duties are retrived successfully',
            'duty'    => $duties,
        ]);
    }

    /**
     * @OA\Get(
     *      path="/api/duty/inactive-duties",
     *      summary="Get All inactive duties.Permission required = duty.list",
     *      description="This endpoint retrieves information about something.",
     *      tags={"Duty"},
     *
     *      @OA\Response(response="200", description="Successful operation"),
     *      @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function inactiveDuties()
    {
        $LoggedInUser = auth::user();
        $loggedInTenant = $LoggedInUser->tenant;
        $loggedInTenantId = $loggedInTenant->id;
        $duties = Duty::where('tenant_id', $loggedInTenantId)
            ->where('status', '0')
            ->get();
        foreach ($duties as $duty) {
            $duty->load(['tenant:id,name', 'company:id,name', 'employee:id,name,emirates_id',  'policy:id,name', 'equipments:id,title']);
        }

        return response()->json([
            'message' => 'All the inactive duties are retrived successfully',
            'duty'    => $duties,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    /**
     * @OA\Get(
     *      path="/api/duty/create",
     *      summary="Show the form for creating the new duty with the given data.Permission required = duty.create ",
     *      description="This endpoint retrieves information about something.",
     *      tags={"Duty"},
     *
     *      @OA\Response(response="200", description="Successful operation"),
     *      @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function create()
    {
        $LoggedInUser = auth::user();
        $loggedInTenant = $LoggedInUser->tenant;
        $loggedInTenantId = $loggedInTenant->id;
        $companies = Company::where('status', '1')
            ->where('tenant_id', $loggedInTenantId)
            ->get();
        $policies = Policy::where('status', '1')
            ->where('tenant_id', $loggedInTenantId)
            ->get();
        $Employee = Employee::where('status', '1')
            ->where('tenant_id', $loggedInTenantId)
            ->get();

        return response()->json([
            'message'   => 'These are all the active companies, policies and dutys',
            'Companies' => $companies,
            'Policies'  => $policies,
            'Employees' => $Employee,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */

    /**
     * @OA\Post(
     *     path="/api/duty",
     *     summary="Create a new duty.Permission required = duty.store",
     *     description="This endpoint creates a new duty.",
     *     tags={"Duty"},
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
     *                     property="employee_id",
     *                     type="number",
     *                     example="1",
     *                     description="The employee_id of the duty => required"
     *                 ),
     *  @OA\Property(
     *                     property="company_id",
     *                     type="number",
     *                     example="1",
     *                     description="The company_id of the duty =>required"
     *                 ),
     *    @OA\Property(
     *                     property="policy_id",
     *                     type="number",
     *                     example="1",
     *                     description="The policy_id of the duty =>required"
     *                 ),
     *  @OA\Property(
     *                     property="note",
     *                     type="string",
     *                     example="This duty is assigned to this duty",
     *                     description="The note of the duty =>nullable"
     *                 ),
     *  @OA\Property(
     *                     property="joining_date",
     *                     type="date",
     *                     example="2023-11-11",
     *                     description="The joining_date of the duty =>nullable"
     *                 ),
     *    @OA\Property(
     *                     property="equipment_ids",
     *                     type="string",
     *                     example="1,2,3",
     *                     description="Give the ids of the equipments selected by the user using the checkboxes and please return the ids in a string seprated by commas =>required"
     *                 ),
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response="201", description="duty created successfully"),
     *     @OA\Response(response="401", description="Unauthorized"),
     *     @OA\Response(response="422", description="Validation failed")
     * )s
     */
    public function store(CreateDutyRequest $dutydata)
    {
        DB::beginTransaction();

        try {
            $dutyData = $dutydata->validated();
            $LoggedInUser = auth::user();
            $loggedInTenant = $LoggedInUser->tenant;
            $loggedInTenantId = $loggedInTenant->id;
            $AdditionaldutyData = [
                'user_id'   => $LoggedInUser->id,
                'tenant_id' => $loggedInTenantId,
            ];
            $MergedData = array_merge($dutyData, $AdditionaldutyData);
            // Check if there is an active duty for the employee
            $active_duty = Duty::where('employee_id', $dutydata->input('employee_id'))
                ->where('status', '1')->count();
            if ($active_duty > 0) {
                return response()->json([
                    'message' => 'The duty for this employee has already been assigned.',
                ]);
            }

            $duty = Duty::create($MergedData);
            $newduty = $duty->refresh();
            if ($dutydata->has('equipment_ids')) {
                // Assuming $equipmentIds is an string of equipment IDs

                $equipmentIds = $dutydata->input('equipment_ids', []);
                // coverting to array

                $equipmentIds = explode(',', $equipmentIds);

                // Attach each equipment to the duty
                $newduty->equipments()->sync($equipmentIds);
            }

            DB::commit();

            return response()->json([
                'message'     => 'The duty is created',
                'dutycreated' => $duty,

            ]);
        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'message' => 'There was an error',
                'error'   => $e->getMessage(),
            ]);
        }
    }

    /**
     * Display the specified resource.
     */
    /**
     * @OA\Get(
     *      path="/api/duty/{id}",
     *      summary="GET The duty.Permission required = duty.edit",
     *      description="This endpoint Gives a specific  duty.",
     *      tags={"Duty"},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="The ID of the duty ",
     *
     *         @OA\Schema(
     *             type="integer",
     *             format="int64"
     *         )
     *     ),
     *
     *      @OA\Response(response="200", description="Successful operation"),
     *      @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function show(Duty $duty)
    {
        $duty->load(['tenant:id,name', 'company:id,name', 'employee:id,name,emirates_id',  'policy:id,name', 'equipments:id,title']);

        return response()->json([
            'message' => 'This is the required duty',
            'duty'    => $duty,
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
     * @OA\Post(
     *      path="/api/duty/GetEmployee",
     *      summary="GET The Employee.Permission required = duty.store",
     *      description="This endpoint gives a specific employee. You just need to enter the emirates id of the employee, and it will return you the employee.",
     *      tags={"Duty"},
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
     *                     description="The emirates_id of the duty (required)"
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
            // Handling the profile image
            if (!is_null($employee->profile_image_id)) {
                $profile_media = Media::find($employee->profile_image_id);
                if ($profile_media) {
                    $profile_media_url = asset("storage/{$profile_media->media_path}");
                    $employee->profile_image_id = $profile_media_url;
                }
            }

            return response()->json([
                'message'  => 'This is your required employee',
                'employee' => $employee,
            ]);
        }
    }

    /**
     * @OA\Post(
     *      path="/api/duty/search-employee",
     *      summary="GET The Employee.Permission required = duty.store",
     *      description="This endpoint gives a specific employee. You just need to enter the emirates id of the employee, and it will return you the employee.",
     *      tags={"Duty"},
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
     *                     property="searchdata",
     *                     type="string",
     *                     example="anas or 1212",
     *                     description="The emirates_id or the name  of the employee (required)"
     *                 )
     *             )
     *         )
     *     ),
     *
     *      @OA\Response(response="200", description="Successful operation"),
     *      @OA\Response(response="401", description="Unauthorized")
     * )
     */
    public function searchEmployee(Request $request)
    {
        if (!$request->filled('searchdata')) {
            return response()->json([
                'message' => 'The search data field is required',
            ]);
        }
        $user = auth::user();
        $tenant = $user->tenant;
        $tenant_id = $tenant->id;
        $input = $request->input('searchdata');
        $employees = Employee::where(function ($query) use ($input) {
            $query->where('name', 'LIKE', '%'.$input.'%')
                ->orWhere('emirates_id', 'LIKE', '%'.$input.'%');
        })
            ->where(function ($query) {
                $query->whereDoesntHave('duties')
                    ->orWhereDoesntHave('duties', function ($subQuery) {
                        $subQuery->where('status', 1);
                    });
            })
            ->where('tenant_id', $tenant_id)
            ->select('id', 'name', 'emirates_id')
            ->get();

        return response()->json([
            'message'   => 'This is the list of all the employees whose duty is not assigned or is inactive',
            'employees' => $employees,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    /**
     * @OA\Patch(
     *     path="/api/duty/{id}",
     *     summary="Update the duty.Permission required = duty.update",
     *     description="This endpoint updates a duty.",
     *     tags={"Duty"},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="The ID of the duty to be updated",
     *
     *         @OA\Schema(
     *             type="integer",
     *             format="int64"
     *         )
     *     ),
     *
     *     @OA\RequestBody(
     *         required=false,
     *
     *         @OA\MediaType(
     *             mediaType="application/x-www-form-urlencoded",
     *
     *             @OA\Schema(
     *                 type="object",
     *
     *                 @OA\Property(
     *                     property="employee_id",
     *                     type="number",
     *                     example="1",
     *                     description="The employee_id of the duty => required"
     *                 ),
     *  @OA\Property(
     *                     property="company_id",
     *                     type="number",
     *                     example="1",
     *                     description="The company_id of the duty =>required"
     *                 ),
     *    @OA\Property(
     *                     property="policy_id",
     *                     type="number",
     *                     example="1",
     *                     description="The policy_id of the duty =>required"
     *                 ),
     *  @OA\Property(
     *                     property="note",
     *                     type="string",
     *                     example="This duty is assigned to this duty",
     *                     description="The note of the duty =>nullable"
     *                 ),
     *  @OA\Property(
     *                     property="joining_date",
     *                     type="date",
     *                     example="2023-11-11",
     *                     description="The joining_date of the duty =>nullable"
     *                 ),
     *  @OA\Property(
     *                     property="status",
     *                     type="string",
     *                     example="1",
     *                     description="The status of the duty =>required"
     *                 ),
     *     @OA\Property(
     *                     property="equipment_ids",
     *                     type="string",
     *                     example="1,2,3",
     *                     description="Give the ids of the equipments selected by the user using the checkboxes and please return the ids in a string seprated by commas =>required"
     *                 ),
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response="201", description="duty created successfully"),
     *     @OA\Response(response="401", description="Unauthorized"),
     *     @OA\Response(response="422", description="Validation failed")
     * )
     */
    public function update(CreateDutyRequest $dutydata, Duty $duty)
    {
        DB::beginTransaction();

        try {
            $dutyData = $dutydata->validated();
            $LoggedInUser = auth::user();
            $loggedInTenant = $LoggedInUser->tenant;
            $loggedInTenantId = $loggedInTenant->id;
            $AdditionaldutyData = [
                'user_id'   => $LoggedInUser->id,
                'tenant_id' => $loggedInTenantId,
            ];
            $MergedData = array_merge($dutyData, $AdditionaldutyData);
            $duty->update($MergedData);
            $newduty = $duty->refresh();

            if ($dutydata->has('equipment_ids')) {
                // Assuming $equipmentIds is an string of equipment IDs

                $equipmentIds = $dutydata->input('equipment_ids', []);
                // coverting to array

                $equipmentIds = explode(',', $equipmentIds);

                // Attach each equipment to the duty
                $newduty->equipments()->sync($equipmentIds);
            }
            DB::commit();

            return response()->json([
                'message'      => 'The duty is updated',
                'duty updated' => $duty,

            ]);
        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'message' => 'There was an error',
                'error'   => $e->getMessage(),
            ]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */

    /**
     * @OA\Delete(
     *      path="/api/duty/{id}",
     *      summary="Delete The duty.Permission required = duty.delete",
     *      description="This endpoint delete duty.",
     *      tags={"Duty"},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="The ID of the duty to be deleted",
     *
     *         @OA\Schema(
     *             type="integer",
     *             format="int64"
     *         )
     *     ),
     *
     *      @OA\Response(response="200", description="Successful operation"),
     *      @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function destroy(Duty $duty)
    {
        DB::beginTransaction();

        try {
            $duty->delete();
            DB::commit();

            return response()->json([
                'message'     => 'The duty is deleted',
                'dutydeleted' => $duty,
            ]);
        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'message' => 'There was an error',
                'error'   => $e->getMessage(),
            ]);
        }
    }
}
