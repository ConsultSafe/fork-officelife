<?php

namespace Tests\Unit\Services\Company\Project;

use App\Jobs\LogAccountAudit;
use App\Models\Company\Comment;
use App\Models\Company\Employee;
use App\Models\Company\Project;
use App\Models\Company\ProjectTask;
use App\Services\Company\Project\DestroyProjectTaskComment;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DestroyProjectTaskCommentTest extends TestCase
{
    /** @test */
    public function it_deletes_a_comment_to_a_project_as_administrator(): void
    {
        $michael = $this->createAdministrator();
        $project = Project::factory()->create([
            'company_id' => $michael->company_id,
        ]);
        $projectTask = ProjectTask::factory()->create([
            'project_id' => $project->id,
        ]);
        $comment = Comment::factory()->create([
            'company_id' => $michael->company_id,
            'author_id' => $michael->id,
        ]);
        $projectTask->comments()->save($comment);
        $this->executeService($michael, $project, $projectTask, $comment);
    }

    /** @test */
    public function it_deletes_a_comment_to_a_project_as_hr(): void
    {
        $michael = $this->createHR();
        $project = Project::factory()->create([
            'company_id' => $michael->company_id,
        ]);
        $projectTask = ProjectTask::factory()->create([
            'project_id' => $project->id,
        ]);
        $comment = Comment::factory()->create([
            'company_id' => $michael->company_id,
            'author_id' => $michael->id,
        ]);
        $projectTask->comments()->save($comment);
        $this->executeService($michael, $project, $projectTask, $comment);
    }

    /** @test */
    public function it_deletes_a_comment_to_a_project_as_normal_user(): void
    {
        $michael = $this->createEmployee();
        $project = Project::factory()->create([
            'company_id' => $michael->company_id,
        ]);
        $projectTask = ProjectTask::factory()->create([
            'project_id' => $project->id,
        ]);
        $comment = Comment::factory()->create([
            'company_id' => $michael->company_id,
            'author_id' => $michael->id,
        ]);
        $projectTask->comments()->save($comment);
        $this->executeService($michael, $project, $projectTask, $comment);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'first_name' => 'Dwight',
        ];

        $this->expectException(ValidationException::class);
        (new DestroyProjectTaskComment)->execute($request);
    }

    /** @test */
    public function it_fails_if_the_project_is_not_in_the_company(): void
    {
        $michael = $this->createAdministrator();
        $project = Project::factory()->create();
        $projectTask = ProjectTask::factory()->create([
            'project_id' => $project->id,
        ]);
        $comment = Comment::factory()->create([
            'company_id' => $michael->company_id,
            'author_id' => $michael->id,
        ]);
        $projectTask->comments()->save($comment);

        $this->expectException(ModelNotFoundException::class);
        $this->executeService($michael, $project, $projectTask, $comment);
    }

    /** @test */
    public function it_fails_if_the_project_message_is_not_in_the_company(): void
    {
        $michael = $this->createEmployee();
        $project = Project::factory()->create([
            'company_id' => $michael->company_id,
        ]);
        $projectTask = ProjectTask::factory()->create();
        $comment = Comment::factory()->create([
            'company_id' => $michael->company_id,
            'author_id' => $michael->id,
        ]);
        $projectTask->comments()->save($comment);

        $this->expectException(ModelNotFoundException::class);
        $this->executeService($michael, $project, $projectTask, $comment);
    }

    /** @test */
    public function it_fails_if_the_comment_is_not_in_the_project_message(): void
    {
        $michael = $this->createEmployee();
        $project = Project::factory()->create([
            'company_id' => $michael->company_id,
        ]);
        $projectTask = ProjectTask::factory()->create([
            'project_id' => $project->id,
        ]);
        $comment = Comment::factory()->create();

        $this->expectException(ModelNotFoundException::class);
        $this->executeService($michael, $project, $projectTask, $comment);
    }

    private function executeService(Employee $michael, Project $project, ProjectTask $projectTask, Comment $comment): void
    {
        Queue::fake();

        $request = [
            'company_id' => $michael->company_id,
            'author_id' => $michael->id,
            'project_id' => $project->id,
            'project_task_id' => $projectTask->id,
            'comment_id' => $comment->id,
        ];

        (new DestroyProjectTaskComment)->execute($request);

        $this->assertDatabaseMissing('comments', [
            'id' => $projectTask->id,
            'author_id' => $michael->id,
            'commentable_id' => $projectTask->id,
            'content' => 'content',
        ]);

        $this->assertDatabaseHas('project_member_activities', [
            'project_id' => $project->id,
            'employee_id' => $michael->id,
        ]);

        Queue::assertPushed(LogAccountAudit::class, function ($job) use ($michael, $project) {
            return $job->auditLog['action'] === 'project_task_comment_destroyed' &&
                $job->auditLog['author_id'] === $michael->id &&
                $job->auditLog['objects'] === json_encode([
                    'project_id' => $project->id,
                    'project_name' => $project->name,
                ]);
        });
    }
}
