<?php
namespace App\Http\Controllers\API;



use App\Http\Controllers\Controller;
use App\Models\Duty;
use App\Models\Employee;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\Resignation;
use App\Models\Guard;
use App\Models\Company;
use App\Http\Requests\ResignationRequest;



/**
 * @OA\Tag(
 *     name="Resignation",
 *     description="Handling the crud of resignation in it."
 * )
 */
class ResignationController extends Controller
{

    /**
     * @OA\Get(
     *      path="/api/resignation",
     *      summary="Get All active resignations.Permission required = resignation.list",
     *      description="This endpoint retrieves information about something.",
     *      tags={"Resignation"},
     *      @OA\Response(response="200", description="Successful operation"),
     *      @OA\Response(response="401", description="Unauthorized"),
     * )
     */

    public function __construct()
    {
        // Apply middleware to all methods in the controller
        $this->middleware('checkPermission:resignation.list')->only('index','inactiveResignations');
        $this->middleware('checkPermission:resignation.create')->only('create');
        $this->middleware('checkPermission:resignation.store')->only('store','searchEmployee');
        $this->middleware('checkPermission:resignation.edit')->only('show');
        $this->middleware('checkPermission:resignation.update')->only('update');
        $this->middleware('checkPermission:resignation.delete')->only('delete');
    }

    public function index()
    {
        $user = auth::user();
        if (!($user->tenant)) {
            return response()->json([
                'message' => 'you can not access it'
            ]);
        }
        $tenant_id = $user->tenant->id;
        $resignation = Resignation::where('tenant_id', $tenant_id)
        ->where('status','1')
        ->get();
        return response()->json([
            'message' => 'List of all active employee resignations',
            'resignations ' => $resignation
        ]);
    }
        /**
     * @OA\Get(
     *      path="/api/resignation/inactive-resignations",
     *      summary="Get inactive All resignations.Permission required = resignation.list",
     *      description="This endpoint retrieves information about something.",
     *      tags={"Resignation"},
     *      @OA\Response(response="200", description="Successful operation"),
     *      @OA\Response(response="401", description="Unauthorized"),
     * )
     */

     public function inactiveResignations()
    {
        $user = auth::user();
        if (!($user->tenant)) {
            return response()->json([
                'message' => 'you can not access it'
            ]);
        }
        $tenant_id = $user->tenant->id;
        $resignation = Resignation::where('tenant_id', $tenant_id)
        ->where('status','0')
        ->get();
        return response()->json([
            'message' => 'List of all inactive employee resignations',
            'resignations ' => $resignation
        ]);
    }

    /**
     * @OA\post(
     *      path="/api/resignation/get-employee",
     *      summary="GET The Employee.Permission required = resignation.store",
     *      description="This endpoint gives a specific employee. You just need to enter the emirates id of the employee, and it will return you the employee.",
     *      tags={"Resignation"},
     *      @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="application/x-www-form-urlencoded",
     *             @OA\Schema(
     *                 type="object",
     *                 @OA\Property(
     *                     property="emirates_id",
     *                     type="number",
     *                     example="12121211212",
     *                     description="The emirates_id of the employee (required)"
     *                 )
     *             )
     *         )
     *     ),
     *      @OA\Response(response="200", description="Successful operation"),
     *      @OA\Response(response="401", description="Unauthorized")
     * )
     */

    public function searchEmployee(Request $request)
    {
        $emirates_id = $request->input('emirates_id');
        $loggedin_user = auth::user();
        $tenant = $loggedin_user->tenant;
        $tenant_id = $tenant->id;
        $employee = Employee::where('emirates_id', $emirates_id)
            ->where('tenant_id', $tenant_id)
            ->whereHas('duties', function ($query) {
                $query->where('status', 1);
            })->get();

        if ($employee == null) {
            return response()->json([
                'message' => 'Oops! No employee found with the provided Emirates ID.',
                'status' => 'error'
            ], 404);
        } else {
            return response()->json([
                'message' => 'This is your required employee',
                'employee' => $employee
            ]);
        }
    }



    /**
     * @OA\Post(
     *     path="/api/resignation",
     *     summary="Create a new resignation.Permission required = resignation.store",
     *     description="This endpoint creates a new resignation.",
     *     tags={"Resignation"},
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="application/x-www-form-urlencoded",
     *             @OA\Schema(
     *                 type="object",
     *                 @OA\Property(
     *                     property="employee_id",
     *                     type="number",
     *                     example="1",
     *                     description="The employee_id of the employee needed to be resigned => required"
     *                 ),
     *
     *  @OA\Property(
     *                     property="reason",
     *                     type="text",
     *                     example="This is my reason",
     *                     description="The reason of the resignation =>nullable"
     *                 ),
     *
     *  @OA\Property(
     *                     property="equipment_status",
     *                     type="string",
     *                     example="ok",
     *                     description=" nullable"
     *                 ),
     *             )
     *         )
     *     ),
     *     @OA\Response(response="201", description="resignation created successfully"),
     *     @OA\Response(response="401", description="Unauthorized"),
     *     @OA\Response(response="422", description="Validation failed")
     * )fd
     */

    public function store(ResignationRequest $resignation_request)
    {
        DB::beginTransaction();
        try {
            $resignation_request = $resignation_request->validated();
            $loggedin_user = auth::user();
            $loggedin_tenant = $loggedin_user->tenant;
            $loggedin_tenant_id = $loggedin_tenant->id;
            $AdditionaldutyData = [
                'user_id' => $loggedin_user->id,
                'tenant_id' => $loggedin_tenant_id
            ];

            $resignation_request = $resignation_request->validated();

            $employee = Employee::where('id', $resignation_request->input('employee_id'))->first();
            $duty = $employee->duties()->where('status', 1)->first();

            if ($duty->isNotEmpty()) {

                $duty->status = 0;
                $duty->update();
            }
            $duty_id = $duty->id;
            if ($employee->isNotEmpty()) {
                $user_id = $employee->user_id;
                $user = User::where('id', $user_id)->first();
                $user->status = '0';
                $user->update();

                // Submit Resignation
                $resignation = Resignation::create([
                    'tenant_id' => $loggedin_tenant_id,
                    'employee_id' => $resignation_request->employee_id,
                    'duty_id ' => $duty_id,
                    'note ' => $resignation_request->note,
                    'equipment_status ' => $resignation_request->equipment_status
                ]);

                DB::commit();
                return response()->json([
                    'message' => 'Resignation has been submitted successfully',
                    'data' => $resignation
                ]);
            } else {
                return response()->json([
                    'message' => 'Oops! No employee could be found'
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
     * @OA\Delete(
     *      path="/api/resignation/{id}",
     *      summary="Delete The resignation.Permission required = resignation.delete",
     *      description="This endpoint delete resignation.",
     *      tags={"Resignation"},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="The ID of the resignation to be deleted",
     *         @OA\Schema(
     *             type="integer",
     *             format="int64"
     *         )
     *     ),
     *      @OA\Response(response="200", description="Successful operation"),
     *      @OA\Response(response="401", description="Unauthorized"),
     * )
     */

    public function destroy(Resignation $resignation)
    {
        DB::beginTransaction();
        try {
            $resignation->delete();
            DB::commit();
            return response()->json([
                'message' => 'Resignation record deleted successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'status' => 'error',
                'message' => 'There is an error on deleting resignation',
                'error' => $e->getMessage()
            ]);
        }
    }
}
