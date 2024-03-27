<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateTenantRequest;
use App\Http\Requests\CreateUserRequest; // Import Mail facade
use App\Models\Media;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use OpenApi\Annotations as OA;
use Spatie\Permission\Models\Role;

/**
 * @OA\Tag(
 *     name="Tenant",
 *     description="Handling the crud of Tenant in it."
 * )
 */
class TenantController extends Controller
{
    public function __construct()
    {
        // Apply middleware to all methods in the controller
        $this->middleware('checkPermission:tenant.list')->only('index', 'inactiveTenants');
        $this->middleware('checkPermission:tenant.create')->only('create');
        $this->middleware('checkPermission:tenant.store')->only('store');
        $this->middleware('checkPermission:tenant.edit')->only('show');
        $this->middleware('checkPermission:tenant.update')->only('update');
        $this->middleware('checkPermission:tenant.delete')->only('delete');
    }

    /**
     * @OA\Get(
     *      path="/api/tenant",
     *      summary="Get All active tenants.Permission required = tenant.list",
     *      description="This endpoint retrieves information about something.",
     *      tags={"Tenant"},
     *
     *      @OA\Response(response="200", description="Successful operation"),
     *      @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function index()
    {
        $tenants = Tenant::where('status', '1')
            ->get();
        foreach ($tenants as $tenant) {
            $tenant->load('user');
            $tenant_logo_id = $tenant->logo_media_id;

            if ($tenant_logo_id !== null) {
                $logo_media = Media::where('id', $tenant_logo_id)->first();
                if ($logo_media) {
                    $logo_media_path_url = asset("storage/{$logo_media->media_path}");
                    $logo_media['media_path'] = $logo_media_path_url;
                    $tenant['logo_media_id'] = $logo_media;
                }
            }

            // handling the document in this case
            $tenant_document_id = $tenant->document_media_id;
            if ($tenant_document_id !== null) {
                $document_media = Media::where('id', $tenant_document_id)->first();
                if ($document_media) {
                    $document_media_path_url = asset("storage/{$document_media->media_path}");
                    $document_media['media_path'] = $document_media_path_url;
                    $tenant['document_media_id'] = $document_media;
                }
            }
        }

        return response()->json([
            'message' => 'All the active tenants are retrieved successfully',
            'tenants' => $tenants,
        ]);
    }

    /**
     * @OA\Get(
     *      path="/api/tenant/inactive-tenants",
     *      summary="Get All inactive tenants.Permission required = tenant.list",
     *      description="This endpoint retrieves information about something.",
     *      tags={"Tenant"},
     *
     *      @OA\Response(response="200", description="Successful operation"),
     *      @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function inactiveTenants()
    {
        $tenants = Tenant::where('status', '0')
            ->get();
        foreach ($tenants as $tenant) {
            $tenant->load('user');
            $tenant_logo_id = $tenant->logo_media_id;

            if ($tenant_logo_id !== null) {
                $logo_media = Media::where('id', $tenant_logo_id)->first();
                if ($logo_media) {
                    $logo_media_path_url = asset("storage/{$logo_media->media_path}");
                    $logo_media['media_path'] = $logo_media_path_url;
                    $tenant['logo_media_id'] = $logo_media;
                }
            }

            // handling the document in this case
            $tenant_document_id = $tenant->document_media_id;
            if ($tenant_document_id !== null) {
                $document_media = Media::where('id', $tenant_document_id)->first();
                if ($document_media) {
                    $document_media_path_url = asset("storage/{$document_media->media_path}");
                    $document_media['media_path'] = $document_media_path_url;
                    $tenant['document_media_id'] = $document_media;
                }
            }
        }

        return response()->json([
            'message' => 'All the inactive tenants are retrieved successfully',
            'tenants' => $tenants,
        ]);
    }

    public function create()
    {
    }

    /**
     * @OA\Post(
     *     path="/api/tenant",
     *     summary="Create a new tenant.Permission required = tenant.store",
     *     description="This endpoint creates a new tenant.",
     *     tags={"Tenant"},
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
     *                     example="newtenant",
     *                     description="The name of the tenant => required"
     *                 ),
     *                 @OA\Property(
     *                     property="phone_no",
     *                     type="number",
     *                     example="03245678967",
     *                     description="The phone_no of the tenant => required"
     *                 ),
     *                 @OA\Property(
     *                     property="website",
     *                     type="string",
     *                     example="www.mywebsite.com",
     *                     description="The website of the tenant => nullable"
     *                 ),
     *                 @OA\Property(
     *                     property="address",
     *                     type="string",
     *                     example="E 73، E1 Hali Rd, Block E1 Block E 1 Gulberg III, Lahore, Punjab 54000, <Pakistan></Pakistan>",
     *                     description="The address of the tenant => nullable"
     *                 ),
     *                 @OA\Property(
     *                     property="city",
     *                     type="string",
     *                     example="lahore",
     *                     description="The city of the tenant => nullable"
     *                 ),
     *  @OA\Property(
     *                     property="state",
     *                     type="string",
     *                     example="pakistan",
     *                     description="The state of the tenant =>nullable"
     *                 ),
     *  @OA\Property(
     *                     property="zip_code",
     *                     type="number",
     *                     example="11111111",
     *                     description="The zip_code of the tenant =>nullable"
     *                 ),
     *  @OA\Property(
     *                     property="country",
     *                     type="string",
     *                     example="India",
     *                     description="The country of the tenant =>required"
     *                 ),
     *                            @OA\Property(
     *                     property="document_media_id",
     *                     type="file",
     *                     example="",
     *                     description="The document_media of the tenant=>nullable"
     *                 ),
     *                            @OA\Property(
     *                     property="logo_media_id",
     *                     type="file",
     *                     example="",
     *                     description="The logo_media of the tenant=>nullable"
     *                 ),
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response="201", description="tenant created successfully"),
     *     @OA\Response(response="401", description="Unauthorized"),
     *     @OA\Response(response="422", description="Validation failed")
     * )fd
     */
    public function store(CreateTenantRequest $tenant_request, CreateUserRequest $user_request)
    {
        $tenant_data = $tenant_request->validated();
        $user_data = $user_request->validated();
        DB::beginTransaction();

        try {
            $loggedin_user = auth::user();
            $loggedin_user_id = $loggedin_user->id;
            if (isset($user_data['password']) && $user_data['password'] !== null) {
                $password = $user_request->input('password');
                $additional_user_data = [
                    'password'    => $password,
                    'modified_by' => $loggedin_user_id,
                ];
            } else {
                $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*()_+{}|:<>?-=[];\',./';
                $password = substr(str_shuffle($characters), 0, 8);
                $additional_user_data = [
                    'password' => $password,
                    // 'password' => '12345678',

                    'modified_by' => $loggedin_user_id,
                ];
            }
            $merged_user_data = array_merge($user_data, $additional_user_data);
            $new_user = User::create($merged_user_data);

            if ($tenant_request->hasFile('logo_media_id')) {
                $logofile = $tenant_request->file('logo_media_id');
                $logo_name = $logofile->getClientOriginalName();
                $logopath = $logofile->store('tenant/logo', 'public');
                $logoextension = $logofile->getClientOriginalExtension();
                // creating the new media for the logo
                $logomedia = Media::create([
                    'user_id'    => $loggedin_user_id,
                    'media_name' => $logo_name,
                    'media_path' => $logopath,
                    'extension'  => $logoextension,
                ]);
                $tenant_data['logo_media_id'] = $logomedia->id;
            }
            // handling the document from the tenant
            if ($tenant_request->hasFile('document_media_id')) {
                $documentfile = $tenant_request->file('document_media_id');
                $document_name = $documentfile->getClientOriginalName();
                $documentpath = $documentfile->store('tenant/document', 'public');
                $documentextension = $documentfile->getClientOriginalExtension();
                // creating the newmedia for the logo
                $documentmedia = Media::create([
                    'user_id'    => $loggedin_user_id,
                    'media_name' => $document_name,
                    'media_path' => $documentpath,
                    'extension'  => $documentextension,
                ]);
                $tenant_data['document_media_id'] = $documentmedia->id;
            }

            // creating new tenant

            $newtenant = $new_user->tenant()->create($tenant_data);
            // Assigning the role of tenant
            $tenantRole = Role::where('name', 'tenant')->where('guard_name', 'sanctum')->first();

            if (!$tenantRole) {
                $tenantRole = Role::create(['name' => 'tenant', 'guard_name' => 'sanctum']);
            }

            $new_user->assignRole($tenantRole);

            // sending the emial to tenant with login credentials
            $data = [
                'name'     => $newtenant->name,
                'email'    => $new_user->email,
                'password' => $password,
            ];
            $email = $new_user->email;
            Mail::send('emails.LoginCredentials', $data, function ($message) use ($email) {
                $message->from('info@logicalcreations.net', 'Logical Creations');
                $message->to($email);
                $message->subject('Login credentials');
            });

            DB::commit();

            return response()->json([
                'message' => 'Tenant and associated user are created successfully',
                'user'    => $new_user,
                'tenant'  => $newtenant,
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
     * @OA\Get(
     *      path="/api/tenant/{id}",
     *      summary="GET The tenant.Permission required = tenant.edit",
     *      description="This endpoint Gives a specific  tenant.",
     *      tags={"Tenant"},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="The ID of the tenant ",
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
        $tenant = Tenant::with(['user'])->findOrFail($id);
        $tenant->user;
        $tenant_logo_id = $tenant->logo_media_id;
        $logo_media = 'null';
        if ($tenant_logo_id !== null) {
            $logo_media = Media::where('id', $tenant_logo_id)->first();
            if ($logo_media) {
                $logo_media_path_url = asset("storage/{$logo_media->media_path}");
                $logo_media['media_path'] = $logo_media_path_url;
            }
        }
        // handling the document in this case

        $tenant_document_id = $tenant->document_media_id;
        $document_media = 'null';
        if ($tenant_logo_id !== null) {
            $document_media = Media::where('id', $tenant_document_id)->first();
            if ($document_media) {
                $document_media_path_url = asset("storage/{$document_media->media_path}");
                $document_media['media_path'] = $document_media_path_url;
            }
        }

        return response()->json([
            'message'  => 'This is the required tenant',
            'tenant '  => $tenant,
            'logo'     => $logo_media,
            'document' => $document_media,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */

    /**
     * Update the specified resource in storage.
     */

    /**
     * @OA\Post(
     *     path="/api/tenant/{tenant}",
     *     summary="Update the tenant.Permission required = tenant.update",
     *     description="This endpoint updates a tenant.",
     *     tags={"Tenant"},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="The ID of the tenant to be updated",
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
     *                     example="newtenant",
     *                     description="The name of the tenant => required"
     *                 ),
     *                 @OA\Property(
     *                     property="phone_no",
     *                     type="number",
     *                     example="03245678967",
     *                     description="The phone_no of the tenant => required"
     *                 ),
     *                 @OA\Property(
     *                     property="website",
     *                     type="string",
     *                     example="www.mywebsite.com",
     *                     description="The website of the tenant => nullable"
     *                 ),
     *                 @OA\Property(
     *                     property="address",
     *                     type="string",
     *                     example="E 73، E1 Hali Rd, Block E1 Block E 1 Gulberg III, Lahore, Punjab 54000, <Pakistan></Pakistan>",
     *                     description="The address of the tenant => nullable"
     *                 ),
     *                 @OA\Property(
     *                     property="city",
     *                     type="string",
     *                     example="lahore",
     *                     description="The city of the tenant => nullable"
     *                 ),
     *  @OA\Property(
     *                     property="state",
     *                     type="string",
     *                     example="pakistan",
     *                     description="The state of the tenant =>nullable"
     *                 ),
     *  @OA\Property(
     *                     property="zip_code",
     *                     type="number",
     *                     example="11111111",
     *                     description="The zip_code of the tenant =>nullable"
     *                 ),
     *  @OA\Property(
     *                     property="country",
     *                     type="string",
     *                     example="India",
     *                     description="The country of the tenant =>required"
     *                 ),
     *  @OA\Property(
     *                     property="status",
     *                     type="number",
     *                     example="1",
     *                     description="The status of the tenant =>required"
     *                 ),
     *                            @OA\Property(
     *                     property="document_media_id",
     *                     type="file",
     *                     example="",
     *                     description="The document_media of the tenant=>nullable"
     *                 ),
     *                            @OA\Property(
     *                     property="logo_media_id",
     *                     type="file",
     *                     example="",
     *                     description="The logo_media of the tenant=>nullable"
     *                 ),
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response="201", description="tenant created successfully"),
     *     @OA\Response(response="401", description="Unauthorized"),
     *     @OA\Response(response="422", description="Validation failed")
     * )fd
     */
    public function update(CreateTenantRequest $tenantrequest, Tenant $tenant)
    {
        $tenant_data = $tenantrequest->validated();

        DB::beginTransaction();

        try {
            if ($tenantrequest->filled('status')) {
                $status = $tenant_data['status'];
            } else {
                $status = $tenant->status;
            }
            $user = $tenant->user;
            $loggedin_user = auth::user();
            $loggedin_user_id = $loggedin_user->id;
            $updateduser = [
                'status'      => $status,
                'modified_by' => $loggedin_user_id,
            ];
            $user->update($updateduser);

            if ($tenantrequest->hasFile('logo_media_id')) {
                if ($tenant->logo_media_id) {
                    $previouslogo = Media::find($tenant->logo_media_id);
                    if ($previouslogo) {
                        Storage::disk('public')->delete($previouslogo->media_path);
                        $previouslogo->delete();
                    }
                }
                $logofile = $tenantrequest->file('logo_media_id');
                $logo_name = $logofile->getClientOriginalName();
                $logopath = $logofile->store('tenant/logo', 'public');
                $logoextension = $logofile->getClientOriginalExtension();
                // creating the new media for the logo
                $logomedia = Media::create([
                    'user_id'    => $loggedin_user_id,
                    'media_name' => $logo_name,
                    'media_path' => $logopath,
                    'extension'  => $logoextension,
                ]);
                $tenant_data['logo_media_id'] = $logomedia->id;
            }
            // handling the document from the tenant
            if ($tenantrequest->hasFile('document_media_id')) {
                if ($tenant->document_media_id) {
                    $previousdocument = Media::find($tenant->document_media_id);
                    if ($previousdocument) {
                        Storage::disk('public')->delete($previousdocument->media_path);
                        $previousdocument->delete();
                    }
                }
                $documentfile = $tenantrequest->file('document_media_id');
                $document_name = $documentfile->getClientOriginalName();
                $documentpath = $documentfile->store('tenant/document', 'public');
                $documentextension = $documentfile->getClientOriginalExtension();
                // creating the newmedia for the logo
                $documentmedia = Media::create([
                    'user_id'    => $loggedin_user_id,
                    'media_name' => $document_name,
                    'media_path' => $documentpath,
                    'extension'  => $documentextension,
                ]);
                $tenant_data['document_media_id'] = $documentmedia->id;
            }

            $tenant->update($tenant_data);

            DB::commit();

            return response()->json([
                'message' => 'The tenant is updated',
                'tenant'  => $tenant,

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
     * @OA\Delete(
     *      path="/api/tenant/{id}",
     *      summary="Delete The tenant.Permission required = tenant.delete",
     *      description="This endpoint delete tenant.",
     *      tags={"Tenant"},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="The ID of the tenant to be deleted",
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
    public function destroy(Tenant $tenant)
    {
        DB::beginTransaction();

        try {
            $tenant->delete();
            $tenant->user()->delete();
            $tenant->companies()->delete();
            $tenant->employees()->delete();
            $tenant->policies()->delete();
            $tenant->duties()->delete();
            $tenant->equipments()->delete();
            $tenant->designations()->delete();
            $tenant->holidays()->delete();
            $tenant->attendances()->delete();
            $tenant->attendanceRosters()->delete();
            $tenant->attendanceReport()->delete();
            $tenant->employeeTransfers()->delete();

            if ($tenant->logo_media_id) {
                $previouslogo = Media::find($tenant->logo_media_id);
                if ($previouslogo) {
                    Storage::disk('public')->delete($previouslogo->media_path);
                    $previouslogo->delete();
                }
            }
            if ($tenant->document_media_id) {
                $previousdocument = Media::find($tenant->document_media_id);
                if ($previousdocument) {
                    Storage::disk('public')->delete($previousdocument->media_path);
                    $previousdocument->delete();
                }
            }
            DB::commit();

            return response()->json([
                'message' => 'The tenant and the associated user are  deleted successfully',
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
