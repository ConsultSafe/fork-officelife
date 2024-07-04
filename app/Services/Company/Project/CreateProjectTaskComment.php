<?php

namespace App\Services\Company\Project;

use App\Jobs\LogAccountAudit;
use App\Models\Company\Comment;
use App\Models\Company\Project;
use App\Models\Company\ProjectMemberActivity;
use App\Models\Company\ProjectTask;
use App\Services\BaseService;
use Carbon\Carbon;

class CreateProjectTaskComment extends BaseService
{
    protected array $data;

    protected ProjectTask $projectTask;

    protected Project $project;

    protected Comment $comment;

    /**
     * Get the validation rules that apply to the service.
     */
    public function rules(): array
    {
        return [
            'company_id' => 'required|integer|exists:companies,id',
            'author_id' => 'required|integer|exists:employees,id',
            'project_id' => 'required|integer|exists:projects,id',
            'project_task_id' => 'required|integer|exists:project_tasks,id',
            'content' => 'required|string|max:65535',
        ];
    }

    /**
     * Create a comment in the given project task.
     */
    public function execute(array $data): Comment
    {
        $this->data = $data;
        $this->validate();
        $this->createComment();
        $this->logActivity();
        $this->log();

        return $this->comment;
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

        $this->projectTask = ProjectTask::where('project_id', $this->data['project_id'])
            ->findOrFail($this->data['project_task_id']);
    }

    private function createComment(): void
    {
        $this->comment = Comment::create([
            'company_id' => $this->data['company_id'],
            'author_id' => $this->data['author_id'],
            'author_name' => $this->author->name,
            'content' => $this->data['content'],
        ]);

        $this->projectTask->comments()->save($this->comment);
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
            'action' => 'project_task_comment_created',
            'author_id' => $this->author->id,
            'author_name' => $this->author->name,
            'audited_at' => Carbon::now(),
            'objects' => json_encode([
                'project_id' => $this->project->id,
                'project_name' => $this->project->name,
            ]),
        ])->onQueue('low');
    }
}
