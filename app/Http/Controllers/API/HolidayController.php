<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateHolidayRequest;
use App\Models\Company;
use App\Models\Holiday;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * @OA\Tag(
 *     name="Holiday",
 *     description="Handling the crud of holiday in it."
 * )
 */
class HolidayController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    /**
     * @OA\Get(
     *      path="/api/holiday",
     *      summary="Get All active holidays.Permission required = holiday.list",
     *      description="This endpoint retrieves information about something.",
     *      tags={"Holiday"},
     *
     *      @OA\Response(response="200", description="Successful operation"),
     *      @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function __construct()
    {
        // Apply middleware to all methods in the controller
        $this->middleware('checkPermission:holiday.list')->only('index', 'inactiveHolidays');
        $this->middleware('checkPermission:holiday.create')->only('create');
        $this->middleware('checkPermission:holiday.store')->only('store');
        $this->middleware('checkPermission:holiday.edit')->only('show');
        $this->middleware('checkPermission:holiday.update')->only('update');
        $this->middleware('checkPermission:holiday.delete')->only('delete');
    }

    public function index()
    {
        $user = auth::user();
        if (!$user->tenant) {
            return response()->json([
                'message' => 'Only Tenant can access it',
            ]);
        }
        $tenant_id = $user->tenant->id;
        $holidays = Holiday::where('tenant_id', $tenant_id)
            ->where('status', '1')
            ->with('company')
            ->get();

        return response()->json([
            'message'  => 'Active Holidays retrieved successfully',
            'Holidays' => $holidays,
        ]);
    }

    /**
     * @OA\Get(
     *      path="/api/holiday/inactive-holidays",
     *      summary="Get All inactive holidays.Permission required = holiday.list",
     *      description="This endpoint retrieves information about something.",
     *      tags={"Holiday"},
     *
     *      @OA\Response(response="200", description="Successful operation"),
     *      @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function inactiveHolidays()
    {
        $user = auth::user();
        if (!$user->tenant) {
            return response()->json([
                'message' => 'Only Tenant can access it',
            ]);
        }
        $tenant_id = $user->tenant->id;
        $holidays = Holiday::where('tenant_id', $tenant_id)
            ->where('status', '0')
            ->with('company')
            ->get();

        return response()->json([
            'message'  => 'Inactive Holidays retrieved successfully',
            'Holidays' => $holidays,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    /**
     * @OA\Get(
     *      path="/api/holiday/create",
     *      summary="Get All companies that are active.Permission required = holiday.create",
     *      description="This endpoint retrieves information about something.",
     *      tags={"Holiday"},
     *
     *      @OA\Response(response="200", description="Successful operation"),
     *      @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function create()
    {
        // getting all the companies whose status are active
        $Logged_in_user = auth::user();
        $logged_in_tenant = $Logged_in_user->tenant;
        $tenant_id = $logged_in_tenant->id;

        $companies = Company::where('status', '1')->where('tenant_id', $tenant_id)->get();

        return response()->json([
            'message'   => 'This is the list of all active  companies',
            'Companies' => $companies,
        ]);
    }

    /**
     * update a newly created resource in storage.
     */
    /**
     * @OA\Post(
     *     path="/api/holiday",
     *     summary="Create a new holiday.Permission required = holiday.update",
     *     description="This endpoint creates a new holiday.",
     *     tags={"Holiday"},
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
     *                     property="company_id",
     *                     type="number",
     *                     example="1",
     *                     description="The id of the company=> required"
     *                 ),
     *  @OA\Property(
     *                     property="name",
     *                     type="string",
     *                     example="Eid holiday",
     *                     description="The name of the holiday =>required"
     *                 ),
     *  @OA\Property(
     *                     property="starting_date",
     *                     type="date",
     *                     example="2024-12-23",
     *                     description="The starting_date of the holiday =>required and it format should be yyyy-mm-dd"
     *                 ),
     *  @OA\Property(
     *                     property="ending_date",
     *                     type="date",
     *                     example="2024-12-23",
     *                     description="The ending_date of the holiday =>required and it format should be yyyy-mm-dd"
     *                 ),
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response="201", description="holiday created successfully"),
     *     @OA\Response(response="401", description="Unauthorized"),
     *     @OA\Response(response="422", description="Validation failed")
     * )fd
     */
    public function store(CreateHolidayRequest $holiday_request)
    // public function update()
    {
        DB::beginTransaction();

        try {
            $holiday_data = $holiday_request->validated();
            $Logged_in_user = auth::user();
            $logged_in_tenant = $Logged_in_user->tenant;
            $tenant_id = $logged_in_tenant->id;
            $holiday_data['tenant_id'] = $tenant_id;
            $starting_date = Carbon::parse($holiday_request->input('starting_date'));
            $ending_date = Carbon::parse($holiday_request->input('ending_date'));

            if ($ending_date->lessThan($starting_date)) {
                return response()->json([
                    'error'=> 'Invalid Date Range',
                    'hint' => 'Please ensure that the starting date comes before the ending date and try again.',
                ], 400);
            }

            $existing_holiday = Holiday::where('tenant_id', $tenant_id)
            ->WhereBetween('starting_date', [$starting_date, $ending_date])
            ->orWhereBetween('ending_date', [$starting_date, $ending_date])
            ->exists();

            if ($existing_holiday) {
                return response()->json([
                    'message' => 'The provided date range overlaps with an existing holiday. Please choose different dates.',
                ]);
            }

            $new_holiday = Holiday::create($holiday_data);

            DB::commit();

            return response()->json([
                'message' => 'Holiday is created successfully',
                'holiday' => $new_holiday,
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
     *      path="/api/holiday/{id}",
     *      summary="GET The holiday.Permission required = holiday.edit",
     *      description="This endpoint Gives a specific  holiday.",
     *      tags={"Holiday"},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="The ID of the holiday ",
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
    public function show(Holiday $holiday)
    {
        DB::beginTransaction();

        try {
            $holiday->company;
            DB::commit();

            return response()->json([
                'message' => 'This is the required holiday',
                'holiday' => $holiday,
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
     *     path="/api/holiday/{id}",
     *     summary="Update the holiday.Permission required = holiday.update",
     *     description="This endpoint updates a holiday.",
     *     tags={"Holiday"},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="The ID of the holiday to be updated",
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
     *                     property="company_id",
     *                     type="number",
     *                     example="1",
     *                     description="The id of the company=> required"
     *                 ),
     *  @OA\Property(
     *                     property="status",
     *                     type="number",
     *                     example="1",
     *                     description="The status of the holiday =>required"
     *                 ),
     *  @OA\Property(
     *                     property="name",
     *                     type="string",
     *                     example="Eid holiday",
     *                     description="The name of the holiday =>required"
     *                 ),
     *  @OA\Property(
     *                     property="starting_date",
     *                     type="date",
     *                     example="2024-12-23",
     *                     description="The starting_date of the holiday =>required and it format should be yyyy-mm-dd"
     *                 ),
     *  @OA\Property(
     *                     property="ending_date",
     *                     type="date",
     *                     example="2024-12-23",
     *                     description="The ending_date of the holiday =>required and it format should be yyyy-mm-dd"
     *                 ),
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response="201", description="holiday created successfully"),
     *     @OA\Response(response="401", description="Unauthorized"),
     *     @OA\Response(response="422", description="Validation failed")
     * )fd
     */
    public function update(CreateHolidayRequest $holiday_request, Holiday $holiday)
    {
        DB::beginTransaction();

        try {
            $holiday_data = $holiday_request->validated();
            $holiday->update($holiday_data);
            DB::commit();

            return response()->json([
                'message' => 'The holiday is updated',
                'holiday' => $holiday,

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
     *      path="/api/holiday/{id}",
     *      summary="Delete The holiday.Permission required = holiday.delete",
     *      description="This endpoint delete holiday.",
     *      tags={"Holiday"},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="The ID of the holiday to be deleted",
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
    public function destroy(Holiday $holiday)
    {
        DB::beginTransaction();

        try {
            $holiday->delete();
            DB::commit();

            return response()->json([
                'message' => 'The holiday is deleted successfully',
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
