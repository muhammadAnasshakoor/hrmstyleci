<?php

namespace App\Http\Controllers\API;

use App\Mail\CreateCompany;
use App\Models\Company;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Media;
use Spatie\Permission\Models\Role;
use App\Models\Tenant;
use Illuminate\Support\Facades\Mail; // Import Mail facade
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\CreateCompanyRequest;
use App\Http\Requests\CreateUserRequest;
use App\Models\User_type;
use Illuminate\Http\Response;

/**
 * @OA\Tag(
 *     name="Company",
 *     description="Handling the crud of company in it."
 * )
 */


class CompanyController extends Controller
{

    /**
     * @OA\Get(
     *      path="/api/company",
     *      summary="Get All active companies.Permission required = company.list",
     *      description="This endpoint retrieves information about something.",
     *      tags={"Company"},
     *      @OA\Response(response="200", description="Successful operation"),
     *      @OA\Response(response="401", description="Unauthorized"),
     * )
     */



    public function __construct()
    {
        // Apply middleware to all methods in the controller
        $this->middleware('checkPermission:company.list')->only('index', 'inactiveCompanies');
        $this->middleware('checkPermission:company.create')->only('create');
        $this->middleware('checkPermission:company.store')->only('store',);
        $this->middleware('checkPermission:company.edit')->only('show');
        $this->middleware('checkPermission:company.update')->only('update');
        $this->middleware('checkPermission:company.delete')->only('delete');
    }


    public function index()
    {

        $LoggedInUser = auth::user();
        if ($LoggedInUser->tenant) {
            $loggedInTenant = $LoggedInUser->tenant;
            $userid = $loggedInTenant->id;

            $companies = Company::where('tenant_id', $userid)
                ->where('status', '1')
                ->with('user')
                ->get();
            foreach ($companies as $company) {
                $companylogoid = $company->logo_media_id;
                if ($companylogoid !== null) {
                    $logomedia = Media::where('id', $companylogoid)->first();
                    if ($logomedia) {
                        $logomediapathurl = asset(Storage::url($logomedia->media_path));
                        $logomedia['media_path'] = $logomediapathurl;
                        $company['logo_media_id'] = $logomedia;
                    }
                }
                // handling the document in this case
                $companydocumentid = $company->document_media_id;
                if ($companylogoid !== null) {
                    $documentmedia = Media::where('id', $companydocumentid)->first();
                    if ($documentmedia) {
                        $documentmediapathurl = asset(Storage::url($documentmedia->media_path));
                        $documentmedia['media_path'] = $documentmediapathurl;
                        $company['document_media_id'] = $documentmedia;
                    }
                }
            }


            return response()->json([
                'message' => 'Successfully retrieved active companies.',
                'companies' => $companies,
            ], 200);
        }
    }


    /**
     * @OA\Get(
     *      path="/api/company/inactive-companies",
     *      summary="Get All inactive companies.Permission required = company.list",
     *      description="This endpoint retrieves information about something.",
     *      tags={"Company"},
     *      @OA\Response(response="200", description="Successful operation"),
     *      @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function inactiveCompanies()
    {

        $LoggedInUser = auth::user();
        if ($LoggedInUser->tenant) {
            $loggedInTenant = $LoggedInUser->tenant;
            $userid = $loggedInTenant->id;

            $companies = Company::where('tenant_id', $userid)
                ->where('status', '0')
                ->with('user')
                ->get();
            foreach ($companies as $company) {
                $companylogoid = $company->logo_media_id;
                if ($companylogoid !== null) {
                    $logomedia = Media::where('id', $companylogoid)->first();
                    if ($logomedia) {
                        $logomediapathurl = asset(Storage::url($logomedia->media_path));
                        $logomedia['media_path'] = $logomediapathurl;
                        $company['logo_media_id'] = $logomedia;
                    }
                }
                // handling the document in this case
                $companydocumentid = $company->document_media_id;
                if ($companylogoid !== null) {
                    $documentmedia = Media::where('id', $companydocumentid)->first();
                    if ($documentmedia) {
                        $documentmediapathurl = asset(Storage::url($documentmedia->media_path));
                        $documentmedia['media_path'] = $documentmediapathurl;
                        $company['document_media_id'] = $documentmedia;
                    }
                }
            }


            return response()->json([
                'message' => 'Successfully retrieved inactive companies.',
                'companies' => $companies,
            ], 200);
        }
    }
    /**
     * @OA\Get(
     *      path="/api/company/{id}",
     *      summary="GET The company.Permission required = company.edit",
     *      description="This endpoint Gives a specific  company.",
     *      tags={"Company"},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="The ID of the company ",
     *         @OA\Schema(
     *             type="integer",
     *
     *             format="int64"
     *         )
     *     ),
     *      @OA\Response(response="200", description="Successful operation"),
     *      @OA\Response(response="401", description="Unauthorized"),
     * )
     */

