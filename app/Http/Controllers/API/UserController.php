<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User_type;
use Spatie\Permission\Models\Role;







/**
 * @OA\Tag(
 *     name="User",
 *     description="Handling the crud of User in it."
 * )
 */
class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    public function __construct()
    {
        // Apply middleware to all methods in the controller
        $this->middleware('checkPermission:user.list')->only('index','inactiveUsers');
        $this->middleware('checkPermission:user.create')->only('create');
        $this->middleware('checkPermission:user.store')->only('store');
        $this->middleware('checkPermission:user.edit')->only('show');
        $this->middleware('checkPermission:user.update')->only('update');
        $this->middleware('checkPermission:user.delete')->only('delete');
    }

    /**
     * @OA\Get(
     *      path="/api/user",
     *      summary="Get All  active users.Permission required = user.list",
     *      description="This endpoint retrieves information about something.",
     *      tags={"User"},
     *      @OA\Response(response="200", description="Successful operation"),
     *      @OA\Response(response="401", description="Unauthorized"),
     * )
     */

    public function index()
    {
        $users  = User::where('status','1')
        ->with('roles')
        ->get();
        return response()->json([
            'message' => 'List of active users retrieved successfully',
            'users' => $users
        ]);
    }


    /**
     * @OA\Get(
     *      path="/api/user/inactive-users",
     *      summary="Get All inactive users.Permission required = user.list",
     *      description="This endpoint retrieves information about something.",
     *      tags={"User"},
     *      @OA\Response(response="200", description="Successful operation"),
     *      @OA\Response(response="401", description="Unauthorized"),
     * )
     */

     public function inactiveUsers()
     {
         $users  = User::where('status','0')
         ->with('roles')
         ->get();
         return response()->json([
             'message' => 'List of inactive users retrieved successfully',
             'users' => $users,
         ]);
     }
    /**
     * @OA\Get(
     *      path="/api/user/create",
     *      summary="Get All roles.Permission required = user.create",
     *      description="This endpoint retrieves all  roles .",
     *      tags={"User"},
     *      @OA\Response(response="200", description="Successful operation"),
     *      @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function create()
    {
        $roles = Role::select('id', 'name')->get();
        return response()->json([
            'message' => 'Roles retrieved successfully!',
            'Roles' => $roles
        ]);
    }


    /**
     * Store a newly created resource in storage.
     */

    /**
     * @OA\Post(
     *     path="/api/user",
     *     summary="Create a new user.Permission required = user.store",
     *     description="This endpoint creates a new user.",
     *     tags={"User"},
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="application/x-www-form-urlencoded",
     *             @OA\Schema(
     *                 type="object",
     *                 @OA\Property(
     *                     property="email",
     *                     type="email",
     *                     example="example@gmail.com",
     *                     description="The email of the user => required"
     *                 ),
     *
     *                 @OA\Property(
     *                     property="password",
     *                     type="password",
     *                     example="password",
     *                     description="The password of the user => required"
     *                 ),
     *                 @OA\Property(
     *                     property="role",
     *                     type="string",
     *                     example="admin",
     *                     description="The role of the user => required"
     *                 ),
    *             )
     *         )
     *     ),
     *     @OA\Response(response="201", description="user created successfully"),
     *     @OA\Response(response="401", description="Unauthorized"),
     *     @OA\Response(response="422", description="Validation failed")
     * )fd
     */

    public function store(Request $request)
    {
        DB::beginTransaction();
        try {

            $user = Auth::user();
            $data = $request->validate([
                'email' => 'required|email|unique:users',
                'password' => 'required',
                'status' => 'nullable|regex:/^[01]$/',
                'modified_by' => 'nullable',
                'role' => 'required'
            ]);
            $modified_by = $user->id;
            $data['modified_by'] = $modified_by;

            $new_user = User::create($data);
            // assigning the role to the user
            $role = $request->input('role');


            $new_user->assignRole($role);
            DB::commit();
            return response()->json([
                'message' => 'User created successfully',
                'data' => $new_user
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
     *      path="/api/user/{id}",
     *      summary="GET The user.Permission required = user.edit",
     *      description="This endpoint Gives a specific  user.",
     *      tags={"User"},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="The ID of the user ",
     *         @OA\Schema(
     *             type="integer",
     *             format="int64"
     *         )
     *     ),
     *      @OA\Response(response="200", description="Successful operation"),
     *      @OA\Response(response="401", description="Unauthorized"),
     * )
     */


    public function show(User $user)
    {
        $user->load('roles');
        return response()->json(
            [
                'message' => 'User retrieved successfully',
                'User' => $user
            ]
        );
    }
    /**
     * Update the specified resource in storage.
     */


    /**
     * @OA\Patch(
     *     path="/api/user/{id}",
     *     summary="Update the user.Permission required = user.update",
     *     description="This endpoint updates a user.",
     *     tags={"User"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="The ID of the user to be updated",
     *         @OA\Schema(
     *             type="integer",
     *             format="int64"
     *         )
     *     ),*     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="application/x-www-form-urlencoded",
     *             @OA\Schema(
     *                 type="object",
     *                 @OA\Property(
     *                     property="password",
     *                     type="password",
     *                     example="password",
     *                     description="The password of the user => required"
     *                 ),
     *                 @OA\Property(
     *                     property="role",
     *                     type="string",
     *                     example="admin",
     *                     description="The role of the user => required"
     *                 ),
     *  @OA\Property(
     *                     property="status",
     *                     type="number",
     *                     example="1",
     *                     description="The status of the user =>required"
     *                 ),
     *             )
     *         )
     *     ),
     *     @OA\Response(response="201", description="user created successfully"),
     *     @OA\Response(response="401", description="Unauthorized"),
     *     @OA\Response(response="422", description="Validation failed")
     * )fd
     */
    public function update(Request $request, User $previous_user)
    {

        $newuserdata = $request->validated();
        DB::beginTransaction();
        try {

            $user = Auth::user();
            $data = $request->validate([
                'password' => 'nullable',
                'status' => 'nullable|regex:/^[01]$/',
                'modified_by' => 'nullable',
                'role' => 'nullable'
            ]);



            $modified_by = $user->id;
            $data['modified_by'] = $modified_by;

            $previous_user->update($data);
            if ($request->filled('role')) {
                $role = $request->input('role');
                $previous_user->assignRole($role);
            }
            DB::commit();
            return response()->json([
                'message' => 'User updated successfully',
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
     *      path="/api/user/{id}",
     *      summary="Delete The user.Permission required = user.delete",
     *      description="This endpoint delete user.",
     *      tags={"User"},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="The ID of the user to be deleted",
     *         @OA\Schema(
     *             type="integer",
     *             format="int64"
     *         )
     *     ),
     *      @OA\Response(response="200", description="Successful operation"),
     *      @OA\Response(response="401", description="Unauthorized"),
     * )
     */

    public function destroy(User $user)
    {

        DB::beginTransaction();
        try {
            $user->delete();
            DB::commit();
            return response()->json([
                'message' => 'The user is deleted successfully!',

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
