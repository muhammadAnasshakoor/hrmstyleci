<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Media;
use App\Models\Designation;
use Spatie\Permission\Models\Role;
use App\Models\Employee;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail; // Import Mail facade

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\CreateEmployeeRequest;
use App\Http\Requests\CreateUserRequest;
use Symfony\Component\HttpKernel\Profiler\Profile;


/**
 * @OA\Tag(
 *     name="Employee",
 *     description="Handling the crud of Employee in it."
 * )
 */



class EmployeeController extends Controller
{
    public function __construct()
    {
        // Apply middleware to all methods in the controller
        $this->middleware('checkPermission:employee.list')->only('index', 'inactiveEmployees');
        $this->middleware('checkPermission:employee.create')->only('create');
        $this->middleware('checkPermission:employee.store')->only('store');
        $this->middleware('checkPermission:employee.edit')->only('show');
        $this->middleware('checkPermission:employee.update')->only('update');
        $this->middleware('checkPermission:employee.delete')->only('delete');
    }
    /**
     * Display a listing of the resource.
     */

    /**
     * @OA\Get(
     *      path="/api/employee",
     *      summary="Get All active employees.Permission required = employee.list",
     *      description="This endpoint retrieves all  employees.",
     *      tags={"Employee"},
     *      @OA\Response(response="200", description="Successful operation"),
     *      @OA\Response(response="401", description="Unauthorized"),
     * )
     */


    public function index()
    {
        $loggedInUser = auth()->user();
        $employees = []; // Initialize an empty array to store employees
        $duties = []; // Initialize an empty array to store duties

        if ($loggedInUser->tenant) {
            $loggedInTenant = $loggedInUser->tenant;
            $employees = Employee::where('tenant_id', $loggedInTenant->id)
                ->where('status', '1')
                ->get();
        } elseif ($loggedInUser->company) {
            $loggedInCompany = $loggedInUser->company;
            $duties = $loggedInCompany->duties()
                ->where('status', '1')
                ->get();
            foreach ($duties as $duty) {
                $employee = $duty->employee;
                $employee->designation;
                $this->handleImages($employee);
                $duty->policy;
                $duty->equipments;
            }
        }

        // Check if $employees is not null
        if (!is_null($employees)) {
            foreach ($employees as $employee) {
                $employee->load('designation');
                if ($loggedInUser->tenant) {
                    // Eager load the 'user' relationship
                    $employee->load('user');
                }
                $this->handleImages($employee);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Active Employees and duties retrieved successfully.',
            'data' => [
                'employees' => $employees,
                'duties' => $duties
            ]
        ], 200);
    }




    /**
     * @OA\Get(
     *      path="/api/employee/inactive-employees",
     *      summary="Get All Inactive employees.Permission required = employee.list",
     *      description="This endpoint retrieves all  employees.",
     *      tags={"Employee"},
     *      @OA\Response(response="200", description="Successful operation"),
     *      @OA\Response(response="401", description="Unauthorized"),
     * )
     */


    public function inactiveEmployees()
    {
        $loggedInUser = auth()->user();
        $employees = []; // Initialize an empty array to store employees
        $duties = []; // Initialize an empty array to store duties

        if ($loggedInUser->tenant) {
            $loggedInTenant = $loggedInUser->tenant;
            $employees = Employee::where('tenant_id', $loggedInTenant->id)
                ->where('status', '0')
                ->get();
        } elseif ($loggedInUser->company) {
            $loggedInCompany = $loggedInUser->company;
            $duties = $loggedInCompany->duties()
                ->where('status', '0')
                ->get();
            foreach ($duties as $duty) {
                $employee = $duty->employee;
                $employee->designation;
                $this->handleImages($employee);
                $duty->policy;
                $duty->equipments;
            }
        }

        // Check if $employees is not null
        if (!is_null($employees)) {
            foreach ($employees as $employee) {
                $employee->designation;
                if ($loggedInUser->tenant) {
                    // Eager load the 'user' relationship
                    $employee->load('user');
                }
                $this->handleImages($employee);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Inactive Employees and duties retrieved successfully.',
            'data' => [
                'employees' => $employees,
                'duties' => $duties
            ]
        ], 200);
    }