    public function show(string $id)
    {

        $company = Company::with('user')->findOrFail($id);
        // handling the logo in this case

        $companylogoid = $company->logo_media_id;
        $logomedia = 'null';
        if ($companylogoid !== null) {

            $logomedia = Media::where('id', $companylogoid)->first();
            if ($logomedia) {
                $logomediapathurl = asset("storage/{$logomedia->media_path}");
                $logomedia['media_path'] = $logomediapathurl;
            }
        }
        // handling the document in this case
        $companydocumentid = $company->document_media_id;
        $documentmedia = 'null';
        if ($companylogoid !== null) {

            $documentmedia = Media::where('id', $companydocumentid)->first();
            if ($documentmedia) {


                $documentmediapathurl = asset("storage/{$documentmedia->media_path}");
                $documentmedia['media_path'] = $documentmediapathurl;
            }
        }
        return response()->json([
            'company' => $company,
            'logomedia' => $logomedia,
            'documentmedia' => $documentmedia
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/company",
     *     summary="Create a new company.Permission required = company.store",
     *     description="This endpoint creates a new company.",
     *     tags={"Company"},
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="application/x-www-form-urlencoded",
     *             @OA\Schema(
     *                 type="object",
     *                 @OA\Property(
     *                     property="name",
     *                     type="string",
     *                     example="Acme Inc.",
     *                     description="The name of the company"
     *                 ),
     *  @OA\Property(
     *                     property="email",
     *                     type="email",
     *                     example="manasshakoor@gmail.com",
     *                     description="The email of the company"
     *                 ),
     *                 @OA\Property(
     *                     property="address",
     *                     type="string",
     *                     example="123 Main St, Anytown, CA",
     *                     description="The address of the company"
     *                 ),
     *                 @OA\Property(
     *                     property="city",
     *                type="string",
     *                     example="lahore",
     *                     description="The city of the company"
     *                 ),
     *                 @OA\Property(
     *                     property="country",
     *                     type="string",
     *                     example="pakistan",
     *                     description="The country of the company"
     *                 ),
     *                       @OA\Property(
     *                     property="state",
     *                     type="string",
     *                     example="state",
     *                     description="The state of the company =>nullable"
     *                 ),
     *                             @OA\Property(
     *                     property="zip_code",
     *                     type="number",
     *                     example="12345",
     *                     description="The zip_code of the company =>nullable"
     *                 ),
     *                            @OA\Property(
     *                     property="registration_no",
     *                       type="number",
     *                     example="12345",
     *                     description="The registration_no of the company =>nullable"
     *                 ),
     *                     @OA\Property(
     *                     property="note",
     *                     type="string",
     *                     example="abc",
     *                     description="The notes for the company =>nullable"
     *                 ),
     *                    @OA\Property(
     *                     property="branch",
     *                     type="string",
     *                     example="lahore branch",
     *                     description="The branch of the company =>nullable"
     *                 ),
     *                            @OA\Property(
     *                     property="phone_no",
     *                     type="number",
     *                     example="03452987687",
     *                     description="The phone_no of the company=>nullable"
     *                 ),
     *
     *                            @OA\Property(
     *                     property="document_media_id",
     *                     type="file",
     *                     example="",
     *                     description="The document_media of the company=>nullable"
     *                 ),
     *                            @OA\Property(
     *                     property="logo_media_id",
     *                     type="file",
     *                     example="",
     *                     description="The logo_media of the company=>nullable"
     *                 ),
     *             )
     *         )
     *     ),
     *     @OA\Response(response="201", description="Company created successfully"),
     *     @OA\Response(response="401", description="Unauthorized"),
     *     @OA\Response(response="422", description="Validation failed")
     * )
     */

