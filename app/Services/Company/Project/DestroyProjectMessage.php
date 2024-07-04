<?php

namespace App\Services\Company\Project;

use App\Jobs\LogAccountAudit;
use App\Models\Company\Project;
use App\Models\Company\ProjectMemberActivity;
use App\Models\Company\ProjectMessage;
use App\Services\BaseService;
use Carbon\Carbon;

class DestroyProjectMessage extends BaseService
{
    protected array $data;

    protected ProjectMessage $projectMessage;

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
            'project_message_id' => 'required|integer|exists:project_messages,id',
        ];
    }

    /**
     * Destroy a project message.
     */
    public function execute(array $data): void
    {
        $this->data = $data;
        $this->validate();
        $this->destroyMessage();
        $this->logActivity();
        $this->log();
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

        $this->projectMessage = ProjectMessage::where('project_id', $this->data['project_id'])
            ->findOrFail($this->data['project_message_id']);
    }

    private function destroyMessage(): void
    {
        $this->projectMessage->delete();
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
            'action' => 'project_message_destroyed',
            'author_id' => $this->author->id,
            'author_name' => $this->author->name,
            'audited_at' => Carbon::now(),
            'objects' => json_encode([
                'project_id' => $this->project->id,
                'project_name' => $this->project->name,
                'title' => $this->projectMessage->title,
            ]),
        ])->onQueue('low');
    }
}
