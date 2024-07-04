<?php

namespace App\Services\Company\Wiki;

use App\Jobs\LogAccountAudit;
use App\Models\Company\Wiki;
use App\Services\BaseService;
use Carbon\Carbon;

class UpdateWiki extends BaseService
{
    protected array $data;

    protected Wiki $wiki;

    /**
     * Get the validation rules that apply to the service.
     */
    public function rules(): array
    {
        return [
            'company_id' => 'required|integer|exists:companies,id',
            'author_id' => 'required|integer|exists:employees,id',
            'wiki_id' => 'required|integer|exists:wikis,id',
            'title' => 'required|string|max:255',
        ];
    }

    /**
     * Update a wiki.
     */
    public function execute(array $data): Wiki
    {
        $this->data = $data;
        $this->validate();
        $this->update();
        $this->log();

        return $this->wiki;
    }

    private function validate(): void
    {
        $this->validateRules($this->data);

        $this->author($this->data['author_id'])
            ->inCompany($this->data['company_id'])
            ->asNormalUser()
            ->canExecuteService();

        $this->wiki = Wiki::where('company_id', $this->data['company_id'])
            ->findOrFail($this->data['wiki_id']);
    }

    private function update(): void
    {
        $this->wiki->title = $this->data['title'];
        $this->wiki->save();
        $this->wiki->refresh();
    }

    private function log(): void
    {
        LogAccountAudit::dispatch([
            'company_id' => $this->data['company_id'],
            'action' => 'wiki_updated',
            'author_id' => $this->author->id,
            'author_name' => $this->author->name,
            'audited_at' => Carbon::now(),
            'objects' => json_encode([
                'wiki_title' => $this->wiki->title,
            ]),
        ])->onQueue('low');
    }
}
