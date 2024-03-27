<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateSubscriberRequest;
use App\Models\Subscriber;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use NunoMaduro\Collision\Adapters\Phpunit\Subscribers\Subscriber as SubscribersSubscriber;
use PHPUnit\Event\Subscriber as EventSubscriber;

/**
 * @OA\Tag(
 *     name="Subscriber",
 *     description="Handling the crud of subscriber in it."
 * )
 */
class SubscriberController extends Controller
{

    public function __construct()
    {
        // Apply middleware to all methods in the controller
        $this->middleware('checkPermission:subscriber.list')->only('index', 'inactiveSubscribers');
        $this->middleware('checkPermission:subscriber.create')->only('create');
        $this->middleware('checkPermission:subscriber.store')->only('store');
        $this->middleware('checkPermission:subscriber.edit')->only('show');
        $this->middleware('checkPermission:subscriber.update')->only('update');
        $this->middleware('checkPermission:subscriber.delete')->only('delete');
    }
    /**
     * Display a listing of the resource.
     */

    /**
     * @OA\Get(
     *      path="/api/subscriber",
     *      summary="Get All subscribers.Permission required = subscriber.list",
     *      description="This endpoint retrieves information about something.",
     *      tags={"Subscriber"},
     *      @OA\Response(response="200", description="Successful operation"),
     *      @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function index()
    {
        $subscribers = Subscriber::where('status', '1')
            ->get();

        return response()->json([
            'message' => 'Active Subscribers retrieved successfully',
            'data' => $subscribers
        ]);
    }

    /**
     * @OA\Get(
     *      path="/api/subscriber/inactive-subscribers",
     *      summary="Get All subscribers.Permission required = subscriber.list",
     *      description="This endpoint retrieves information about something.",
     *      tags={"Subscriber"},
     *      @OA\Response(response="200", description="Successful operation"),
     *      @OA\Response(response="401", description="Unauthorized"),
     * )
     */

    public function inactiveSubscribers()
    {
        $subscribers = Subscriber::where('status', '0')
            ->get();

        return response()->json([
            'message' => 'Inactive Subscribers retrieved successfully',
            'data' => $subscribers
        ]);
    }
    /**
     * Show the form for creating a new resource.
     */

    /**
     * @OA\Get(
     *      path="/api/subscriber/create",
     *      summary="Get All companies that are active.Permission required = subscriber.create",
     *      description="This endpoint retrieves information about something.",
     *      tags={"Subscriber"},
     *      @OA\Response(response="200", description="Successful operation"),
     *      @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function create()
    {
        $subscription_plans = SubscriptionPlan::where('status', '1')
            ->select('id', 'title', 'description')
            ->get();


        $active_tenants = Tenant::where('status', '1')
            ->select('id', 'name')
            ->get();
        return response()->json([
            'message' => 'Data retrieved successfully',
            'Subscription Plans' => $subscription_plans,
            'Tenant' => $active_tenants
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */

    /**
     * @OA\Post(
     *     path="/api/subscriber",
     *     summary="Create a new subscriber.Permission required = subscriber.store",
     *     description="This endpoint creates a new subscriber.",
     *     tags={"Subscriber"},
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="application/x-www-form-urlencoded",
     *             @OA\Schema(
     *                 type="object",
     *                 @OA\Property(
     *                     property="tenant_id",
     *                     type="integer",
     *                     example="1",
     *                     description="The id of the tenant => required"
     *                 ),
     *
     *                 @OA\Property(
     *                     property="subscription_plan_id",
     *                     type="integer",
     *                     example="1",
     *                     description="The id of the subscription_plan => required"
     *                 ),
     *
     *
     *                 @OA\Property(
     *                     property="start_date",
     *                     type="date",
     *                     example="2024-03-25",
     *                     description="The starting date of plan=> required and its format should be yyyy-mm-dd"
     *                 ),
     *
     *                 @OA\Property(
     *                     property="end_date",
     *                     type="date",
     *                     example="2024-03-25",
     *                     description="The ending date of plan=> required and its format should be yyyy-mm-dd"
     *                 ),
     *
     *                 @OA\Property(
     *                     property="amount",
     *                     type="string",
     *                     example="100 PKR",
     *                     description="The amount of plan=> required"
     *                 ),
     *
     *             )
     *         )
     *     ),
     *     @OA\Response(response="201", description="subscriber created successfully"),
     *     @OA\Response(response="401", description="Unauthorized"),
     *     @OA\Response(response="422", description="Validation failed")
     * )fd
     */

