<?php

namespace App\Http\Controllers\Company\Company\Project;

use App\Helpers\InstanceHelper;
use App\Http\Controllers\Controller;
use App\Services\Company\Project\ToggleProjectSprint;
use App\Services\Company\Project\UpdateProjectIssuePosition;
use Illuminate\Http\Request;

class ProjectSprintController extends Controller
{
    /**
     * Store the position of the issues in the sprint.
     */
    public function storePosition(Request $request, int $companyId, int $projectId, int $boardId, int $sprintId, int $storyId)
    {
        $loggedEmployee = InstanceHelper::getLoggedEmployee();
        $loggedCompany = InstanceHelper::getLoggedCompany();

        $data = [
            'company_id' => $loggedCompany->id,
            'author_id' => $loggedEmployee->id,
            'project_id' => $projectId,
            'project_issue_id' => $storyId,
            'project_sprint_id' => $sprintId,
            'new_position' => $request->input('position'),
        ];

        (new UpdateProjectIssuePosition)->execute($data);

        return response()->json([
            'data' => true,
        ], 200);
    }

    /**
     * Collapse or expand the sprint for the user.
     */
    public function toggle(Request $request, int $companyId, int $projectId, int $boardId, int $sprintId)
    {
        $loggedEmployee = InstanceHelper::getLoggedEmployee();
        $loggedCompany = InstanceHelper::getLoggedCompany();

        $data = [
            'company_id' => $loggedCompany->id,
            'author_id' => $loggedEmployee->id,
            'project_id' => $projectId,
            'project_sprint_id' => $sprintId,
        ];

        (new ToggleProjectSprint)->execute($data);

        return response()->json([
            'data' => true,
        ], 200);
    }
}