    public function store(CreateCompanyRequest $companyRequest, CreateUserRequest $userRequest)
    {
        $companydata = $companyRequest->validated();
        $userdata = $userRequest->validated();
        try {
            DB::beginTransaction();
            // creating new user
            // assigning the companystatus to user
            // assigning the value of modified by
            $loggedinuser = auth::user();
            $loggedinuserid = $loggedinuser->id;
            if (isset($userdata['password']) && $userdata['password'] != null) {
                $additionalUserData = [
                    'modified_by' => $loggedinuserid
                ];
                // if user enter the password
                $password = $companyRequest->input('password');
            } else {
                $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*()_+{}|:<>?-=[];\',./';
                $password = substr(str_shuffle($characters), 0, 8);
                $additionalUserData = [
                    'password' => $password,
                    'modified_by' => $loggedinuserid
                ];
            }
            if ($companyRequest->hasFile('logo_media_id')) {
                $logofile = $companyRequest->file('logo_media_id');
                $logo_name = $logofile->getClientOriginalName();
                $logopath = $logofile->store('company/logo', 'public');
                $logoextension = $logofile->getClientOriginalExtension();
                // creating the new media for the logo
                $logomedia = Media::create([
                    'user_id' => $loggedinuserid,
                    'media_name' =>  $logo_name,
                    'media_path' => $logopath,
                    'extension' => $logoextension
                ]);
                $companydata['logo_media_id']  = $logomedia->id;
            }
            // handling the document from the company
            if ($companyRequest->hasFile('document_media_id')) {
                $documentfile = $companyRequest->file('document_media_id');
                $document_name = $documentfile->getClientOriginalName();
                $documentpath = $documentfile->store('company/document', 'public');
                $documentextension = $documentfile->getClientOriginalExtension();
                // creating the newmedia for the logo
                $documentmedia = Media::create([
                    'user_id' => $loggedinuserid,
                    'media_name' =>  $document_name,
                    'media_path' => $documentpath,
                    'extension' => $documentextension
                ]);
                $companydata['document_media_id'] = $documentmedia->id;
            }

            $mergedUserData = array_merge($userdata, $additionalUserData);
            $newuser = User::create($mergedUserData);

            // creating new company
            $newcompany =  $newuser->company()->create($companydata);

            // associating the company with the tenant
            $tenantaccociated = $loggedinuser->tenant;
            if ($tenantaccociated) {

                $tenantaccociated->companies()->save($newcompany);
            }
            // Create or find the company role
            $companyRole = Role::where('name', 'company')->where('guard_name', 'sanctum')->first();
            if (!$companyRole) {
                $companyRole = Role::create(['name' => 'company', 'guard_name' => 'sanctum']);
            }

            $newuser->assignRole($companyRole);

            // sending the emial to company with login credentials.
            $data = [
                'name' => $newcompany->name,
                'email' => $newuser->email,
                'password' => $password
            ];

            $email = $newuser->email;

            Mail::send('emails.LoginCredentials', $data, function ($message) use ($email) {
                $message->from('info@logicalcreations.net', 'Logical Creations');
                $message->to($email);
                $message->subject('Login credentials');
            });
            DB::commit();
            return response()->json([
                'message' => 'Company and associated user are created successfully',
                'company' => $newcompany,
                'user'  => $newuser
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => 'There was an error',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function create()
    {
    }



    /**
     * @OA\Post(
     *     path="/api/company/{company}",
     *     summary="Update the company.Permission required = company.update",
     *     description="This endpoint updates a company.",
     *     tags={"Company"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="The ID of the company to be updated",
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
     *                     example="Acme Inc.",
     *                     description="The name of the company"
     *                 ),
     *                 @OA\Property(
     *                     property="address",
     *                     type="string",
     *                     example="123 Main St, Anytown, CA",
     *                     description="The address of the company"
     *                 ),
     *                 @OA\Property(
     *                     property="status",
     *                     type="string",
     *                     example="1",
     *                     description="The status of the company"
     *                 ),
     *                 @OA\Property(
     *                     property="city",
     *                type="string",
     *                     example="lahore",
     *                     description="The city of the company"
     *                 ),
     *                 @OA\Property(
     *                     property="country",
     *                     type="string",
     *                     example="pakistan",
     *                     description="The country of the company"
     *                 ),
     *                       @OA\Property(
     *                     property="state",
     *                     type="string",
     *                     example="state",
     *                     description="The state of the company =>nullable"
     *                 ),
     *                             @OA\Property(
     *                     property="zip_code",
     *                     type="number",
     *                     example="12345",
     *                     description="The zip_code of the company =>nullable"
     *                 ),
     *                            @OA\Property(
     *                     property="registration_no",
     *                       type="number",
     *                     example="12345",
     *                     description="The registration_no of the company =>nullable"
     *                 ),
     *                     @OA\Property(
     *                     property="note",
     *                     type="string",
     *                     example="abc",
     *                     description="The notes for the company =>nullable"
     *                 ),
     *                    @OA\Property(
     *                     property="branch",
     *                     type="string",
     *                     example="lahore branch",
     *                     description="The branch of the company =>nullable"
     *                 ),
     *                            @OA\Property(
     *                     property="phone_no",
     *                     type="number",
     *                     example="03452987687",
     *                     description="The phone_no of the company=>nullable"
     *                 ),
     *
     *                            @OA\Property(
     *                     property="document_media_id",
     *                     type="file",
     *                     example="",
     *                     description="The document_media of the company=>nullable"
     *                 ),
     *                            @OA\Property(
     *                     property="logo_media_id",
     *                     type="file",
     *                     example="",
     *                     description="The logo_media of the company=>nullable"
     *                 ),
     *
     *
     *             )
     *         )
     *     ),
     *     @OA\Response(response="200", description="Company updated successfully"),
     *     @OA\Response(response="401", description="Unauthorized"),
     *     @OA\Response(response="422", description="Validation failed")
     * )
     */

    public function update(CreateCompanyRequest $companyRequest, Company $company)
    {
        $companydata = $companyRequest->validated();
        DB::beginTransaction();
        try {
            // handling the update  of user

            if ($companyRequest->filled('status')) {
                $status = $companydata['status'];
            } else {
                $status = $company->status;
            }

            $user = $company->user;
            $loggedinuser = auth::user();
            $loggedinuserid = $loggedinuser->id;
            $updateduser = [
                'status' => $status,
                'modified_by' => $loggedinuserid
            ];
            $user->update($updateduser);



            if ($companyRequest->hasFile('logo_media_id')) {
                if ($company->logo_media_id) {
                    $previouslogo = Media::find($company->logo_media_id);
                    if ($previouslogo) {
                        Storage::disk('public')->delete($previouslogo->media_path);
                        $previouslogo->delete();
                    }
                }
                $logofile = $companyRequest->file('logo_media_id');
                $logo_name = $logofile->getClientOriginalName();
                $logopath = $logofile->store('company/logo', 'public');
                $logoextension = $logofile->getClientOriginalExtension();
                // creating the new media for the logo
                $logomedia = Media::create([
                    'user_id' => $loggedinuserid,
                    'media_name' =>  $logo_name,
                    'media_path' => $logopath,
                    'extension' => $logoextension
                ]);
                $companydata['logo_media_id']  = $logomedia->id;
            }
            // handling the document from the company
            if ($companyRequest->hasFile('document_media_id')) {
                if ($company->document_media_id) {
                    $previousdocument = Media::find($company->document_media_id);
                    if ($previousdocument) {
                        Storage::disk('public')->delete($previousdocument->media_path);
                        $previousdocument->delete();
                    }
                }
                $documentfile = $companyRequest->file('document_media_id');
                $document_name = $documentfile->getClientOriginalName();
                $documentpath = $documentfile->store('company/document', 'public');
                $documentextension = $documentfile->getClientOriginalExtension();
                // creating the newmedia for the logo
                $documentmedia = Media::create([
                    'user_id' => $loggedinuserid,
                    'media_name' =>  $document_name,
                    'media_path' => $documentpath,
                    'extension' => $documentextension
                ]);
                $companydata['document_media_id'] = $documentmedia->id;
            }

            // Update the company data
            $company->update($companydata);
            // Assuming you want to use the updated company instance
            $newcompany = $company;

            // Associating the company with the tenant
            $tenantaccociated = $loggedinuser->tenant;
            if ($tenantaccociated) {
                $tenantaccociated->companies()->save($newcompany);
            }
            //if the company status is inactive then all duties related to it should be inactive
            if ($company->status == '0') {
                $duties =  $company->duties;
                foreach ($duties as $duty) {
                    $duty->update(['status' => '0']);
                }
            }

            if ($company->status == '1') {
                $duties =  $company->duties;
                foreach ($duties as $duty) {
                    $duty->update(['status' => '1']);
                }
            }

            DB::commit();
            return response()->json([
                'message' => 'The company have been updated',

                'company' => $company
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => 'There was an error',
                'error' => $e->getMessage()
            ]);
        }
    }
    /**
     * @OA\Delete(
     *      path="/api/company/{id}",
     *      summary="Delete The company.Permission required = company.delete",
     *      description="This endpoint delete company.",
     *      tags={"Company"},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="The ID of the company to be deleted",
     *         @OA\Schema(
     *             type="integer",
     *             format="int64"
     *         )
     *     ),
     *      @OA\Response(response="200", description="Successful operation"),
     *      @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function destroy(Company $company)
    {
        try {
            DB::beginTransaction();


            $duties = $company->duties()->where('status', '1')->count();
            if ($duties > 0) {
                return response()->json([
                    'message' => 'Cannot delete company. There are active duties associated with it.',
                ]);
            }

            $company->delete();
            $company->user()->delete();
            if ($company->logo_media_id) {
                $previouslogo = Media::find($company->logo_media_id);
                if ($previouslogo) {
                    Storage::disk('public')->delete($previouslogo->media_path);
                    $previouslogo->delete();
                }
            }
            if ($company->document_media_id) {
                $previousdocument = Media::find($company->document_media_id);
                if ($previousdocument) {
                    Storage::disk('public')->delete($previousdocument->media_path);
                    $previousdocument->delete();
                }
            }
            DB::commit();
            return response()->json([
                'message' => 'Company  and  associated user are  deleted successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([

                'message' => 'There was an error deleting the company and associated user',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
