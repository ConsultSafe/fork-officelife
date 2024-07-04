<?php

namespace Tests\Unit\Models\Company;

use App\Models\Company\RateYourManagerAnswer;
use App\Models\Company\RateYourManagerSurvey;
use Tests\TestCase;

class RateYourManagerSurveyTest extends TestCase
{
    /** @test */
    public function it_belongs_to_a_manager(): void
    {
        $entry = RateYourManagerSurvey::factory()->create([]);
        $this->assertTrue($entry->manager()->exists());
    }

    /** @test */
    public function it_has_many_answers(): void
    {
        $entry = RateYourManagerSurvey::factory()->create([]);
        RateYourManagerAnswer::factory()->count(2)->create([
            'rate_your_manager_survey_id' => $entry->id,
        ]);

        $this->assertTrue($entry->answers()->exists());
    }
}
