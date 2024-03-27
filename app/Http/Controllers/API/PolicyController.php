<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreatePolicyRequest;
use App\Models\Policy;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * @OA\Tag(
 *     name="Policy",
 *     description="Handling the crud of Policy in it."
 * )
 */
class PolicyController extends Controller
{
    public function __construct()
    {
        // Apply middleware to all methods in the controller
        $this->middleware('checkPermission:policy.list')->only('index', 'inactivePolicies');
        $this->middleware('checkPermission:policy.create')->only('create');
        $this->middleware('checkPermission:policy.store')->only('store');
        $this->middleware('checkPermission:policy.edit')->only('show');
        $this->middleware('checkPermission:policy.update')->only('update');
        $this->middleware('checkPermission:policy.delete')->only('delete');
    }

    /**
     * @OA\Get(
     *      path="/api/policy",
     *      summary="Get All active policies.Permission required = policy.list",
     *      description="This endpoint retrieves information about something.",
     *      tags={"Policy"},
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
        $policy = Policy::where('tenant_id', $loggedInTenantId)
        ->where('status', '1')
        ->get();

        return response()->json([
            'message'  => 'Active Policies retrieved successfully',
            'Policies' => $policy,
        ], 200);
    }

    /**
     * @OA\Get(
     *      path="/api/policy/inactive-policies",
     *      summary="Get All active policies.Permission required = policy.list",
     *      description="This endpoint retrieves information about something.",
     *      tags={"Policy"},
     *
     *      @OA\Response(response="200", description="Successful operation"),
     *      @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function inactivePolicies()
    {
        $LoggedInUser = auth::user();
        $loggedInTenant = $LoggedInUser->tenant;
        $loggedInTenantId = $loggedInTenant->id;
        $policy = Policy::where('tenant_id', $loggedInTenantId)
        ->where('status', '0')
        ->get();

        return response()->json([
            'message'  => 'Inactive Policies retrieved successfully',
            'Policies' => $policy,
        ], 200);
    }

    public function create()
    {
    }

    /**
     * @OA\Post(
     *     path="/api/policy",
     *     summary="Create a new policy.Permission required = policy.store",
     *     description="This endpoint creates a new policy.",
     *     tags={"Policy"},
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
     *                     property="name",
     *                     type="string",
     *                     example="new policy",
     *                     description="The name of the policy => required"
     *                 ),
     *                 @OA\Property(
     *                     property="shift_start",
     *                     type="string",
     *                     example="10:00 AM",
     *                     description="The shift_start timing  of the policy => required"
     *                 ),
     *                 @OA\Property(
     *                     property="shift_end",
     *                     type="string",
     *                     example="10:00 AM",
     *                     description="The shift_end timing  of the policy => required"
     *                 ),
     *                 @OA\Property(
     *                     property="late_allow",
     *                     type="string",
     *                     example="10 min",
     *                     description=" => required"
     *                 ),
     *                 @OA\Property(
     *                     property="early_departure_allow",
     *                     type="string",
     *                     example="15 min",
     *                     description="=> required"
     *                 ),
     *  @OA\Property(
     *                     property="late_deduction",
     *                     type="string",
     *                     example="100 AED",
     *                     description="The amount to be deducted when employee is late =>required"
     *                 ),
     *  @OA\Property(
     *                     property="early_deduction",
     *                     type="string",
     *                     example="100 AED",
     *                     description=" =>required"
     *                 ),
     *  @OA\Property(
     *                     property="status",
     *                     type="number",
     *                     example="1",
     *                     description="The status of the policy =>required"
     *                 ),
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response="201", description="policy created successfully"),
     *     @OA\Response(response="401", description="Unauthorized"),
     *     @OA\Response(response="422", description="Validation failed")
     * )fd
     */
    public function store(CreatePolicyRequest $request)
    {
        DB::beginTransaction();

        try {
            // adding the value user_id and tenant_id
            $policydata = $request->validated();
            $LoggedInUser = auth::user();
            $LoggedInUserid = $LoggedInUser->id;
            $loggedInTenant = $LoggedInUser->tenant;
            $loggedInTenantid = $loggedInTenant->id;
            $additionalpolicydata = [
                'user_id'   => $LoggedInUserid,
                'tenant_id' => $loggedInTenantid,

            ];
            $mergedpolicydata = array_merge($policydata, $additionalpolicydata);

            $newpolicy = Policy::create($mergedpolicydata);

            DB::commit();

            return response()->json([
                'message'        => 'The new policy is created ',
                'this is policy' => $newpolicy,
            ]);
        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'message' => 'There was an error creating the policy',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *      path="/api/policy/{id}",
     *      summary="GET The policy.Permission required = policy.edit",
     *      description="This endpoint Gives a specific  policy.",
     *      tags={"Policy"},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="The ID of the policy ",
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
    public function show(string $id)
    {
        $policy = Policy::find($id);

        return response()->json([
            'message' => 'this is your policy ',
            'policy'  => $policy,
        ], 200);
    }

    /**
     * @OA\Patch(
     *     path="/api/policy/{id}",
     *     summary="Update the policy.Permission required = policy.update",
     *     description="This endpoint updates a policy.",
     *     tags={"Policy"},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="The ID of the policy to be updated",
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
     *                     property="name",
     *                     type="string",
     *                     example="new policy",
     *                     description="The name of the policy => required"
     *                 ),
     *                 @OA\Property(
     *                     property="shift_start",
     *                     type="string",
     *                     example="10:00 AM",
     *                     description="The shift_start timing  of the policy => required"
     *                 ),
     *                 @OA\Property(
     *                     property="shift_end",
     *                     type="string",
     *                     example="10:00 AM",
     *                     description="The shift_end timing  of the policy => required"
     *                 ),
     *                 @OA\Property(
     *                     property="late_allow",
     *                     type="string",
     *                     example="10 min",
     *                     description=" => required"
     *                 ),
     *                 @OA\Property(
     *                     property="early_departure_allow",
     *                     type="string",
     *                     example="15 min",
     *                     description="=> required"
     *                 ),
     *  @OA\Property(
     *                     property="late_deduction",
     *                     type="string",
     *                     example="100 AED",
     *                     description="The amount to be deducted when employee is late =>required"
     *                 ),
     *  @OA\Property(
     *                     property="early_deduction",
     *                     type="string",
     *                     example="100 AED",
     *                     description=" =>required"
     *                 ),
     *  @OA\Property(
     *                     property="status",
     *                     type="number",
     *                     example="1",
     *                     description="The status of the policy =>required"
     *                 ),
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response="201", description="policy created successfully"),
     *     @OA\Response(response="401", description="Unauthorized"),
     *     @OA\Response(response="422", description="Validation failed")
     * )fd
     */
    public function update(CreatePolicyRequest $request, Policy $policy)
    {
        DB::beginTransaction();

        try {
            $policydata = $request->validated();
            $LoggedInUser = auth::user();
            $LoggedInUserid = $LoggedInUser->id;
            $loggedInTenant = $LoggedInUser->tenant;
            $loggedInTenantid = $loggedInTenant->id;
            $additionalpolicydata = [
                'user_id'   => $LoggedInUserid,
                'tenant_id' => $loggedInTenantid,

            ];
            $mergedpolicydata = array_merge($policydata, $additionalpolicydata);
            $active_duties_count = $policy->duties()->where('status', '1')->count();
            if ($active_duties_count > 0) {
                return response()->json([
                    'message' => 'Sorry, you cannot update this policy because it is currently assigned to an active duty.',
                ]);
            }
            $policy->update($mergedpolicydata);
            DB::commit();

            return response()->json([
                'message'       => 'Policy is updated successfully',
                'updatedpolicy' => $policy,
            ]);
        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'message' => 'There was an error updating the policy',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */

    /**
     * @OA\Delete(
     *      path="/api/policy/{id}",
     *      summary="Delete The policy.Permission required = policy.delete",
     *      description="This endpoint delete policy.",
     *      tags={"Policy"},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="The ID of the policy to be deleted",
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
    public function destroy(Policy $policy)
    {
        DB::beginTransaction();

        try {
            $active_duties_count = $policy->duties()->where('status', '1')->count();
            if ($active_duties_count > 0) {
                return response()->json([
                    'message' => 'Sorry, you cannot delete this policy because it is currently assigned to an active duty.',
                ]);
            }
            $policy->delete();
            DB::commit();

            return response()->json([
                'message' => 'The policy is deleted successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'message' => 'There was an error creating the policy',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
