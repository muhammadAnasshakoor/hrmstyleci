<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Equipment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\CreateEquipmentRequest;




/**
 * @OA\Tag(
 *     name="Equipment",
 *     description="Handling the crud of equipment in it."
 * )
 */


class EquipmentController extends Controller
{
    public function __construct()
    {
        // Apply middleware to all methods in the controller
        $this->middleware('checkPermission:equipment.list')->only('index','inactiveEquipments');
        $this->middleware('checkPermission:equipment.create')->only('create');
        $this->middleware('checkPermission:equipment.store')->only('store');
        $this->middleware('checkPermission:equipment.edit')->only('show');
        $this->middleware('checkPermission:equipment.update')->only('update');
        $this->middleware('checkPermission:equipment.delete')->only('delete');
    }
    /**
     * Display a listing of the resource.
     */

    /**
     * @OA\Get(
     *      path="/api/equipment",
     *      summary="Get All active equipments.Permission required = equipment.list",
     *      description="This endpoint retrieves information about something.",
     *      tags={"Equipment"},
     *      @OA\Response(response="200", description="Successful operation"),
     *      @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function index()
    {
        $LoggedInUser = auth::user();
        $loggedInTenant = $LoggedInUser->tenant;
        $loggedInTenantId = $loggedInTenant->id;

        $equipments = Equipment::where('tenant_id', $loggedInTenantId)
        ->where('status','1')
        ->get();
        return response()->json([
            'message' => 'Active equipments retrieved successfully',
            'equipment' => $equipments
        ]);
    }

    /**
     * @OA\Get(
     *      path="/api/equipment/inactive-equipments",
     *      summary="Get All inactive equipments.Permission required = equipment.list",
     *      description="This endpoint retrieves information about something.",
     *      tags={"Equipment"},
     *      @OA\Response(response="200", description="Successful operation"),
     *      @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function inactiveEquipments()
    {
        $LoggedInUser = auth::user();
        $loggedInTenant = $LoggedInUser->tenant;
        $loggedInTenantId = $loggedInTenant->id;

        $equipments = Equipment::where('tenant_id', $loggedInTenantId)
        ->where('status','0')
        ->get();
        return response()->json([
            'message' => 'Inactive equipments retrieved successfully',
            'equipment' => $equipments
        ]);
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
    /**
     * @OA\Post(
     *     path="/api/equipment",
     *     summary="Create a new equipment.Permission required = equipment.store",
     *     description="This endpoint creates a new equipment.",
     *     tags={"Equipment"},
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="application/x-www-form-urlencoded",
     *             @OA\Schema(
     *                 type="object",
     *                 @OA\Property(
     *                     property="title",
     *                     type="string",
     *                     example="gun",
     *                     description="The name of the equipment => required"
     *                 ),
     *             )
     *         )
     *     ),
     *     @OA\Response(response="201", description="equipment created successfully"),
     *     @OA\Response(response="401", description="Unauthorized"),
     *     @OA\Response(response="422", description="Validation failed")
     * )
     */

    public function store(CreateEquipmentRequest $equipment)
    {

        DB::beginTransaction();
        try {
            $equipmentData = $equipment->validated();
            $LoggedInUser = auth::user();
            $loggedInTenant = $LoggedInUser->tenant;
            $loggedInTenantId = $loggedInTenant->id;
            $AdditionalequipmentData = [
                'user_id' => $LoggedInUser->id,
                'tenant_id' => $loggedInTenantId
            ];
            $MergedData = array_merge($equipmentData, $AdditionalequipmentData);


            $Newequipment = equipment::create($MergedData);
            DB::commit();
            return response()->json([
                'message' => 'The equipment is created',

                'equipment' => $Newequipment
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
     *      path="/api/equipment/{id}",
     *      summary="GET The equipment.Permission required = equipment.edit",
     *      description="This endpoint Gives a specific  equipment.",
     *      tags={"Equipment"},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="The ID of the equipment ",
     *         @OA\Schema(
     *             type="integer",
     *             format="int64"
     *         )
     *     ),
     *      @OA\Response(response="200", description="Successful operation"),
     *      @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function show(Equipment $equipment)
    {
        return response()->json([
            'message' => 'this is your equipment',
            'equipment' => $equipment
        ]);
    }


    /**
     * Update the specified resource in storage.
     */


    /**
     * @OA\Patch(
     *     path="/api/equipment/{id}",
     *     summary="Update the equipment.Permission required = equipment.update",
     *     description="This endpoint updates a equipment.",
     *     tags={"Equipment"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="The ID of the equipment to be updated",
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
     *                     example="gun",
     *                     description="The name of the equipment => required"
     *                 ),
     *  @OA\Property(
     *                     property="status",
     *                     type="string",
     *                     example="1",
     *                     description="The status of the equipment =>required"
     *                 ),
     *
     *             )
     *         )
     *     ),
     *     @OA\Response(response="200", description="equipment updated successfully"),
     *     @OA\Response(response="401", description="Unauthorized"),
     *     @OA\Response(response="422", description="Validation failed")
     * )
     */

    public function update(CreateEquipmentRequest $equipmentdata, equipment $equipment)
    {

        DB::beginTransaction();
        try {
            $equipmentData = $equipmentdata->validated();
            $LoggedInUser = auth::user();
            $loggedInTenant = $LoggedInUser->tenant;
            $loggedInTenantId = $loggedInTenant->id;
            $AdditionalequipmentData = [
                'user_id' => $LoggedInUser->id,
                'tenant_id' => $loggedInTenantId
            ];
            $MergedData = array_merge($equipmentData, $AdditionalequipmentData);

            $active_duties_count = $equipment->duties()->where('status', '1')->count();
            if ($active_duties_count > 0) {
                return response()->json([
                    'message' => 'Oops! You cannot update a equipment that is currently assigned to an duty.'
                ]);
            } else {
                $equipment->update($MergedData);

                DB::commit();
                return response()->json([
                    'message' => 'The equipment is updated',

                    'equipment' => $equipment
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
     *      path="/api/equipment/{id}",
     *      summary="Delete The equipment.Permission required = equipment.delete",
     *      description="This endpoint delete equipment.",
     *      tags={"Equipment"},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="The ID of the equipment to be deleted",
     *         @OA\Schema(
     *             type="integer",
     *             format="int64"
     *         )
     *     ),
     *      @OA\Response(response="200", description="Successful operation"),
     *      @OA\Response(response="401", description="Unauthorized"),
     * )
     */

    public function destroy(Equipment $equipment)
    {
        DB::beginTransaction();
        try {

            $active_duties = $equipment->duties()
            ->where('status','1')
            ->count();
            if($active_duties > 0){
                return response()->json([
                    'message' => 'Cannot delete equipment.There are active duties associated with it.'
                ]);
            }
            $equipment->delete();
            DB::commit();
            return response()->json([
                'message' => 'The equipment is deleted',
                'equipment deleted' => $equipment
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