    public function store(CreateSubscriberRequest $request)
    {
        DB::beginTransaction();
        try {

            $subscriber_data = $request->validated();

            $user_id = Auth::user()->id;

            $subscriber_data['user_id'] = $user_id;

            $new_subscriber = Subscriber::create($subscriber_data);

            DB::commit();
            return response()->json([
                'message' => 'Subscriber Created successfully',

                'data' => $new_subscriber
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
     *      path="/api/subscriber/{id}",
     *      summary="GET The subscriber.Permission required = subscriber.edit",
     *      description="This endpoint Gives a specific  subscriber.",
     *      tags={"Subscriber"},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="The ID of the subscriber ",
     *         @OA\Schema(
     *             type="integer",
     *             format="int64"
     *         )
     *     ),
     *      @OA\Response(response="200", description="Successful operation"),
     *      @OA\Response(response="401", description="Unauthorized"),
     * )
     */

    public function show(Subscriber $subscriber)
    {
        return response()->json([
            'message' => 'Subscriber retrieved successfully',
            'data' => $subscriber
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
     *     path="/api/subscriber/{id}",
     *     summary="Update the subscriber.Permission required = subscriber.update",
     *     description="This endpoint updates a subscriber.",
     *     tags={"Subscriber"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="The ID of the subscriber to be updated",
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
     *                     property="tenant_id",
     *                     type="integer",
     *                     example="1",
     *                     description="The id of the tenant => required"
     *                 ),
     *                 @OA\Property(
     *                     property="subscription_plan_id",
     *                     type="integer",
     *                     example="1",
     *                     description="The id of the subscription_plan => required"
     *                 ),
     *                 @OA\Property(
     *                     property="start_date",
     *                     type="date",
     *                     example="2024-03-25",
     *                     description="The starting date of plan=> required and its format should be yyyy-mm-dd"
     *                 ),
     *                 @OA\Property(
     *                     property="end_date",
     *                     type="date",
     *                     example="2024-03-25",
     *                     description="The ending date of plan=> required and its format should be yyyy-mm-dd"
     *                 ),
     *                 @OA\Property(
     *                     property="amount",
     *                     type="string",
     *                     example="100 PKR",
     *                     description="The amount of plan=> required"
     *                 ),
     *                @OA\Property(
     *                     property="status",
     *                     type="number",
     *                     example="1",
     *                     description="The status of the subscriber =>required"
     *                 ),
     *             )
     *         )
     *     ),
     *     @OA\Response(response="201", description="subscriber created successfully"),
     *     @OA\Response(response="401", description="Unauthorized"),
     *     @OA\Response(response="422", description="Validation failed")
     * )fd
     */

    public function update(CreateSubscriberRequest $request, Subscriber $subscriber)
    {
        DB::beginTransaction();
        try {
            $subscriber_data = $request->validated();

            $subscriber->update($subscriber_data);



            DB::commit();


            return response()->json([
                'message' => 'Subscriber updated successfully',
                'data' => $subscriber
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
     *      path="/api/subscriber/{id}",
     *      summary="Delete The subscriber.Permission required = subscriber.delete",
     *      description="This endpoint delete subscriber.",
     *      tags={"Subscriber"},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="The ID of the subscriber to be deleted",
     *         @OA\Schema(
     *             type="integer",
     *             format="int64"
     *         )
     *     ),
     *      @OA\Response(response="200", description="Successful operation"),
     *      @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function destroy(Subscriber $subscriber)
    {
        DB::beginTransaction();
        try {


            $subscriber->delete();


            DB::commit();
            return response()->json([
                'message' => 'Subscriber deleted successfully',

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
