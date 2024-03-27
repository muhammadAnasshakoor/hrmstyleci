<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\DB;

/**
 * @OA\Tag(
 *     name="Permission",
 *     description="Handling the crud of Permission in it."
 * )
 */
class PermissionController extends Controller
{
    public function __construct()
    {
        // Apply middleware to all methods in the controller
        $this->middleware('checkPermission:permission.list')->only('index');
        $this->middleware('checkPermission:permission.create')->only('create');
        $this->middleware('checkPermission:permission.store')->only('store');
        $this->middleware('checkPermission:permission.edit')->only('show');
        $this->middleware('checkPermission:permission.update')->only('update');
        $this->middleware('checkPermission:permission.delete')->only('delete');
    }
    /**
     * @OA\Get(
     *      path="/api/permission",
     *      summary="Get All permissions.Permission required = permission.list",
     *      description="This endpoint retrieves information about something.",
     *      tags={"Permission"},
     *      @OA\Response(response="200", description="Successful operation"),
     *      @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function index()
    {
        $permissions = Permission::all();
        return response()->json([
            'message' => 'Retrieved all permissions successfully',
            'permissions' => $permissions

        ]);
    }
    public function create()
    {
    }


    /**  @OA\Get(
     *      path="/api/permission/{id}",
     *      summary="GET The permission.Permission required = permission.edit",
     *      description="This endpoint Gives a specific  permission.",
     *      tags={"Permission"},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="The ID of the permission ",
     *         @OA\Schema(
     *             type="integer",
     *             format="int64"
     *         )
     *     ),
     *      @OA\Response(response="200", description="Successful operation"),
     *      @OA\Response(response="401", description="Unauthorized"),
     * )
     */

    public function show(Permission $permission)
    {
        return response()->json([
            'message' => 'Permission details retrieved successfully',
            'Permission' => $permission
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    /**
     * @OA\Post(
     *     path="/api/permission",
     *     summary="Create a new permission.Permission required = permission.store",
     *     description="This endpoint creates a new permission.",
     *     tags={"Permission"},
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="application/x-www-form-urlencoded",
     *             @OA\Schema(
     *                 type="object",
     *                 @OA\Property(
     *                     property="name",
     *                     type="string",
     *                     example="module.delete",
     *                     description="The name of the permission => required"
     *                 ),
     *             )
     *         )
     *     ),
     *     @OA\Response(response="201", description="permission created successfully"),
     *     @OA\Response(response="401", description="Unauthorized"),
     *     @OA\Response(response="422", description="Validation failed")
     * )
     */

    public function store(Request $request)
    {

        DB::beginTransaction();
        try {

            $request->validate([
                'name' => 'required|string'

            ]);

            $Permission = Permission::create(['name' => $request->input('name'), 'guard_name' => 'sanctum']);
            DB::commit();

            return response()->json([
                'message' => 'Permission has been added successfully',
                'permission' => $Permission
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => 'There  is some error',
                'error' => $e->getMessage()
            ]);
        }
    }



    /**
     * @OA\Patch(
     *     path="/api/permission/{id}",
     *     summary="Update the permission.Permission required = permission.update",
     *     description="This endpoint updates a permission.",
     *     tags={"Permission"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="The ID of the permission to be updated",
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
     *                     property="name",
     *                     type="string",
     *                     example="module.example",
     *                     description="The name of the permission => required"
     *                 ),
     *             )
     *         )
     *     ),
     *     @OA\Response(response="200", description="permission updated successfully"),
     *     @OA\Response(response="401", description="Unauthorized"),
     *     @OA\Response(response="422", description="Validation failed")
     * )
     */




    public function update(Request $request, Permission $permission)
    {
        DB::beginTransaction();


        try {
            $req =   $request->validate([
                'name' => 'required|string'
                // 'name' => 'required|numeric'
            ]);


            $roles = $permission->roles()->count();
            if ($roles > 0) {
                return response()->json([
                    'message' => 'Unable to update permission. It is associated with existing roles.'
                ]);
            }
            $permission->update($req);

            DB::commit();

            return response()->json([
                'message' => 'Permission is updated successfully',
                'Updated Permission' => $permission
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => 'There is some error',
                'error' => $e->getMessage()
            ]);
        }
    }


    /**
     * @OA\Delete(
     *      path="/api/permission/{id}",
     *      summary="Delete The permission.Permission required = permission.delete",
     *      description="This endpoint delete permission.",
     *      tags={"Permission"},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="The ID of the permission to be deleted",
     *         @OA\Schema(
     *             type="integer",
     *             format="int64"
     *         )
     *     ),
     *      @OA\Response(response="200", description="Successful operation"),
     *      @OA\Response(response="401", description="Unauthorized"),
     * )
     */


    public function destroy(Permission $permission)
    {
        DB::beginTransaction();
        try {



            $roles = $permission->roles()->count();
            if ($roles > 0) {
                return response()->json([
                    'message' => 'Unable to delete permission. It is associated with existing roles.'
                ]);
            }
            $permission->delete();

            DB::commit();
            return response()->json([
                'message' => 'Permission deleted successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'status' => 'error',
                'message' => 'There was an error deleting the permission',
                'error' => $e->getMessage()
            ]);
        }
    }
}
