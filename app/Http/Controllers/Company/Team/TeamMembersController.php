<?php

namespace App\Http\Controllers\Company\Team;

use App\Helpers\InstanceHelper;
use App\Http\Controllers\Controller;
use App\Http\ViewHelpers\Team\TeamMembersViewHelper;
use App\Models\Company\Employee;
use App\Models\Company\Team;
use App\Services\Company\Employee\Team\AddEmployeeToTeam;
use App\Services\Company\Employee\Team\RemoveEmployeeFromTeam;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TeamMembersController extends Controller
{
    /**
     * Search members that can be added to the team.
     */
    public function index(Request $request, int $companyId, int $teamId): JsonResponse
    {
        $company = InstanceHelper::getLoggedCompany();

        try {
            $team = Team::where('company_id', $company->id)
                ->findOrFail($teamId);
        } catch (ModelNotFoundException $e) {
            return redirect('home');
        }

        $criteria = $request->input('searchTerm');
        $employees = TeamMembersViewHelper::searchPotentialTeamMembers($company, $team, $criteria);

        return response()->json([
            'data' => $employees,
        ], 200);
    }

    /**
     * Add the employee to the team.
     */
    public function attach(Request $request, int $companyId, int $teamId, int $employeeId): JsonResponse
    {
        $loggedEmployee = InstanceHelper::getLoggedEmployee();

        $data = [
            'company_id' => $companyId,
            'author_id' => $loggedEmployee->id,
            'team_id' => $teamId,
            'employee_id' => $employeeId,
        ];

        $employee = (new AddEmployeeToTeam)->execute($data);

        return response()->json([
            'data' => TeamMembersViewHelper::employee($employee),
        ], 200);
    }

    /**
     * Remove the employee from the team.
     */
    public function detach(Request $request, int $companyId, int $teamId, int $employeeId): JsonResponse
    {
        $loggedEmployee = InstanceHelper::getLoggedEmployee();

        $data = [
            'company_id' => $companyId,
            'author_id' => $loggedEmployee->id,
            'team_id' => $teamId,
            'employee_id' => $employeeId,
        ];

        $employee = (new RemoveEmployeeFromTeam)->execute($data);

        return response()->json([
            'data' => TeamMembersViewHelper::employee($employee),
        ], 200);
    }
}
