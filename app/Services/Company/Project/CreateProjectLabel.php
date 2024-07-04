<?php

namespace App\Services\Company\Project;

use App\Exceptions\LabelAlreadyExistException;
use App\Jobs\LogAccountAudit;
use App\Models\Company\Project;
use App\Models\Company\ProjectLabel;
use App\Models\Company\ProjectMemberActivity;
use App\Services\BaseService;
use Carbon\Carbon;

class CreateProjectLabel extends BaseService
{
    protected array $data;

    protected Project $project;

    protected ProjectLabel $projectLabel;

    /**
     * Get the validation rules that apply to the service.
     */
    public function rules(): array
    {
        return [
            'company_id' => 'required|integer|exists:companies,id',
            'author_id' => 'required|integer|exists:employees,id',
            'project_id' => 'required|integer|exists:projects,id',
            'name' => 'required|string|max:255',
        ];
    }

    /**
     * Create a project label.
     */
    public function execute(array $data): ProjectLabel
    {
        $this->data = $data;
        $this->validate();
        $this->verifyLabelUniqueness();
        $this->createLabel();
        $this->logActivity();
        $this->log();

        return $this->projectLabel;
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
    }

    private function verifyLabelUniqueness(): void
    {
        $alreadyExists = ProjectLabel::where('project_id', $this->data['project_id'])
            ->where('name', $this->data['name'])
            ->exists();

        if ($alreadyExists) {
            throw new LabelAlreadyExistException();
        }
    }

    private function createLabel(): void
    {
        $this->projectLabel = ProjectLabel::create([
            'project_id' => $this->data['project_id'],
            'name' => $this->data['name'],
        ]);
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
            'action' => 'project_label_created',
            'author_id' => $this->author->id,
            'author_name' => $this->author->name,
            'audited_at' => Carbon::now(),
            'objects' => json_encode([
                'project_id' => $this->project->id,
                'project_name' => $this->project->name,
                'name' => $this->projectLabel->name,
            ]),
        ])->onQueue('low');
    }
}
