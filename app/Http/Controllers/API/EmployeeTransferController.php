<?php

namespace App\Http\Controllers\API;

use App\Models\Company;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Duty;
use App\Models\Media;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon; // Add this line
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\CreateDutyRequest;
use App\Http\Requests\EmployeeTransferRequest;
use App\Models\Employee;
use App\Models\EmployeeTransfer;

/**
 * @OA\Tag(
 *     name="Employee Transfer"
 * )
 */
class EmployeeTransferController extends Controller
{

    /**
     * Display a listing of the resource.
     */

    /**
     * @OA\Get(
     *      path="/api/employee-transfer",
     *      summary="Get All employee transfers.Permission required = employee-transfer.list",
     *      description="This endpoint retrieves information about employee transfers.",
     *      tags={"Employee Transfer"},
     *      @OA\Response(response="200", description="Successful operation"),
     *      @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function __construct()
    {
        // Apply middleware to all methods in the controller
        $this->middleware('checkPermission:employee-transfer.list')->only('index');
        // $this->middleware('checkPermission:employee-transfer.create')->only('create');
        $this->middleware('checkPermission:employee-transfer.store')->only('store', 'newDutyForm');
        // $this->middleware('checkPermission:employee-transfer.edit')->only('show');
        // $this->middleware('checkPermission:employee-transfer.update')->only('update');
        // $this->middleware('checkPermission:employee-transfer.delete')->only('delete');
    }

    public function index()
    {
        $user = auth::user();
        $tenant = $user->tenant;
        $tenant_id = $tenant->id;
        $employee_transfers = EmployeeTransfer::where('tenant_id', $tenant_id)->get();
        foreach ($employee_transfers as $employee_transfer) {
            $employee_transfer->employee;
            $from_company_id = $employee_transfer->from_company_id;
            $to_company_id = $employee_transfer->to_company_id;
            $from_duty_id = $employee_transfer->from_duty_id;
            $to_duty_id = $employee_transfer->to_duty_id;

            $from_company_name = Company::where('id', $from_company_id)->value('name');
            $to_company_name = Company::where('id', $to_company_id)->value('name');
            $from_duty_name = Duty::where('id', $from_duty_id)->value('name');
            $to_duty_name = Duty::where('id', $to_duty_id)->value('name');

            $employee_transfer->from_company_id =     $from_company_name;
            $employee_transfer->to_company_id = $to_company_name;
            $employee_transfer->from_duty_id = $from_duty_name;
            $employee_transfer->to_duty_id = $to_duty_name;
        }

        return response()->json(
            [
                'message' => 'This is the list of all employee transfers',
                'Employee transfers' => $employee_transfers
            ]
        );
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
     *     path="/api/employee-transfer/{previous_duty}",
     *     summary="Create a new employee transfer.Permission required = employee-transfer.store",
     *     description="This endpoint creates a new employee transfer.",
     *     tags={"Employee Transfer"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="The ID of the duty to be transfered",
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
     *    @OA\Property(
     *                     property="equipment_ids",
     *                     type="string",
     *                     example="1,2,3",
     *                     description="Give the ids of the equipments selected by the user using the checkboxes and please return the ids in a string seprated by commas =>required"
     *                 ),
     *       @OA\Property(
     *                     property="reason",
     *                     type="text",
     *                     example="The duty contract was ended",
     *                     description="The reson why duty was transfered =>nullable ,frontend"
     *                 ),
     *             )
     *         )
     *     ),
     *     @OA\Response(response="201", description="duty created successfully"),
     *     @OA\Response(response="401", description="Unauthorized"),
     *     @OA\Response(response="422", description="Validation failed")
     * )s
     */












    public function store(CreateDutyRequest $duty_request, EmployeeTransferRequest $transfer_request, Duty $previous_duty)
    {


        DB::beginTransaction();
        try {

            // Update the previous duty record to inactive it
            $current_date = now()->format('Y-m-d');
            $previous_duty->update([
                'status' => 0,
                'ended_at' => $current_date
            ]);


            // getting the data of previous duty
            $previous_duty_id = $previous_duty->id;
            $previous_company_id = $previous_duty->company_id;
            $previous_employee_id = $previous_duty->employee_id;
            $previous_joining_date = $previous_duty->joining_date;

            // creating new duty
            $duty_data = $duty_request->validated();
            $loggedin_user = auth::user();
            $loggedin_tenant = $loggedin_user->tenant;
            $loggedin_tenantid = $loggedin_tenant->id;
            $additional_duty_data = [
                'user_id' => $loggedin_user->id,
                'tenant_id' =>   $loggedin_tenantid
            ];
            $merged_duty_data = array_merge($duty_data, $additional_duty_data);

            $duty = Duty::create($merged_duty_data);
            $new_duty = $duty->refresh();
            if ($duty_request->has('equipment_ids')) {
                // Assuming $equipmentIds is an string of equipment IDs
                $equipmentIds = $duty_request->input('equipment_ids', []);
                $equipmentIds = explode(',', $equipmentIds);
                $new_duty->equipments()->sync($equipmentIds);
            }

            // storing  the employee transfer details in the employee_transfers table
            $transfer_data = $transfer_request->validated();

            $additional_transfer_data = [
                'tenant_id' =>  $loggedin_tenantid,
                'employee_id' => $previous_employee_id,
                'from_company_id' =>  $previous_company_id,
                'to_company_id' => $new_duty->company_id,
                'from_duty_id' => $previous_duty_id,
                'to_duty_id' => $new_duty->id,
                'started_at' => $previous_joining_date,
                'ended_at' => $current_date

            ];

            $merged_transfer_data = array_merge($transfer_data, $additional_transfer_data);

            $new_employee_transfer = EmployeeTransfer::create($merged_transfer_data);

            DB::commit();
            return response()->json([
                'message' => 'The employee has been transfered to the new duty while the previous duty has been inactivated',
                'Employee transfer' => $new_employee_transfer,
                'new duty' => $new_duty
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
     *      path="/api/employee-transfer/{duty}",
     *      summary="GET The transfer form.Permission required = employee-transfer.store",
     *      description="This endpoint Gives a specific  duty.",
     *      tags={"Employee Transfer"},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="The ID of the duty to be transfered ",
     *         @OA\Schema(
     *             type="integer",
     *             format="int64"
     *         )
     *     ),
     *      @OA\Response(response="200", description="Successful operation"),
     *      @OA\Response(response="401", description="Unauthorized"),
     * )
     */


    public function newDutyForm(Duty $duty)
    {
        $duty->load('employee:id,name,emirates_id', 'policy:id,name', 'company:id,name', 'equipments:id,title',);

        // getting the employee and the company based on the ids in the duty
        $employee = Employee::where('id', $duty->employee_id)->first();
        $company = Company::where('id', $duty->company_id)->first();

        // Handling the profile image
        if (!is_null($employee->profile_image_id)) {
            $profile_media = Media::find($employee->profile_image_id);
            if ($profile_media) {
                $profile_media_url = asset("storage/{$profile_media->media_path}");
            }
        }

        //handling the logo of the company
        if (!is_null($company->logo_media_id)) {
            $logo_media = Media::find($company->logo_media_id);
            if ($logo_media) {
                $logo_media_url = asset("storage/{$logo_media->media_path}");
            }
        }

        return response()->json([
            'message' => 'This is the data of the duty ,with employee ,company,policy and equipment data',
            'duty' => $duty,
            'Employee Profile image' => $profile_media_url,
            'Company logo' =>   $logo_media_url

        ]);
    }
    /**
     * Display the specified resource.
     */
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
       
    }
}
