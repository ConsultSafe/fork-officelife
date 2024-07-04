<?php

namespace App\Services\Company\Project;

use App\Jobs\LogAccountAudit;
use App\Models\Company\Project;
use App\Models\Company\ProjectIssue;
use App\Models\Company\ProjectMemberActivity;
use App\Models\Company\ProjectSprint;
use App\Services\BaseService;
use Carbon\Carbon;

class AssignIssueToSprint extends BaseService
{
    protected array $data;

    protected ProjectIssue $projectIssue;

    protected ProjectSprint $projectSprint;

    protected Project $project;

    /**
     * Get the validation rules that apply to the service.
     */
    public function rules(): array
    {
        return [
            'company_id' => 'required|integer|exists:companies,id',
            'author_id' => 'required|integer|exists:employees,id',
            'project_id' => 'required|integer|exists:projects,id',
            'project_sprint_id' => 'required|integer|exists:project_sprints,id',
            'project_issue_id' => 'required|integer|exists:project_issues,id',
        ];
    }

    /**
     * Assign a project issue to a sprint.
     */
    public function execute(array $data): ProjectIssue
    {
        $this->data = $data;
        $this->validate();
        $this->assign();
        $this->logActivity();
        $this->log();

        return $this->projectIssue;
    }

    private function validate(): void
    {
        $this->validateRules($this->data);

        $this->author($this->data['author_id'])
            ->inCompany($this->data['company_id'])
            ->asNormalUser()
            ->canExecuteService();

        $this->project = Project::where('company_id', $this->data['company_id'])
            ->findOrFail($this->data['project_id']);

        $this->projectSprint = ProjectSprint::where('project_id', $this->data['project_id'])
            ->findOrFail($this->data['project_sprint_id']);

        $this->projectIssue = ProjectIssue::where('project_id', $this->data['project_id'])
            ->findOrFail($this->data['project_issue_id']);
    }

    private function assign(): void
    {
        $this->projectIssue->sprints()->syncWithoutDetaching([$this->projectSprint->id]);
    }

    private function logActivity(): void
    {
        ProjectMemberActivity::create([
            'project_id' => $this->project->id,
            'employee_id' => $this->author->id,
        ]);
    }

    private function log(): void
    {
        LogAccountAudit::dispatch([
            'company_id' => $this->data['company_id'],
            'action' => 'project_issue_assigned_to_sprint',
            'author_id' => $this->author->id,
            'author_name' => $this->author->name,
            'audited_at' => Carbon::now(),
            'objects' => json_encode([
                'project_id' => $this->project->id,
                'project_name' => $this->project->name,
                'title' => $this->projectIssue->title,
                'sprint_name' => $this->projectSprint->name,
            ]),
        ])->onQueue('low');
    }
}
