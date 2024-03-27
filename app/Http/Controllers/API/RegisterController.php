<?php

namespace App\Http\Controllers\API;

use App\Models\Media;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
// use Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Tag(
 *     name="Login",
 *
 * )
 */
class RegisterController extends BaseController
{
    /**
     * Register api.
     *
     * @return \Illuminate\Http\Response
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name'       => 'required',
            'email'      => 'required|email',
            'password'   => 'required',
            'c_password' => 'required|same:password',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors());
        }

        $input = $request->all();
        $input['password'] = bcrypt($input['password']);
        $user = User::create($input);
        $success['token'] = $user->createToken('MyApp')->plainTextToken;
        $success['name'] = $user->name;

        return $this->sendResponse($success, 'User registered successfully.');
    }

    /**
     * @OA\Post(
     *     path="/api/login",
     *     summary="Authenticate user and generate JWT token",
     *           tags={"Login"},
     *
     *     @OA\Parameter(
     *         name="email",
     *         in="query",
     *         description="User's email",
     *         required=true,
     *
     *         @OA\Schema(type="string")
     *     ),
     *
     *     @OA\Parameter(
     *         name="password",
     *         in="query",
     *         description="User's password",
     *         required=true,
     *
     *         @OA\Schema(type="string")
     *     ),
     *
     *     @OA\Response(response="200", description="Login successful"),
     *     @OA\Response(response="401", description="Invalid credentials")
     * )
     */
    public function login(Request $request): JsonResponse
    {
        // Validate the request data
        $validator = Validator::make($request->all(), [
            'email'     => ['required', 'email'],
            'password'  => ['required'],
            'user_type' => ['nullable'],
        ]);

        // Check if validation fails
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation Error',
                'errors'  => $validator->errors(),
            ], 422);
        }

        DB::beginTransaction();

        try {
            // Attempt authentication
            if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
                $user = Auth::user();
                if ($user->status != '1') {
                    return response()->json([
                        'message' => 'Login Failed',
                        'error'   => 'Your account is inactive. Please contact the administrator for assistance or reactivate your account.',
                    ]);
                }

                $rolesData = [];
                $permissionsData = [];
                $name = [];
                if ($user->company) {
                    $company = $user->company;
                    $name = $company->name;

                    //handling the logo of the company
                    if (!is_null($company->logo_media_id)) {
                        $logo_media = Media::find($company->logo_media_id);
                        if ($logo_media) {
                            $logo_media_url = asset("storage/{$logo_media->media_path}");
                            $success['Company logo'] = $logo_media_url;
                        }
                    }
                } elseif ($user->tenant) {
                    $tenant = $user->tenant;
                    $name = $tenant->name;
                    //handling the logo of the tenant
                    if (!is_null($tenant->logo_media_id)) {
                        $logo_media = Media::find($tenant->logo_media_id);
                        if ($logo_media) {
                            $logo_media_url = asset("storage/{$logo_media->media_path}");
                            $success['Tenant logo'] = $logo_media_url;
                        }
                    }
                } elseif ($user->employee) {
                    $employee = $user->employee;
                    $name = $employee->name;

                    // Handling the profile image
                    if (!is_null($employee->profile_image_id)) {
                        $profile_media = Media::find($employee->profile_image_id);
                        if ($profile_media) {
                            $profile_media_url = asset("storage/{$profile_media->media_path}");
                            $success['Employee Profile image'] = $profile_media_url;
                        }
                    }
                }

                $roles = $user->roles()->get();
                // Retrieve roles and permissions
                foreach ($roles as $role) {
                    $rolesData[] = $role->name;

                    foreach ($role->permissions as $permission) {
                        $permissionsData[] = $permission->name;
                    }
                }

                // Formulate the success response
                $success['token'] = $user->createToken('Auth_Token')->plainTextToken;
                $success['name'] = $name; // Assigning user's name directly
                $success['user'] = $user;
                $success['roles'] = $rolesData;
                $success['permissions'] = $permissionsData;

                DB::commit();

                return response()->json([
                    'message' => 'User login successful to HRM.',
                    'success' => true,
                    'data'    => $success,
                ], 200);
            } else {
                DB::rollback();

                return response()->json([
                    'success' => false,
                    'message' => 'Invalid email or password.',
                ], 401);
            }
        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'success' => false,
                'message' => 'There was an error',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
