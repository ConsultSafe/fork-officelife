<?php

namespace Tests\Unit\Models\Company;

use App\Models\Company\CompanyDailyUsageHistory;
use App\Models\Company\CompanyUsageHistoryDetails;
use Tests\TestCase;

class CompanyDailyUsageHistoryTest extends TestCase
{
    /** @test */
    public function it_belongs_to_a_company(): void
    {
        $usage = CompanyDailyUsageHistory::factory()->create([]);
        $this->assertTrue($usage->company()->exists());
    }

    /** @test */
    public function it_has_many_details(): void
    {
        $usage = CompanyDailyUsageHistory::factory()->create([]);
        CompanyUsageHistoryDetails::factory()->count(2)->create([
            'usage_history_id' => $usage->id,
        ]);

        $this->assertTrue($usage->details()->exists());
    }
}
