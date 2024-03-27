<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateSubscriptionPlanRequest;
use App\Models\SubscriptionPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;



/**
 * @OA\Tag(
 *     name="Subscrition Plan",
 *     description="Handling the crud of subscrition plan in it."
 * )
 */

class SubscriptionPlanController extends Controller
{
    /**
     * Display a listing of the resource.
     */

     public function __construct()
     {
         // Apply middleware to all methods in the controller
         $this->middleware('checkPermission:subscription_plan.list')->only('index','inactiveSubscriptionPlans');
         $this->middleware('checkPermission:subscription_plan.create')->only('create');
         $this->middleware('checkPermission:subscription_plan.store')->only('store');
         $this->middleware('checkPermission:subscription_plan.edit')->only('show');
         $this->middleware('checkPermission:subscription_plan.update')->only('update');
         $this->middleware('checkPermission:subscription_plan.delete')->only('delete');
     }

    /**
     * @OA\Get(
     *      path="/api/subscrition-plan",
     *      summary="Get All subscrition plans.Permission required = subscrition_plan.list",
     *      description="This endpoint retrieves information about something.",
     *      tags={"Subscrition Plan"},
     *      @OA\Response(response="200", description="Successful operation"),
     *      @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function index()
    {
        $subscription_plans = SubscriptionPlan::where('status', '1')
            ->get();

        return response()->json([
            'message' => 'Active subscription plans retrieved successfully',
            'data' => $subscription_plans
        ]);
    }


    /**
     * @OA\Get(
     *      path="/api/subscrition-plan/inactive-subscrition plans",
     *      summary="Get All subscrition plans.Permission required = subscrition_plan.list",
     *      description="This endpoint retrieves information about something.",
     *      tags={"Subscrition Plan"},
     *      @OA\Response(response="200", description="Successful operation"),
     *      @OA\Response(response="401", description="Unauthorized"),
     * )
     */

    public function inactiveSubscriptionPlans()
    {
        $subscription_plans = SubscriptionPlan::where('status', '0')
            ->get();

        return response()->json([
            'message' => 'Inactive subscription plans retrieved successfully',
            'data' => $subscription_plans
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
     *     path="/api/subscrition-plan",
     *     summary="Create a new subscrition plan.Permission required = subscrition_plan.store",
     *     description="This endpoint creates a new subscrition plan.",
     *     tags={"Subscrition Plan"},
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="application/x-www-form-urlencoded",
     *             @OA\Schema(
     *                 type="object",
     *                 @OA\Property(
     *                     property="title",
     *                     type="string",
     *                     example="newsubscrition plan",
     *                     description="The name of the subscrition plan => required"
     *                 ),
     *             )
     *         )
     *     ),
     *     @OA\Response(response="201", description="subscrition plan created successfully"),
     *     @OA\Response(response="401", description="Unauthorized"),
     *     @OA\Response(response="422", description="Validation failed")
     * )fd
     */

    public function store(CreateSubscriptionPlanRequest $request)
    {
        DB::beginTransaction();
        try {
            $subscription_plan_data = $request->validated();
            $user_id = auth()->user()->id;
            $subscription_plan_data['user_id'] = $user_id;

            $subscription_plan = SubscriptionPlan::create($subscription_plan_data);
            DB::commit();
            return response()->json([
                'message' => 'Subscription Plan Created successfully',
                'data' => $subscription_plan
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
     *      path="/api/subscrition-plan/{id}",
     *      summary="GET The subscrition plan.Permission required = subscrition_plan.edit",
     *      description="This endpoint Gives a specific  subscrition plan.",
     *      tags={"Subscrition Plan"},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="The ID of the subscrition plan ",
     *         @OA\Schema(
     *             type="integer",
     *             format="int64"
     *         )
     *     ),
     *      @OA\Response(response="200", description="Successful operation"),
     *      @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function show(SubscriptionPlan $subscription_plan)
    {

        return response()->json([
            'message' => 'Subscription plan retrieved successfully',
            'data' => $subscription_plan
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
     *     path="/api/subscrition-plan/{id}",
     *     summary="Update the subscrition plan.Permission required = subscrition_plan.update",
     *     description="This endpoint updates a subscrition plan.",
     *     tags={"Subscrition Plan"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="The ID of the subscrition plan to be updated",
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
     *                     example="newsubscrition plan",
     *                     description="The name of the subscrition plan => required"
     *                 ),
     *  @OA\Property(
     *                     property="status",
     *                     type="status",
     *                     example="1",
     *                     description="The status of the subscrition plan =>required"
     *                 ),
     *
     *             )
     *         )
     *     ),
     *     @OA\Response(response="200", description="subscrition plan updated successfully"),
     *     @OA\Response(response="401", description="Unauthorized"),
     *     @OA\Response(response="422", description="Validation failed")
     * )
     */

    public function update(CreateSubscriptionPlanRequest $request, SubscriptionPlan $subscription_plan)
    {
        DB::beginTransaction();
        try {
            $subscription_plan_data = $request->validated();
            $user_id = auth()->user()->id;
            $subscription_plan_data['user_id'] = $user_id;

            if ($request->filled('status') && $request->input('status') == '0') {

                $subscribers_count = $subscription_plan->subscribers
                    ->where('status', '1')
                    ->count();
                if ($subscribers_count > 0) {

                    return response()->json([
                        'message' => 'The status of this plan cannot be deactivated at the moment due to active subscribers associated with it. Please deactivate all subscribers before changing the status.'
                    ]);
                }
            }
            $subscription_plan->update($subscription_plan_data);


            DB::commit();
            return response()->json([
                'message' => 'Subscription plan updated successfully',
                'data' => $subscription_plan
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
     * Remove the specified resource from storage.
     */


    /**
     * @OA\Delete(
     *      path="/api/subscrition-plan/{id}",
     *      summary="Delete The subscrition plan.Permission required = subscrition_plan.delete",
     *      description="This endpoint delete subscrition plan.",
     *      tags={"Subscrition Plan"},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="The ID of the subscrition plan to be deleted",
     *         @OA\Schema(
     *             type="integer",
     *             format="int64"
     *         )
     *     ),
     *      @OA\Response(response="200", description="Successful operation"),
     *      @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function destroy(SubscriptionPlan $subscription_plan)
    {
        DB::beginTransaction();
        try {
            $subscribers_count = $subscription_plan->subscribers
                ->where('status', '1')
                ->count();
            if ($subscribers_count > 0) {
                return response()->json([
                    'error' => 'Cannot delete subscription plan',
                    'message' => 'There are active subscribers associated with this plan. Please deactivate all subscribers before deleting the plan.'
                ], 400);
            }
            $subscription_plan->delete();
            DB::commit();
            return response()->json([
                'message' => 'Subscription Plan deleted succussfully',
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
