<?php

namespace Tests\Unit\Services\Company\Project;

use App\Jobs\LogAccountAudit;
use App\Models\Company\Employee;
use App\Models\Company\Project;
use App\Models\Company\ProjectSprint;
use App\Services\Company\Project\StartProjectSprint;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class StartProjectSprintTest extends TestCase
{
    /** @test */
    public function it_starts_the_project_sprint_as_administrator(): void
    {
        $michael = $this->createAdministrator();
        $project = Project::factory()->create([
            'company_id' => $michael->company_id,
        ]);
        $projectSprint = ProjectSprint::factory()->create([
            'project_id' => $project->id,
        ]);
        $this->executeService($michael, $project, $projectSprint);
    }

    /** @test */
    public function it_starts_the_project_sprint_as_hr(): void
    {
        $michael = $this->createHR();
        $project = Project::factory()->create([
            'company_id' => $michael->company_id,
        ]);
        $projectSprint = ProjectSprint::factory()->create([
            'project_id' => $project->id,
        ]);
        $this->executeService($michael, $project, $projectSprint);
    }

    /** @test */
    public function it_starts_the_project_sprint_as_normal_user(): void
    {
        $michael = $this->createEmployee();
        $project = Project::factory()->create([
            'company_id' => $michael->company_id,
        ]);
        $projectSprint = ProjectSprint::factory()->create([
            'project_id' => $project->id,
        ]);
        $this->executeService($michael, $project, $projectSprint);
    }

    /** @test */
    public function it_fails_if_project_is_not_part_of_the_company(): void
    {
        $michael = Employee::factory()->create();
        $project = Project::factory()->create();

        $projectSprint = ProjectSprint::factory()->create([
            'project_id' => $project->id,
        ]);
        $this->expectException(ModelNotFoundException::class);
        $this->executeService($michael, $project, $projectSprint);
    }

    /** @test */
    public function it_fails_if_project_sprint_is_not_part_of_the_project(): void
    {
        $michael = Employee::factory()->create();
        $project = Project::factory()->create();
        $projectSprint = ProjectSprint::factory()->create([]);

        $this->expectException(ModelNotFoundException::class);
        $this->executeService($michael, $project, $projectSprint);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $michael = Employee::factory()->create();

        $request = [
            'company_id' => $michael->company_id,
        ];

        $this->expectException(ValidationException::class);
        (new StartProjectSprint)->execute($request);
    }

    private function executeService(Employee $michael, Project $project, ProjectSprint $sprint): void
    {
        Queue::fake();
        Carbon::setTestNow(Carbon::create(2018, 1, 1));

        $request = [
            'company_id' => $michael->company_id,
            'author_id' => $michael->id,
            'project_id' => $project->id,
            'project_sprint_id' => $sprint->id,
        ];

        $sprint = (new StartProjectSprint)->execute($request);

        $this->assertDatabaseHas('project_sprints', [
            'id' => $sprint->id,
            'active' => true,
            'started_at' => '2018-01-01 00:00:00',
        ]);

        $this->assertDatabaseHas('project_member_activities', [
            'project_id' => $project->id,
            'employee_id' => $michael->id,
        ]);

        Queue::assertPushed(LogAccountAudit::class, function ($job) use ($michael, $project, $sprint) {
            return $job->auditLog['action'] === 'project_sprint_started' &&
                $job->auditLog['author_id'] === $michael->id &&
                $job->auditLog['objects'] === json_encode([
                    'project_id' => $project->id,
                    'project_name' => $project->name,
                    'project_sprint_id' => $sprint->id,
                    'project_sprint_name' => $sprint->name,
                ]);
        });
    }
}
