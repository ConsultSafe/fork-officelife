<?php

namespace Tests\Unit\Models\Company;

use App\Models\Company\JobOpening;
use App\Models\Company\RecruitingStage;
use App\Models\Company\RecruitingStageTemplate;
use Tests\TestCase;

class RecruitingStageTemplateTest extends TestCase
{
    /** @test */
    public function it_belongs_to_a_company(): void
    {
        $template = RecruitingStageTemplate::factory()->create([]);
        $this->assertTrue($template->company()->exists());
    }

    /** @test */
    public function it_has_many_stages(): void
    {
        $template = RecruitingStageTemplate::factory()->create([]);
        RecruitingStage::factory()->count(2)->create([
            'recruiting_stage_template_id' => $template->id,
        ]);
        $this->assertTrue($template->stages()->exists());
    }

    /** @test */
    public function it_has_many_job_openings(): void
    {
        $template = RecruitingStageTemplate::factory()->create([]);
        JobOpening::factory()->count(2)->create([
            'recruiting_stage_template_id' => $template->id,
        ]);
        $this->assertTrue($template->jobOpenings()->exists());
    }
}
