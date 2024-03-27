<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Media;
use App\Models\Tenant;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * @OA\Tag(
 *     name="Company Report",
 *     description="Handling the crud of Company Report in it."
 * )
 */
class TenantReportController extends Controller
{
    public function __construct()
    {
        // Apply middleware to all methods in the controller
        $this->middleware('checkPermission:company-report.list')->only('report', 'create');
    }

    public function index()
    {
    }

    /**
     * @OA\Get(
     *      path="/api/get-company-report/{id}",
     *      summary="Get the company report.Permission required = company-report.list",
     *      description="This endpoint retrieves information about something.",
     *      tags={"Company Report"},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="The id of the company against which you need the report",
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
    public function report(string $id)
    {
        DB::beginTransaction();

        try {
            $company = Company::find($id);

            //handling the logo of the company
            if (!is_null($company->logo_media_id)) {
                $logo_media = Media::find($company->logo_media_id);
                if ($logo_media) {
                    $logo_media_url = asset("storage/{$logo_media->media_path}");
                    $company->logo_media_id = $logo_media_url;
                }
            }

            //handling the logo of the company
            if (!is_null($company->document_media_id)) {
                $document_media = Media::find($company->document_media_id);
                if ($document_media) {
                    $document_media_url = asset("storage/{$document_media->media_path}");
                    $company->document_media_id = $document_media_url;
                }
            }
            $duties_count = $company->duties()->where('status', '1')->count();
            $duties = $company->duties()->where('status', '1')->get();
            if (!($duties_count > 0)) {
                return response()->json([
                    'Company' => $company,
                    'message' => 'No duties could be found for this company',
                ]);
            }
            foreach ($duties as $duty) {
                $duty->load('employee', 'policy', 'equipments', 'attendanceRoster');
            }
            DB::commit();

            return response()->json([
                'message' => 'The data fetched successfully',
                'company' => $company,
                'duties'  => $duties,

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
     *      path="/api/company-report/create",
     *      summary="Get All companies.Permission required = company-report.list",
     *      description="This endpoint retrieves all  companies related to this logged in tenant.",
     *      tags={"Company Report"},
     *
     *      @OA\Response(response="200", description="Successful operation"),
     *      @OA\Response(response="401", description="Unauthorized"),
     * )
     */
    public function create()
    {
        $user = auth::user();
        if ($user->tenant) {
            $tenant = $user->tenant;
            $companies = $tenant->companies()->where('status', '1')->get();

            return response()->json([
                'message'   => 'This is the list of the all the active companies related to this tenant',
                'Companies' => $companies,
            ]);
        }
    }
}
