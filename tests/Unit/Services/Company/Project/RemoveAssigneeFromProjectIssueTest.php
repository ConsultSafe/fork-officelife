<?php

namespace Tests\Unit\Services\Company\Project;

use App\Jobs\LogAccountAudit;
use App\Models\Company\Employee;
use App\Models\Company\Project;
use App\Models\Company\ProjectIssue;
use App\Services\Company\Project\RemoveAssigneeFromProjectIssue;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class RemoveAssigneeFromProjectIssueTest extends TestCase
{
    /** @test */
    public function it_unassigns_the_issue_to_an_employeee_as_administrator(): void
    {
        $michael = $this->createAdministrator();
        $project = Project::factory()->create([
            'company_id' => $michael->company_id,
        ]);
        $projectIssue = ProjectIssue::factory()->create([
            'project_id' => $project->id,
        ]);
        $employee = Employee::factory()->create([
            'company_id' => $michael->company_id,
        ]);
        $projectIssue->assignees()->syncWithoutDetaching([$employee->id]);
        $this->executeService($michael, $project, $employee, $projectIssue);
    }

    /** @test */
    public function it_unassigns_the_issue_to_an_employeee_as_hr(): void
    {
        $michael = $this->createHR();
        $project = Project::factory()->create([
            'company_id' => $michael->company_id,
        ]);
        $projectIssue = ProjectIssue::factory()->create([
            'project_id' => $project->id,
        ]);
        $employee = Employee::factory()->create([
            'company_id' => $michael->company_id,
        ]);
        $projectIssue->assignees()->syncWithoutDetaching([$employee->id]);
        $this->executeService($michael, $project, $employee, $projectIssue);
    }

    /** @test */
    public function it_unassigns_the_issue_to_an_employeee_as_normal_user(): void
    {
        $michael = $this->createEmployee();
        $project = Project::factory()->create([
            'company_id' => $michael->company_id,
        ]);
        $projectIssue = ProjectIssue::factory()->create([
            'project_id' => $project->id,
        ]);
        $employee = Employee::factory()->create([
            'company_id' => $michael->company_id,
        ]);
        $projectIssue->assignees()->syncWithoutDetaching([$employee->id]);
        $this->executeService($michael, $project, $employee, $projectIssue);
    }

    /** @test */
    public function it_fails_if_project_is_not_part_of_the_company(): void
    {
        $michael = Employee::factory()->create();
        $project = Project::factory()->create();
        $projectIssue = ProjectIssue::factory()->create([
            'project_id' => $project->id,
        ]);
        $employee = Employee::factory()->create([
            'company_id' => $michael->company_id,
        ]);
        $projectIssue->assignees()->syncWithoutDetaching([$employee->id]);
        $this->expectException(ModelNotFoundException::class);
        $this->executeService($michael, $project, $employee, $projectIssue);
    }

    /** @test */
    public function it_fails_if_project_issue_is_not_part_of_the_project(): void
    {
        $michael = Employee::factory()->create();
        $project = Project::factory()->create([
            'company_id' => $michael->company_id,
        ]);
        $projectIssue = ProjectIssue::factory()->create([]);
        $employee = Employee::factory()->create([
            'company_id' => $michael->company_id,
        ]);
        $projectIssue->assignees()->syncWithoutDetaching([$employee->id]);

        $this->expectException(ModelNotFoundException::class);
        $this->executeService($michael, $project, $employee, $projectIssue);
    }

    /** @test */
    public function it_fails_if_employee_is_not_part_of_the_company(): void
    {
        $michael = Employee::factory()->create();
        $project = Project::factory()->create([
            'company_id' => $michael->company_id,
        ]);
        $projectIssue = ProjectIssue::factory()->create([]);
        $employee = Employee::factory()->create();
        $projectIssue->assignees()->syncWithoutDetaching([$employee->id]);
        $this->expectException(ModelNotFoundException::class);
        $this->executeService($michael, $project, $employee, $projectIssue);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $michael = Employee::factory()->create();

        $request = [
            'company_id' => $michael->company_id,
        ];

        $this->expectException(ValidationException::class);
        (new RemoveAssigneeFromProjectIssue)->execute($request);
    }

    private function executeService(Employee $michael, Project $project, Employee $employee, ProjectIssue $issue): void
    {
        Queue::fake();

        $request = [
            'company_id' => $michael->company_id,
            'author_id' => $michael->id,
            'project_id' => $project->id,
            'project_issue_id' => $issue->id,
            'employee_id' => $employee->id,
        ];

        (new RemoveAssigneeFromProjectIssue)->execute($request);

        $this->assertDatabaseMissing('project_issue_assignees', [
            'project_issue_id' => $issue->id,
            'employee_id' => $employee->id,
        ]);

        $this->assertDatabaseHas('project_member_activities', [
            'project_id' => $project->id,
            'employee_id' => $michael->id,
        ]);

        Queue::assertPushed(LogAccountAudit::class, function ($job) use ($michael, $project, $issue, $employee) {
            return $job->auditLog['action'] === 'project_issue_unassigned_to_assignee' &&
                $job->auditLog['author_id'] === $michael->id &&
                $job->auditLog['objects'] === json_encode([
                    'project_id' => $project->id,
                    'project_name' => $project->name,
                    'employee_name' => $employee->name,
                    'issue_title' => $issue->title,
                ]);
        });
    }
}
