<?php

namespace Tests\Unit\Models\Company;

use App\Models\Company\ECoffee;
use App\Models\Company\ECoffeeMatch;
use Tests\TestCase;

class ECoffeeTest extends TestCase
{
    /** @test */
    public function it_belongs_to_a_company(): void
    {
        $coffee = ECoffee::factory()->create([]);
        $this->assertTrue($coffee->company()->exists());
    }

    /** @test */
    public function it_has_many_matches(): void
    {
        $coffee = ECoffee::factory()->create([]);

        ECoffeeMatch::factory()->count(2)->create([
            'e_coffee_id' => $coffee->id,
        ]);

        $this->assertTrue($coffee->matches()->exists());
    }
}
