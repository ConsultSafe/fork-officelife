<?php

namespace Tests\Unit\Services\Company\Adminland\Expense;

use App\Exceptions\NotEnoughPermissionException;
use App\Jobs\LogAccountAudit;
use App\Jobs\LogEmployeeAudit;
use App\Jobs\NotifyEmployee;
use App\Models\Company\Employee;
use App\Services\Company\Adminland\Expense\AllowEmployeeToManageExpenses;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AllowEmployeeToManageExpensesTest extends TestCase
{
    /** @test */
    public function it_allows_an_employee_to_manage_expenses_as_administrator(): void
    {
        $michael = $this->createAdministrator();
        $dwight = $this->createAnotherEmployee($michael);
        $this->executeService($michael, $dwight);
    }

    /** @test */
    public function it_allows_an_employee_to_manage_expenses_as_hr(): void
    {
        $michael = $this->createHR();
        $dwight = $this->createAnotherEmployee($michael);
        $this->executeService($michael, $dwight);
    }

    /** @test */
    public function normal_user_cant_execute_the_service(): void
    {
        $michael = $this->createEmployee();
        $dwight = $this->createAnotherEmployee($michael);

        $this->expectException(NotEnoughPermissionException::class);
        $this->executeService($michael, $dwight);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'name' => 'travel',
        ];

        $this->expectException(ValidationException::class);
        (new AllowEmployeeToManageExpenses)->execute($request);
    }

    private function executeService(Employee $michael, Employee $dwight): void
    {
        Queue::fake();
        $request = [
            'company_id' => $michael->company_id,
            'author_id' => $michael->id,
            'employee_id' => $dwight->id,
        ];

        $employee = (new AllowEmployeeToManageExpenses)->execute($request);

        $this->assertDatabaseHas('employees', [
            'id' => $dwight->id,
            'can_manage_expenses' => true,
        ]);

        $this->assertInstanceOf(
            Employee::class,
            $employee
        );

        Queue::assertPushed(LogAccountAudit::class, function ($job) use ($michael, $dwight) {
            return $job->auditLog['action'] === 'employee_allowed_to_manage_expenses' &&
                $job->auditLog['author_id'] === $michael->id &&
                $job->auditLog['objects'] === json_encode([
                    'employee_id' => $dwight->id,
                    'employee_name' => $dwight->name,
                ]);
        });

        Queue::assertPushed(LogEmployeeAudit::class, function ($job) use ($michael) {
            return $job->auditLog['action'] === 'employee_allowed_to_manage_expenses' &&
                $job->auditLog['author_id'] === $michael->id &&
                $job->auditLog['objects'] === json_encode([]);
        });

        Queue::assertPushed(NotifyEmployee::class, function ($job) use ($dwight) {
            return $job->notification['action'] === 'employee_allowed_to_manage_expenses' &&
                $job->notification['employee_id'] === $dwight->id &&
                $job->notification['objects'] === json_encode([]);
        });
    }
}