    private function handleImages($employee)
    {
        // Handling the profile image
        if (!is_null($employee->profile_image_id)) {
            $profileMedia = Media::find($employee->profile_image_id);
            if ($profileMedia) {
                $profileMediaPathUrl = asset("storage/{$profileMedia->media_path}");
                $profileMedia['media_path'] = $profileMediaPathUrl;
                $employee['profile_image_id'] = $profileMedia;
            }
        }

        // Handling the resume image
        if (!is_null($employee->resume_image_id)) {
            $resumeMedia = Media::find($employee->resume_image_id);
            if ($resumeMedia) {
                $resumeMediaPathUrl = asset("storage/{$resumeMedia->media_path}");
                $resumeMedia['media_path'] = $resumeMediaPathUrl;
                $employee['resume_image_id'] = $resumeMedia;
            }
        }

        // Handling the Emirates ID image
        if (!is_null($employee->emirates_image_id)) {
            $emiratesMedia = Media::find($employee->emirates_image_id);
            if ($emiratesMedia) {
                $emiratesMediaPathUrl = asset("storage/{$emiratesMedia->media_path}");
                $emiratesMedia['media_path'] = $emiratesMediaPathUrl;
                $employee['emirates_image_id'] = $emiratesMedia;
            }
        }

        // Handling the passport image
        if (!is_null($employee->passport_image_id)) {
            $passportMedia = Media::find($employee->passport_image_id);
            if ($passportMedia) {
                $passportMediaPathUrl = asset("storage/{$passportMedia->media_path}");
                $passportMedia['media_path'] = $passportMediaPathUrl;
                $employee['passport_image_id'] = $passportMedia;
            }
        }
    }


    /**
     * Show the form for creating a new resource.
     */
    /**
     * @OA\Get(
     *      path="/api/employee/create",
     *      summary="Get All designation.Permission required = employee.create",
     *      description="This endpoint retrieves all  designation related to this logged in tenant.",
     *      tags={"Employee"},
     *      @OA\Response(response="200", description="Successful operation"),
     *      @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function create()
    {
        $LoggedInUser = auth::user();
        $loggedInTenant = $LoggedInUser->tenant;
        $loggedInTenantId = $loggedInTenant->id;

        $activedesignation = Designation::where('status', '1')
            ->where('tenant_id', $loggedInTenantId)
            ->select('id', 'title')
            ->get();
        return response()->json([
            'message' => 'Active designations retrieved successfully',
            'designation' => $activedesignation
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    /**
     * @OA\Post(
     *     path="/api/employee",
     *     summary="Create a new employee.Permission required = employee.store",
     *     description="This endpoint creates a new employee.",
     *     tags={"Employee"},
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="application/x-www-form-urlencoded",
     *             @OA\Schema(
     *                 type="object",
     *                 @OA\Property(
     *                      property="name",
     *                     type="string",
     *                     example="New Employee",
     *                     description="The name of the employee =>required"
     *                 ),
     *
     *
     *                 @OA\Property(
     *                     property="phone_no",
     *                     type="number",
     *                     example="0332322112",
     *                     description="The phon3_no of the employee => required"
     *                 ),
     *
     *                 @OA\Property(
     *                     property="gender",
     *                     type="checkbox",
     *                     example="male",
     *                     description="The gender of the employee => required"
     *                 ),
     *                 @OA\Property(
     *                     property="emirates_id",
     *                     type="number",
     *                     example="1212121211211",
     *                     description="The emirates_id of the employee => required"
     *                 ),
     *                 @OA\Property(
     *                     property="permanent_address",
     *                     type="string",
     *                     example="lahore 123 street ",
     *                     description="The permanent_address of the employee =>required"
     *                 ),
     *                 @OA\Property(
     *                     property="local_address",
     *                     type="string",
     *                     example="lahore 123 street ",
     *                     description="The local_address of the employee =>required"
     *                 ),
     *                             @OA\Property(
     *                     property="nationality",
     *                     type="strign",
     *                     example="Pakistan",
     *                     description="The nationality of the employee =>required"
     *                 ),
     *                            @OA\Property(
     *                     property="city",
     *                       type="string",
     *                     example="lahore",
     *                     description="The city of the employee =>required"
     *                 ),
     *                             @OA\Property(
     *                     property="acount_title",
     *                     type="string",
     *                     example="M Anas Shakoor",
     *                     description="The acount_title of the employee=>required"
     *                 ),
     *                          @OA\Property(
     *                     property="acount_no",
     *                     type="number",
     *                     example="12121212121",
     *                     description="The acount_no of the employee =>required"
     *                 ),
     *                     @OA\Property(
     *                     property="bank_name",
     *                     type="string",
     *                     example="abc",
     *                     description="The bank_name for the employee =>required"
     *                 ),
     *                    @OA\Property(
     *                     property="branch_name",
     *                     type="string",
     *                     example="lahore branch",
     *                     description="The branch_name of the employee =>required"
     *                 ),
     *                            @OA\Property(
     *                     property="email",
     *                     type="email",
     *                     example="abc@gmail.com",
     *                     description="The emial of the employee=>required"
     *                 ),
     *                            @OA\Property(
     *                     property="state",
     *                     type="string",
     *                     example="pakistan",
     *                     description="The state of the employee=>nullable"
     *                 ),
     *                       @OA\Property(
     *                     property="zip_code",
     *                     type="number",
     *                     example="12121212",
     *                     description="The zip_code of the employee=>nullable"
     *                 ),
     *                            @OA\Property(
     *                     property="designation_id",
     *                     type="integer",
     *                     example="1",
     *                     description="The designation of the employee=>nullable"
     *                 ),
     *
     *                            @OA\Property(
     *                     property="profile_image_id",
     *                     type="file",
     *                     example="",
     *                     description="The profile_image of the employee=>nullable"
     *                 ),
     *                            @OA\Property(
     *                     property="passport_image_id",
     *                     type="file",
     *                     example="",
     *                     description="The passport_image of the employee=>nullable"
     *                 ),
     *                            @OA\Property(
     *                     property="emirates_image_id",
     *                     type="file",
     *                     example="",
     *                     description="The emirates_image of the employee=>nullable"
     *                 ),
     *                            @OA\Property(
     *                     property="resume_image_id",
     *                     type="file",
     *                     example="",
     *                     description="The resume_image of the employee=>nullable"
     *                 ),
     *             )
     *         )
     *     ),
     *     @OA\Response(response="200", description="employee updated successfully"),
     *     @OA\Response(response="401", description="Unauthorized"),
     *     @OA\Response(response="422", description="Validation failed")
     * )
     */

