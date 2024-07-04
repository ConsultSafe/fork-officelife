<?php

namespace App\Http\Controllers\Company\Dashboard\Me;

use App\Helpers\InstanceHelper;
use App\Http\Controllers\Controller;
use App\Services\Company\Employee\ECoffee\MarkECoffeeSessionAsHappened;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardMeECoffeeController extends Controller
{
    /**
     * Mark an e-coffee match as happened.
     */
    public function store(Request $request, int $companyId, int $eCoffeeId, int $eCoffeeMatchId): JsonResponse
    {
        $company = InstanceHelper::getLoggedCompany();
        $employee = InstanceHelper::getLoggedEmployee();

        $data = [
            'company_id' => $company->id,
            'author_id' => $employee->id,
            'e_coffee_id' => $eCoffeeId,
            'e_coffee_match_id' => $eCoffeeMatchId,
        ];

        (new MarkECoffeeSessionAsHappened)->execute($data);

        return response()->json([
            'data' => true,
        ], 200);
    }
}
