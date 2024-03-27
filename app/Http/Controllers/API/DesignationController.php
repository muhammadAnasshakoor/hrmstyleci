<?php

namespace App\Http\Controllers\API;

use App\Models\Designation;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\CreateDesignationRequest;
use App\Jobs\CheckSubscriptionExpiryJob;

/**
 * @OA\Tag(
 *     name="Designation",
 *     description="Handling the crud of designation in it."
 * )
 */

class DesignationController  extends Controller
{
    public function __construct()
    {
        // Apply middleware to all methods in the controller
        $this->middleware('checkPermission:designation.list')->only('index', 'inactiveDesignation');
        $this->middleware('checkPermission:designation.create')->only('create');
        $this->middleware('checkPermission:designation.store')->only('store');
        $this->middleware('checkPermission:designation.edit')->only('show');
        $this->middleware('checkPermission:designation.update')->only('update');
        $this->middleware('checkPermission:designation.delete')->only('delete');
    }

    /**
     * Display a listing of the resource.
     */

    /**
     * @OA\Get(
     *      path="/api/designation",
     *      summary="Get All designations.Permission required = designation.list",
     *      description="This endpoint retrieves information about something.",
     *      tags={"Designation"},
     *      @OA\Response(response="200", description="Successful operation"),
     *      @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function index()
    {

        $LoggedInUser = auth::user();
        $loggedInTenant = $LoggedInUser->tenant;
        $loggedInTenantId = $loggedInTenant->id;
        $designations = Designation::where('tenant_id', $loggedInTenantId)
            ->where('status', '1')
            ->get();
        return response()->json([
            'message' => 'Successfully retrieved the list of all active designations.',
            'designation' => $designations
        ]);
    }
    /**
     * @OA\Get(
     *      path="/api/designation/inactive-designations",
     *      summary="Get All designations.Permission required = designation.list",
     *      description="This endpoint retrieves information about something.",
     *      tags={"Designation"},
     *      @OA\Response(response="200", description="Successful operation"),
     *      @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function inactiveDesignation()
    {

        $LoggedInUser = auth::user();
        $loggedInTenant = $LoggedInUser->tenant;
        $loggedInTenantId = $loggedInTenant->id;
        $designations = Designation::where('tenant_id', $loggedInTenantId)
            ->where('status', '0')
            ->get();
        return response()->json([
            'message' => 'Successfully retrieved the list of all inactive designations.',
            'designation' => $designations
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
     *     path="/api/designation",
     *     summary="Create a new designation.Permission required = designation.store",
     *     description="This endpoint creates a new designation.",
     *     tags={"Designation"},
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
     *     @OA\Response(response="201", description="designation created successfully"),
     *     @OA\Response(response="401", description="Unauthorized"),
     *     @OA\Response(response="422", description="Validation failed")
     * )fd
     */

    public function store(CreateDesignationRequest $designation)
    {

        DB::beginTransaction();
        try {
            $DesignationData = $designation->validated();
            $LoggedInUser = auth::user();
            $loggedInTenant = $LoggedInUser->tenant;
            $loggedInTenantId = $loggedInTenant->id;
            $AdditionalDesignationData = [
                'user_id' => $LoggedInUser->id,
                'tenant_id' => $loggedInTenantId
            ];
            $MergedData = array_merge($DesignationData, $AdditionalDesignationData);

            $is_existing_designation = Designation::where('tenant_id', $loggedInTenantId)
                ->where('title', $MergedData['title'])
                ->exists();

            if ($is_existing_designation) {
                return response()->json([
                    'error' => 'conflict',
                    'message' => 'A designation with the same name already exists.'
                ], 409); // HTTP status code 409 indicates a conflict
            }

            $NewDesignation = Designation::create($MergedData);
            DB::commit();
            return response()->json([
                'message' => 'The designation is created',
                'designation' => $NewDesignation
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
     * Display the specified resource.
     */

    /**
     * @OA\Get(
     *      path="/api/designation/{id}",
     *      summary="GET The designation.Permission required = designation.edit",
     *      description="This endpoint Gives a specific  designation.",
     *      tags={"Designation"},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="The ID of the designation ",
     *         @OA\Schema(
     *             type="integer",
     *             format="int64"
     *         )
     *     ),
     *      @OA\Response(response="200", description="Successful operation"),
     *      @OA\Response(response="401", description="Unauthorized"),
     * )
     */

    public function show(Designation $designation)
    {
        return response()->json([
            'message' => 'this is your designation',
            'designation' => $designation
        ]);
    }


    /**
     * Update the specified resource in storage.
     */

    /**
     * @OA\Patch(
     *     path="/api/designation/{id}",
     *     summary="Update the designation.Permission required = designation.update",
     *     description="This endpoint updates a designation.",
     *     tags={"Designation"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="The ID of the designation to be updated",
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
     *                     description="The status of the designation =>required"
     *                 ),
     *             )
     *         )
     *     ),
     *     @OA\Response(response="201", description="designation created successfully"),
     *     @OA\Response(response="401", description="Unauthorized"),
     *     @OA\Response(response="422", description="Validation failed")
     * )fd
     */

    public function update(CreateDesignationRequest $designationdata, Designation $designation)
    {

        DB::beginTransaction();
        try {
            $DesignationData = $designationdata->validated();
            $LoggedInUser = auth::user();
            $loggedInTenant = $LoggedInUser->tenant;
            $loggedInTenantId = $loggedInTenant->id;
            $AdditionalDesignationData = [
                'user_id' => $LoggedInUser->id,
                'tenant_id' => $loggedInTenantId
            ];
            $MergedData = array_merge($DesignationData, $AdditionalDesignationData);
            if ($designation->employee) {

                DB::commit();
                return response()->json([
                    'message' => 'Oops! You cannot update a designation that is currently assigned to an employee.'
                ]);
            } else {

                    $is_existing_designation = Designation::where('tenant_id', $loggedInTenantId)
                        ->where('title', $MergedData['title'])
                        ->where('id', '!=', $designation->id)
                        ->exists();

                    if ($is_existing_designation) {
                        return response()->json([
                            'error' => 'conflict',
                            'message' => 'A designation with the same name already exists.'
                        ], 409); // HTTP status code 409 indicates a conflict
                    }
                $designation->update($MergedData);

                DB::commit();
                return response()->json([
                    'message' => 'Designation updated successfully.',
                    'designation' => $designation
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

    /**
     * @OA\Delete(
     *      path="/api/designation/{id}",
     *      summary="Delete The designation.Permission required = designation.delete",
     *      description="This endpoint delete designation.",
     *      tags={"Designation"},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="The ID of the designation to be deleted",
     *         @OA\Schema(
     *             type="integer",
     *             format="int64"
     *         )
     *     ),
     *      @OA\Response(response="200", description="Successful operation"),
     *      @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function destroy(Designation $designation)
    {
        DB::beginTransaction();
        try {
            $employees =   $designation->employees()->where('status', '1')
                ->count();
            if ($employees > 0) {
                return response()->json([
                    'message' => 'Cannot delete designation. There are active employees associated with it.',
                ]);
            }
            $designation->delete();
            DB::commit();
            return response()->json([
                'message' => 'The designation is deleted',
                'designationdeleted' => $designation
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => 'There was an error',
                'error' => $e->getMessage(),
            ]);
        }
    }
}
