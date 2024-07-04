<?php

namespace App\Services\Company\Adminland\Company;

use App\Jobs\LogAccountAudit;
use App\Models\Company\Company;
use App\Services\BaseService;
use Carbon\Carbon;

class UpdateCompanyCurrency extends BaseService
{
    protected Company $company;

    protected array $data;

    /**
     * Get the validation rules that apply to the service.
     */
    public function rules(): array
    {
        return [
            'company_id' => 'required|integer|exists:companies,id',
            'author_id' => 'required|integer|exists:employees,id',
            'currency' => 'required|string|max:255',
        ];
    }

    /**
     * Update the company's company.
     */
    public function execute(array $data): Company
    {
        $this->validateRules($data);

        $this->author($data['author_id'])
            ->inCompany($data['company_id'])
            ->asAtLeastAdministrator()
            ->canExecuteService();

        $this->data = $data;

        $this->company = Company::find($data['company_id']);
        $oldCurrency = $this->company->currency;

        $this->updateCurrency();

        $this->log($oldCurrency);

        return $this->company;
    }

    /**
     * Update the currency.
     */
    private function updateCurrency(): void
    {
        Company::where('id', $this->company->id)->update([
            'currency' => $this->data['currency'],
        ]);
    }

    /**
     * Add an audit log entry for this action.
     */
    private function log(string $oldCurrency): void
    {
        LogAccountAudit::dispatch([
            'company_id' => $this->company->id,
            'action' => 'company_currency_updated',
            'author_id' => $this->author->id,
            'author_name' => $this->author->name,
            'audited_at' => Carbon::now(),
            'objects' => json_encode([
                'old_currency' => $oldCurrency,
                'new_currency' => $this->data['currency'],
            ]),
        ])->onQueue('low');
    }
}
