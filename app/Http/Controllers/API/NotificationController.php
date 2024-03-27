<?php

namespace App\Http\Controllers\API;

use App\Events\NotifyUser;
use App\Models\Designation;
use App\Models\User;
use App\Models\Company;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;


use App\Http\Requests\CreateDesignationRequest;
use App\Http\Requests\CreateNotificationRequest;
use App\Models\Employee;
use PDO;

class NotificationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
      $user = auth::user();
      $tenant_id = $user->tenant->id;

      $employees = Employee::where('tenant_id',$tenant_id)
      ->where('status','1')
      ->select('name','id','emirates_id')
      ->get();
      $companies = Company::where('tenant_id',$tenant_id)
      ->where('status','1')
      ->select('name','id')
      ->get();

      return response()->json([
        'message' => 'Data fetched successfully',
        'Employees' => $employees,
        'Companies' => $companies
      ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateNotificationRequest $request)
    {
        DB::beginTransaction();
        try {
            $user = auth::user();
            $request->validated();
            $tenant_id = $user->tenant->id;
            $data = [
                'title' => $request->input('title'),
                'summary' => $request->input('summary'),
                'description' => $request->input('description'),
                'tenant_id' => $tenant_id,
                'companies_ids' => null,
                'employees_ids' => null
            ];
            if ($request->input('companies_ids') != null) {
                $companies_ids = $request->input('companies_ids', []);
                $companies_ids = explode(',', $companies_ids);
                $data['companies_ids'] = $companies_ids;
            }

            if ($request->input('employees_ids') != null) {
                $employees_ids = $request->input('employees_ids', []);
                $employees_ids = explode(',', $employees_ids);
                $data['employees_ids'] = $employees_ids;
            }

            event(new NotifyUser($data));

            DB::commit();
            return response()->json([
                'message' => "Success! The notification has been sent.",
                'data' => $data
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
    public function show(string $id)
    {


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
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
