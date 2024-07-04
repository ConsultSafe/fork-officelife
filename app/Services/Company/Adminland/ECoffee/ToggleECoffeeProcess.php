<?php

namespace App\Services\Company\Adminland\ECoffee;

use App\Jobs\LogAccountAudit;
use App\Models\Company\Company;
use App\Services\BaseService;
use Carbon\Carbon;

class ToggleECoffeeProcess extends BaseService
{
    /**
     * Get the validation rules that apply to the service.
     */
    public function rules(): array
    {
        return [
            'company_id' => 'required|integer|exists:companies,id',
            'author_id' => 'required|integer|exists:employees,id',
        ];
    }

    /**
     * Toggle the e-coffee process in the company.
     */
    public function execute(array $data): Company
    {
        $this->validateRules($data);

        $this->author($data['author_id'])
            ->inCompany($data['company_id'])
            ->asAtLeastHR()
            ->canExecuteService();

        $company = Company::findOrFail($data['company_id']);

        $company->e_coffee_enabled = ! $company->e_coffee_enabled;
        $company->save();
        $company->refresh();

        LogAccountAudit::dispatch([
            'company_id' => $data['company_id'],
            'action' => 'toggle_e_coffee_process',
            'author_id' => $this->author->id,
            'author_name' => $this->author->name,
            'audited_at' => Carbon::now(),
            'objects' => json_encode([]),
        ])->onQueue('low');

        return $company;
    }
}
