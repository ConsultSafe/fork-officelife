<?php

namespace App\Services\Company\Project;

use App\Jobs\LogAccountAudit;
use App\Models\Company\Comment;
use App\Models\Company\Project;
use App\Models\Company\ProjectIssue;
use App\Models\Company\ProjectMemberActivity;
use App\Services\BaseService;
use Carbon\Carbon;

class DestroyProjectIssueComment extends BaseService
{
    protected array $data;

    protected ProjectIssue $projectIssue;

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
            'project_issue_id' => 'required|integer|exists:project_issues,id',
            'comment_id' => 'required|integer|exists:comments,id',
        ];
    }

    /**
     * Delete a comment.
     */
    public function execute(array $data): Comment
    {
        $this->data = $data;
        $this->validate();
        $this->destroyComment();
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

        $this->projectIssue = ProjectIssue::where('project_id', $this->data['project_id'])
            ->findOrFail($this->data['project_issue_id']);

        $this->comment = Comment::where('company_id', $this->data['company_id'])
            ->where('commentable_type', 'App\Models\Company\ProjectIssue')
            ->where('commentable_id', $this->data['project_issue_id'])
            ->findOrFail($this->data['comment_id']);
    }

    private function destroyComment(): void
    {
        $this->comment->delete();
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
            'action' => 'project_issue_comment_destroyed',
            'author_id' => $this->author->id,
            'author_name' => $this->author->name,
            'audited_at' => Carbon::now(),
            'objects' => json_encode([
                'project_id' => $this->project->id,
                'project_name' => $this->project->name,
                'project_issue_id' => $this->projectIssue->id,
                'project_issue_title' => $this->projectIssue->title,
            ]),
        ])->onQueue('low');
    }
}
