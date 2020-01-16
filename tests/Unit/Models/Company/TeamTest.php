<?php

namespace Tests\Unit\Models\Company;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\Company\Task;
use App\Models\Company\Team;
use App\Models\Company\Company;
use App\Models\Company\Worklog;
use App\Models\Company\Employee;
use App\Models\Company\TeamNews;
use App\Models\Company\TeamUsefulLink;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class TeamTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_belongs_to_company()
    {
        $team = factory(Team::class)->create([]);
        $this->assertTrue($team->company()->exists());
    }

    /** @test */
    public function it_has_many_employees()
    {
        $team = factory(Team::class)->create([]);
        $dwight = factory(Employee::class)->create([
            'company_id' => $team->company_id,
        ]);
        $michael = factory(Employee::class)->create([
            'company_id' => $team->company_id,
        ]);

        $team->employees()->syncWithoutDetaching([$dwight->id => ['company_id' => $team->company_id]]);
        $team->employees()->syncWithoutDetaching([$michael->id => ['company_id' => $team->company_id]]);

        $this->assertTrue($team->employees()->exists());
    }

    /** @test */
    public function it_has_a_leader()
    {
        $team = factory(Team::class)->create([]);
        $this->assertTrue($team->leader()->exists());
    }

    /** @test */
    public function it_has_many_tasks()
    {
        $team = factory(Team::class)->create([]);
        factory(Task::class, 2)->create([
            'team_id' => $team->id,
        ]);

        $this->assertTrue($team->tasks()->exists());
    }

    /** @test */
    public function it_has_many_links()
    {
        $team = factory(Team::class)->create([]);
        factory(TeamUsefulLink::class, 2)->create([
            'team_id' => $team->id,
        ]);

        $this->assertTrue($team->links()->exists());
    }

    /** @test */
    public function it_has_many_news()
    {
        $team = factory(Team::class)->create([]);
        factory(TeamNews::class, 2)->create([
            'team_id' => $team->id,
        ]);

        $this->assertTrue($team->news()->exists());
    }

    /** @test */
    public function it_returns_an_object(): void
    {
        $dunder = factory(Company::class)->create([]);
        $team = factory(Team::class)->create([
            'company_id' => $dunder->id,
            'name' => 'sales',
            'description' => 'this is a description',
            'created_at' => '2020-01-12 00:00:00',
        ]);

        $this->assertEquals(
            [
                'id' => $team->id,
                'company' => [
                    'id' => $dunder->id,
                ],
                'name' => 'sales',
                'raw_description' => 'this is a description',
                'parsed_description' => '<p>this is a description</p>',
                'created_at' => '2020-01-12 00:00:00',
            ],
            $team->toObject()
        );

        $team = factory(Team::class)->create([
            'company_id' => $dunder->id,
            'name' => 'sales',
            'description' => null,
            'created_at' => '2020-01-12 00:00:00',
        ]);

        $this->assertEquals(
            [
                'id' => $team->id,
                'company' => [
                    'id' => $dunder->id,
                ],
                'name' => 'sales',
                'raw_description' => null,
                'parsed_description' => null,
                'created_at' => '2020-01-12 00:00:00',
            ],
            $team->toObject()
        );
    }

    /** @test */
    public function it_returns_the_number_of_team_members_who_have_completed_a_worklog_at_a_given_time()
    {
        $date = Carbon::now();
        $team = factory(Team::class)->create([]);
        $dwight = factory(Employee::class)->create([
            'company_id' => $team->company_id,
        ]);
        $michael = factory(Employee::class)->create([
            'company_id' => $team->company_id,
        ]);

        $team->employees()->syncWithoutDetaching([$dwight->id => ['company_id' => $team->company_id]]);
        $team->employees()->syncWithoutDetaching([$michael->id => ['company_id' => $team->company_id]]);

        factory(Worklog::class)->create([
            'employee_id' => $dwight->id,
            'created_at' => $date,
        ]);
        factory(Worklog::class)->create([
            'employee_id' => $michael->id,
            'created_at' => $date,
        ]);

        $this->assertEquals(
            2,
            count($team->worklogsForDate($date))
        );
    }
}
