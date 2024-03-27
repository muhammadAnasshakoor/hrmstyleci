<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\DB;




/**
 * @OA\Tag(
 *     name="Role",
 *     description="Handling the crud of Role in it."
 * )
 */
class RoleController extends Controller
{
    public function __construct()
    {
        // Apply middleware to all methods in the controller
        $this->middleware('checkPermission:role.list')->only('index');
        $this->middleware('checkPermission:role.create')->only('create');
        $this->middleware('checkPermission:role.store')->only('store');
        $this->middleware('checkPermission:role.edit')->only('show');
        $this->middleware('checkPermission:role.update')->only('update');
        $this->middleware('checkPermission:role.delete')->only('delete');
    }

    /**
     * @OA\Get(
     *      path="/api/role",
     *      summary="Get All roles.Permission required = role.list",
     *      description="This endpoint retrieves information about something.",
     *      tags={"Role"},
     *      @OA\Response(response="200", description="Successful operation"),
     *      @OA\Response(response="401", description="Unauthorized"),
     * )
     */

    public function index()
    {
        $roles = Role::all();
        return response()->json([
            'message' => 'Roles retrieved successfully.',
            'role' => $roles
        ]);
    }
    /**
     * @OA\Get(
     *      path="/api/role/create",
     *      summary="Get All permissions.Permission required = role.create",
     *      description="This endpoint retrieves information about something.",
     *      tags={"Role"},
     *      @OA\Response(response="200", description="Successful operation"),
     *      @OA\Response(response="401", description="Unauthorized"),
     * )
     */

    public function create()
    {
        $permissions = Permission::all();
        $chunks = [];
        foreach ($permissions as $permission) {
            $module = explode('.', $permission->name);
            $chunks[$module[0]][$permission->id] = $permission->name;
        }
        return response()->json([
            'message' => 'Permissions retrieved successfully.',
            'Permission' => $chunks
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/role",
     *     summary="Create a new role.Permission required = role.store",
     *     description="This endpoint creates a new role.",
     *     tags={"Role"},
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="application/x-www-form-urlencoded",
     *             @OA\Schema(
     *                 type="object",
     *                 @OA\Property(
     *                     property="name",
     *                     type="string",
     *                     example="newrole",
     *                     description="The name of the role => required"
     *                 ),
     *  @OA\Property(
     *                     property="permissions",
     *                     type="string",
     *                     example="company.create,company.update",
     *                     description="The The permission needed to be assigned to   the role =>required"
     *                 ),
     *             )
     *         )
     *     ),
     *     @OA\Response(response="201", description="role created successfully"),
     *     @OA\Response(response="401", description="Unauthorized"),
     *     @OA\Response(response="422", description="Validation failed")
     * )
     */


    public function store(Request $request)
    {
        DB::beginTransaction();

        try {
            $request->validate([
                'name' => 'required|string',

                'permissions' => 'nullable|string', // Change validation to 'string'
            ]);

            $role = Role::create(['name' => $request->name, 'guard_name' => 'sanctum']);


            if ($request->has('permissions')) {


                $permission = $request->input('permissions');
                // Convert permissions string to array
                $permissions = explode(',', $permission);

                // Assign permissions to the role
                foreach ($permissions as $permission) {
                    $role->givePermissionTo($permission);
                }
            }
            DB::commit();

            return response()->json([
                'message' => 'Your role has been created with permissions',
                'role' => $role,
            ]);
        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'message' => 'There was an error creating the role',
                'error' => $e->getMessage()
            ], 500); // Use appropriate HTTP status code for error
        }
    }
    /**
     * @OA\Patch(
     *     path="/api/role/{id}",
     *     summary="Update the role.Permission required = role.update",
     *     description="This endpoint updates a role.",
     *     tags={"Role"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="The ID of the role to be updated",
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
     *                     example="newrole",
     *                     description="The name of the role => required"
     *                 ),
     *  @OA\Property(
     *                     property="permissions",
     *                     type="string",
     *                     example="company.create,company.update",
     *                     description="The The permission needed to be assigned to   the role =>required"
     *                 ),
     *             )
     *         )
     *     ),
     *     @OA\Response(response="201", description="role created successfully"),
     *     @OA\Response(response="401", description="Unauthorized"),
     *     @OA\Response(response="422", description="Validation failed")
     * )
     */



    public function update(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            $role = Role::findOrFail($id);

            $request->validate([
                'name' => 'nullable|string',
                'permissions' => 'nullable|string', // Changed validation to 'string'
            ]);

            $role->update(['name' => $request->name]);

            if ($request->has('permissions')) {
                $permission = $request->input('permissions');
                // Convert permissions string to array
                $permissions = explode(',', $permission);

                // Sync permissions to the role
                $role->syncPermissions($permissions);
            }

            DB::commit();

            return response()->json([
                'message' => 'Your role has been updated with permissions',
                'role' => $role,
            ]);
        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'message' => 'There was an error updating the role',
                'error' => $e->getMessage()
            ], 500); // Use appropriate HTTP status code for error
        }
    }

    /**
     * @OA\Get(
     *      path="/api/role/{id}",
     *      summary="GET The role.Permission required = role.edit",
     *      description="This endpoint Gives a specific  role.",
     *      tags={"Role"},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="The ID of the role ",
     *         @OA\Schema(
     *             type="integer",
     *             format="int64"
     *         )
     *     ),
     *      @OA\Response(response="200", description="Successful operation"),
     *      @OA\Response(response="401", description="Unauthorized"),
     * )
     */

    public function show(Role $role)
    {
        $role->load('permissions');
        return response()->json([
            'message' => 'This is the required role',
            'Role' => $role
        ]);
    }

    /**
     * @OA\Delete(
     *      path="/api/role/{id}",
     *      summary="Delete The role.Permission required = role.delete",
     *      description="This endpoint delete role.",
     *      tags={"Role"},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="The ID of the role to be deleted",
     *         @OA\Schema(
     *             type="integer",
     *             format="int64"
     *         )
     *     ),
     *      @OA\Response(response="200", description="Successful operation"),
     *      @OA\Response(response="401", description="Unauthorized"),
     * )
     */


    public function destroy(Role $role)
    {
        DB::beginTransaction();
        try {
            $permissions = $role->permissions()->count();
             if($permissions > 0 ){
                return response()->json([
                    'message' => 'Unable to delete role. It is associated with existing permissions.'
                ]);
             }
            $role->delete();
            DB::commit();
            return response()->json([
                'message' => 'The role is deleted'
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => 'There is some error',

                'error' => $e->getMessage()
            ]);
        }
    }
}
