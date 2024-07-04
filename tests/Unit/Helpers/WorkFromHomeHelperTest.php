<?php

namespace Tests\Unit\Helpers;

use App\Helpers\WorkFromHomeHelper;
use App\Models\Company\WorkFromHome;
use Carbon\Carbon;
use Tests\TestCase;

class WorkFromHomeHelperTest extends TestCase
{
    /** @test */
    public function it_indicates_that_an_employee_worked_from_home(): void
    {
        $date = Carbon::createFromDate(2010, 10, 10);
        $dwight = $this->createAdministrator();
        WorkFromHome::factory()->create([
            'employee_id' => $dwight->id,
            'date' => '2010-10-10 00:00:00',
        ]);

        $this->assertTrue(WorkFromHomeHelper::hasWorkedFromHomeOnDate($dwight, $date));
    }

    /** @test */
    public function it_indicates_that_an_employee_didnt_work_from_home(): void
    {
        $date = Carbon::createFromDate(2010, 10, 10);
        $dwight = $this->createAdministrator();

        $this->assertFalse(WorkFromHomeHelper::hasWorkedFromHomeOnDate($dwight, $date));
    }
}
