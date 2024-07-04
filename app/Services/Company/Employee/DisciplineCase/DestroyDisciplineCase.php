<?php

namespace App\Services\Company\Employee\DisciplineCase;

use App\Models\Company\DisciplineCase;
use App\Services\BaseService;

class DestroyDisciplineCase extends BaseService
{
    /**
     * Get the validation rules that apply to the service.
     */
    public function rules(): array
    {
        return [
            'company_id' => 'required|integer|exists:companies,id',
            'author_id' => 'required|integer|exists:employees,id',
            'discipline_case_id' => 'required|integer|exists:discipline_cases,id',
        ];
    }

    /**
     * Destroy a discipline case.
     */
    public function execute(array $data): bool
    {
        $this->validateRules($data);

        $this->author($data['author_id'])
            ->inCompany($data['company_id'])
            ->asAtLeastHR()
            ->canExecuteService();

        $case = DisciplineCase::where('company_id', $data['company_id'])
            ->findOrFail($data['discipline_case_id']);

        $case->delete();

        return true;
    }
}