    public function store(CreateEmployeeRequest $employeeRequest, CreateUserRequest $userRequest)
    {

        $employeedata = $employeeRequest->validated();
        $userdata = $userRequest->validated();
        DB::beginTransaction();
        try {

            // CREATING NEW USER

            // assigning the employee status to user
            $userstatus = $employeedata['status'];

            // assigning the value of modified by
            $loggedinuser = auth::user();
            $loggedinuserid = $loggedinuser->id;
            if (isset($userdata['password']) && $userdata['password'] != null) {

                $additionalUserData = [

                    'modified_by' => $loggedinuserid,
                    $password = $userRequest->input('password')
                ];
            } else {
                $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*()_+{}|:<>?-=[];\',./';
                $password = substr(str_shuffle($characters), 0, 8);

                $additionalUserData = [
                    'password' => $password,
                    'modified_by' => $loggedinuserid
                ];
            }
            $mergedUserData = array_merge($userdata, $additionalUserData);

            //finally creating the newuser
            $newuser = User::create($mergedUserData);


            // handling the media files files

            // profile_image
            if ($employeeRequest->hasFile('profile_image_id')) {
                $profileimagefile = $employeeRequest->file('profile_image_id');
                $profileimage_name = $profileimagefile->getClientOriginalName();
                $profileimagepath = $profileimagefile->store('employee/profile', 'public');
                $profileimageextension = $profileimagefile->getClientOriginalExtension();

                // creating the new media for the profileimage
                $profileimagemedia = Media::create([
                    'user_id' => $loggedinuserid,
                    'media_name' =>  $profileimage_name,
                    'media_path' => $profileimagepath,
                    'extension' => $profileimageextension
                ]);
                $employeedata['profile_image_id']  = $profileimagemedia->id;
            }

            //resume_image
            if ($employeeRequest->hasFile('resume_image_id')) {
                $resumeimagefile = $employeeRequest->file('resume_image_id');
                $resumeimage_name = $resumeimagefile->getClientOriginalName();
                $resumeimagepath = $resumeimagefile->store('employee/resume', 'public');
                $resumeimageextension = $resumeimagefile->getClientOriginalExtension();

                // creating the new media for the resumeimage
                $resumeimagemedia = Media::create([
                    'user_id' => $loggedinuserid,
                    'media_name' =>  $resumeimage_name,
                    'media_path' => $resumeimagepath,
                    'extension' => $resumeimageextension
                ]);
                $employeedata['resume_image_id']  = $resumeimagemedia->id;
            }

            //passport_image
            if ($employeeRequest->hasFile('passport_image_id')) {
                $passportimagefile = $employeeRequest->file('passport_image_id');
                $passportimage_name = $passportimagefile->getClientOriginalName();
                $passportimagepath = $passportimagefile->store('employee/passport', 'public');
                $passportimageextension = $passportimagefile->getClientOriginalExtension();

                // creating the new media for the passportimage
                $passportimagemedia = Media::create([
                    'user_id' => $loggedinuserid,
                    'media_name' =>  $passportimage_name,
                    'media_path' => $passportimagepath,
                    'extension' => $passportimageextension
                ]);
                $employeedata['passport_image_id']  = $passportimagemedia->id;
            }

            //emirates_image
            if ($employeeRequest->hasFile('emirates_image_id')) {
                $emiratesimagefile = $employeeRequest->file('emirates_image_id');
                $emiratesimage_name = $emiratesimagefile->getClientOriginalName();
                $emiratesimagepath = $emiratesimagefile->store('employee/emirates_id', 'public');
                $emiratesimageextension = $emiratesimagefile->getClientOriginalExtension();

                // creating the new media for the emiratesimage
                $emiratesimagemedia = Media::create([
                    'user_id' => $loggedinuserid,
                    'media_name' =>  $emiratesimage_name,
                    'media_path' => $emiratesimagepath,
                    'extension' => $emiratesimageextension
                ]);
                $employeedata['emirates_image_id']  = $emiratesimagemedia->id;
            }

            // CREATING NEW EMPLOYEE
            $newemployee =  $newuser->employee()->create($employeedata);

            // associating the employee with the tenant
            $tenantaccociated = $loggedinuser->tenant;
            if ($tenantaccociated) {

                $tenantaccociated->employees()->save($newemployee);
            }


            $employeeRole = Role::where('name', 'employee')->where('guard_name', 'sanctum')->first();
            if (!$employeeRole) {
                $employeeRole = Role::create(['name' => 'employee', 'guard_name' => 'sanctum']);
            }
            $newuser->assignRole($employeeRole);


            // sending the emial to employee with login credentials
            $data = [
                'name' => $newemployee->name,
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
                'message' => 'employee and associated user are created successfully',
                'employee' => $newemployee,
                'user'  => $newuser,
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => 'There was an error',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * @OA\Get(
     *      path="/api/employee/{id}",
     *      summary="GET The Employee.Permission required = employee.edit",
     *      description="This endpoint Gives a specific  Employee.",
     *      tags={"Employee"},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="The ID of the Employee ",
     *         @OA\Schema(
     *             type="integer",
     *             format="int64"
     *         )
     *     ),
     *      @OA\Response(response="200", description="Successful operation"),
     *      @OA\Response(response="401", description="Unauthorized"),
     * )
     */

    public function show(string $id)
    {
        $Employee = Employee::with('user:id,email', 'designation:id,title')->findOrFail($id);
        // handling the profile in this case
        $EmployeeProfileid = $Employee->profile_image_id;
        if ($EmployeeProfileid !== null) {

            $Profilemedia = Media::where('id', $EmployeeProfileid)->first();
            if ($Profilemedia) {


                $Profilemediapathurl = asset("storage/{$Profilemedia->media_path}");
                $Profilemedia['media_path'] = $Profilemediapathurl;
                $Employee['profile_image_id'] = $Profilemedia;
            }
        }
        // handling the profile in this case
        $Employeeresumeid = $Employee->resume_image_id;
        if ($Employeeresumeid !== null) {

            $resumemedia = Media::where('id', $Employeeresumeid)->first();
            if ($resumemedia) {


                $resumemediapathurl = asset("storage/{$resumemedia->media_path}");
                $resumemedia['media_path'] = $resumemediapathurl;
                $Employee['resume_image_id'] = $resumemedia;
            }
        }
        // handling the emirates id in this case
        $Employeeemiratesid = $Employee->emirates_image_id;
        if ($Employeeemiratesid !== null) {

            $emiratesmedia = Media::where('id', $Employeeemiratesid)->first();
            if ($emiratesmedia) {


                $emiratesmediapathurl = asset("storage/{$emiratesmedia->media_path}");
                $emiratesmedia['media_path'] = $emiratesmediapathurl;
                $Employee['emirates_image_id'] = $emiratesmedia;
            }
        }
        // handling the profile in this case
        $Employeepassportid = $Employee->passport_image_id;
        if ($Employeepassportid !== null) {

            $passportmedia = Media::where('id', $Employeepassportid)->first();
            if ($passportmedia) {


                $passportmediapathurl = asset("storage/{$passportmedia->media_path}");
                $passportmedia['media_path'] = $passportmediapathurl;
                $Employee['passport_image_id'] = $passportmedia;
            }
        }

        return response()->json([
            'message' => 'Employee data retrived successfully',
            'Employee' => $Employee,
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    /**
     * @OA\Post(
     *     path="/api/employee/{employee}",
     *     summary="Update the employee.Permission required = employee.update",
     *     description="This endpoint updates a employee.",
     *     tags={"Employee"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="The ID of the employee to be updated",
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
     *                     example="New Employee",
     *                     description="The name of the employee =>required"
     *                 ),
     *
     *
     *                 @OA\Property(
     *                     property="phone_no",
     *                     type="number",
     *                     example="0332322112",
     *                     description="The phon3_no of the employee => required"
     *                 ),
     *
     *                 @OA\Property(
     *                     property="gender",
     *                     type="checkbox",
     *                     example="male",
     *                     description="The gender of the employee => required"
     *                 ),
     *                 @OA\Property(
     *                     property="emirates_id",
     *                     type="number",
     *                     example="1212121211211",
     *                     description="The emirates_id of the employee => required"
     *                 ),
     *                 @OA\Property(
     *                     property="permanent_address",
     *                     type="string",
     *                     example="lahore 123 street ",
     *                     description="The permanent_address of the employee =>required"
     *                 ),
     *                 @OA\Property(
     *                     property="local_address",
     *                     type="string",
     *                     example="lahore 123 street ",
     *                     description="The local_address of the employee =>required"
     *                 ),
     *                             @OA\Property(
     *                     property="nationality",
     *                     type="strign",
     *                     example="Pakistan",
     *                     description="The nationality of the employee =>required"
     *                 ),
     *                            @OA\Property(
     *                     property="city",
     *                       type="string",
     *                     example="lahore",
     *                     description="The city of the employee =>required"
     *                 ),
     *                             @OA\Property(
     *                     property="acount_title",
     *                     type="string",
     *                     example="M Anas Shakoor",
     *                     description="The acount_title of the employee=>required"
     *                 ),
     *                          @OA\Property(
     *                     property="acount_no",
     *                     type="number",
     *                     example="12121212121",
     *                     description="The acount_no of the employee =>required"
     *                 ),
     *                     @OA\Property(
     *                     property="bank_name",
     *                     type="string",
     *                     example="abc",
     *                     description="The bank_name for the employee =>required"
     *                 ),
     *                    @OA\Property(
     *                     property="branch_name",
     *                     type="string",
     *                     example="lahore branch",
     *                     description="The branch_name of the employee =>required"
     *                 ),
     *                            @OA\Property(
     *                     property="status",
     *                     type="string",
     *                     example="1",
     *                     description="The status of the employee=>nullable"
     *                 ),
     *                            @OA\Property(
     *                     property="state",
     *                     type="string",
     *                     example="pakistan",
     *                     description="The state of the employee=>nullable"
     *                 ),
     *                       @OA\Property(
     *                     property="zip_code",
     *                     type="number",
     *                     example="12121212",
     *                     description="The zip_code of the employee=>nullable"
     *                 ),
     *                            @OA\Property(
     *                     property="designation_id",
     *                     type="integer",
     *                     example="1",
     *                     description="The designation of the employee=>nullable"
     *                 ),
     *
     *                            @OA\Property(
     *                     property="profile_image_id",
     *                     type="file",
     *                     example="",
     *                     description="The profile_image of the employee=>nullable"
     *                 ),
     *                            @OA\Property(
     *                     property="passport_image_id",
     *                     type="file",
     *                     example="",
     *                     description="The passport_image of the employee=>nullable"
     *                 ),
     *                            @OA\Property(
     *                     property="emirates_image_id",
     *                     type="file",
     *                     example="",
     *                     description="The emirates_image of the employee=>nullable"
     *                 ),
     *                            @OA\Property(
     *                     property="resume_image_id",
     *                     type="file",
     *                     example="",
     *                     description="The resume_image of the employee=>nullable"
     *                 ),
     *             )
     *         )
     *     ),
     *     @OA\Response(response="200", description="employee updated successfully"),
     *     @OA\Response(response="401", description="Unauthorized"),
     *     @OA\Response(response="422", description="Validation failed")
     * )
     */



    public function update(CreateEmployeeRequest $employeeRequest, Employee $employee)
    {
        DB::beginTransaction();
        try {
            $employeedata = $employeeRequest->validated();
            // UPDATING NEW USER

            if ($employeeRequest->filled('status')) {
                // assigning the employee status to user
                $status = $employeedata['status'];
            } else {
                $status = $employee->status;
            }


            // Check if the status is being updated to inactive and if the employee has active duties
            if ($status == '0') {
                $active_duties_count = $employee->duties()
                    ->where('status', '1')
                    ->count();

                if ($active_duties_count > 0) {
                    // Fetch the details of active duties if needed
                    $active_duties = $employee->duties()->where('status', '1')->get();

                    return response()->json([
                        'message' => 'This employee cannot be inactivated as he/she is assigned to active duties',
                        'active_duties' => $active_duties,
                        'active_duties_count' => $active_duties_count
                    ], 422);
                }
            }
            $user = $employee->user;
            $loggedinuser = auth::user();
            $loggedinuserid = $loggedinuser->id;
            $updateduserdata = [
                'status' => $status,
                'modified_by' => $loggedinuserid
            ];

            // finally updating the user\
            $user->update($updateduserdata);

            // handling the media files files
            // profile_image
            if ($employeeRequest->hasFile('profile_image_id')) {

                if ($employee->profile_image_id) {
                    $previousprofile = Media::find($employee->profile_image_id);
                    if ($previousprofile) {
                        Storage::disk('public')->delete($previousprofile->media_path);
                        $previousprofile->delete();
                    }
                }

                $profileimagefile = $employeeRequest->file('profile_image_id');
                $profileimage_name = $profileimagefile->getClientOriginalName();
                $profileimagepath = $profileimagefile->store('employee/profile', 'public');
                $profileimageextension = $profileimagefile->getClientOriginalExtension();

                // creating the new media for the profileimage
                $profileimagemedia = Media::create([
                    'user_id' => $loggedinuserid,
                    'media_name' =>  $profileimage_name,
                    'media_path' => $profileimagepath,
                    'extension' => $profileimageextension
                ]);
                $employeedata['profile_image_id']  = $profileimagemedia->id;
            }

            //resume_image
            if ($employeeRequest->hasFile('resume_image_id')) {

                if ($employee->resume_image_id) {
                    $previousresume = Media::find($employee->resume_image_id);
                    if ($previousresume) {
                        Storage::disk('public')->delete($previousresume->media_path);
                        $previousresume->delete();
                    }
                }
                $resumeimagefile = $employeeRequest->file('resume_image_id');
                $resumeimage_name = $resumeimagefile->getClientOriginalName();
                $resumeimagepath = $resumeimagefile->store('employee/resume', 'public');
                $resumeimageextension = $resumeimagefile->getClientOriginalExtension();

                // creating the new media for the resumeimage
                $resumeimagemedia = Media::create([
                    'user_id' => $loggedinuserid,
                    'media_name' =>  $resumeimage_name,
                    'media_path' => $resumeimagepath,
                    'extension' => $resumeimageextension
                ]);
                $employeedata['resume_image_id']  = $resumeimagemedia->id;
            }

            //passport_image
            if ($employeeRequest->hasFile('passport_image_id')) {
                if ($employee->passport_image_id) {
                    $previouspassport = Media::find($employee->passport_image_id);
                    if ($previouspassport) {
                        Storage::disk('public')->delete($previouspassport->media_path);
                        $previouspassport->delete();
                    }
                }
                $passportimagefile = $employeeRequest->file('passport_image_id');
                $passportimage_name = $passportimagefile->getClientOriginalName();
                $passportimagepath = $passportimagefile->store('employee/passport', 'public');
                $passportimageextension = $passportimagefile->getClientOriginalExtension();

                // creating the new media for the passportimage
                $passportimagemedia = Media::create([
                    'user_id' => $loggedinuserid,
                    'media_name' =>  $passportimage_name,
                    'media_path' => $passportimagepath,
                    'extension' => $passportimageextension
                ]);
                $employeedata['passport_image_id']  = $passportimagemedia->id;
            }

            //emirates_image
            if ($employeeRequest->hasFile('emirates_image_id')) {
                if ($employee->emirates_image_id) {
                    $previousemirates = Media::find($employee->emirates_image_id);
                    if ($previousemirates) {
                        Storage::disk('public')->delete($previousemirates->media_path);
                        $previousemirates->delete();
                    }
                }

                $emiratesimagefile = $employeeRequest->file('emirates_image_id');
                $emiratesimage_name = $emiratesimagefile->getClientOriginalName();
                $emiratesimagepath = $emiratesimagefile->store('employee/emirates_id', 'public');
                $emiratesimageextension = $emiratesimagefile->getClientOriginalExtension();

                // creating the new media for the emiratesimage
                $emiratesimagemedia = Media::create([
                    'user_id' => $loggedinuserid,
                    'media_name' =>  $emiratesimage_name,
                    'media_path' => $emiratesimagepath,
                    'extension' => $emiratesimageextension
                ]);
                $employeedata['emirates_image_id']  = $emiratesimagemedia->id;
            }

            // UPDATING THE EMPLOYEE
            // finally creating the new employee
            $employee->update($employeedata);
            $newemployee = $employee;
            // associating the employee with the tenant
            $tenantaccociated = $loggedinuser->tenant;
            if ($tenantaccociated) {

                $tenantaccociated->employees()->save($newemployee);
            }
            DB::commit();
            return response()->json([
                'message' => 'employee and associated user are updated successfully',
                'employee' => $employee
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => 'There was an error',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Remove the specified resource from storage.
     */
    /**
     * @OA\Delete(
     *      path="/api/employee/{id}",
     *      summary="Delete The Employee.Permission required = employee.delete",
     *      description="This endpoint deletes Employee.",
     *      tags={"Employee"},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="The ID of the Employee to be deleted",
     *         @OA\Schema(
     *             type="integer",
     *             format="int64"
     *         )
     *     ),
     *      @OA\Response(response="200", description="Successful operation"),
     *      @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function destroy(Employee $employee)
    {
        DB::beginTransaction();
        try {

            $active_duties = $employee->duties()
                ->where('status', '1')
                ->count();

            if ($active_duties > 0) {
                return response()->json([
                    'message' => 'Cannot delete employee. There are active duties associated with them.'
                ]);
            }

            $employee->delete();
            $employee->user()->delete();


            if ($employee->profile_image_id) {
                $previousprofile = Media::find($employee->profile_image_id);
                if ($previousprofile) {
                    Storage::disk('public')->delete($previousprofile->media_path);
                    $previousprofile->delete();
                }
            }
            if ($employee->resume_image_id) {
                $previousresume = Media::find($employee->resume_image_id);
                if ($previousresume) {
                    Storage::disk('public')->delete($previousresume->media_path);
                    $previousresume->delete();
                }
            }
            if ($employee->passport_image_id) {
                $previouspassport = Media::find($employee->passport_image_id);
                if ($previouspassport) {
                    Storage::disk('public')->delete($previouspassport->media_path);
                    $previouspassport->delete();
                }
            }
            if ($employee->emirates_image_id) {
                $previousemirates = Media::find($employee->emirates_image_id);
                if ($previousemirates) {
                    Storage::disk('public')->delete($previousemirates->media_path);
                    $previousemirates->delete();
                }
            }

            DB::commit();
            return response()->json([
                'message' => 'The Employee and the associated user is deleted',

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
