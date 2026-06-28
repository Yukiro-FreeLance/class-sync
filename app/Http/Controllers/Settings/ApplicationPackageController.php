<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Services\Deployment\ApplicationPackageService;
use App\Services\Users\SuperAdminService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ApplicationPackageController extends Controller
{
    public function download(Request $request, string $filename, ApplicationPackageService $packages, SuperAdminService $superAdmin): BinaryFileResponse
    {
        if (! $superAdmin->is($request->user())) {
            abort(403);
        }

        return $packages->downloadResponse($filename);
    }
}
