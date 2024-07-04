<?php

namespace Tests\Unit\Services\Company\Wiki;

use App\Models\Company\Employee;
use App\Models\Company\Page;
use App\Models\Company\PageRevision;
use App\Models\Company\Wiki;
use App\Services\Company\Wiki\CreatePageRevision;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CreatePageRevisionTest extends TestCase
{
    /** @test */
    public function it_creates_a_page_revision_as_administrator(): void
    {
        $michael = $this->createAdministrator();
        $wiki = Wiki::factory()->create([
            'company_id' => $michael->company_id,
        ]);
        $page = Page::factory()->create([
            'wiki_id' => $wiki->id,
        ]);
        $this->executeService($michael, $wiki, $page);
    }

    /** @test */
    public function it_creates_a_page_revision_as_hr(): void
    {
        $michael = $this->createHR();
        $wiki = Wiki::factory()->create([
            'company_id' => $michael->company_id,
        ]);
        $page = Page::factory()->create([
            'wiki_id' => $wiki->id,
        ]);
        $this->executeService($michael, $wiki, $page);
    }

    /** @test */
    public function it_creates_a_page_revision_as_normal_user(): void
    {
        $michael = $this->createEmployee();
        $wiki = Wiki::factory()->create([
            'company_id' => $michael->company_id,
        ]);
        $page = Page::factory()->create([
            'wiki_id' => $wiki->id,
        ]);
        $this->executeService($michael, $wiki, $page);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'first_name' => 'Dwight',
        ];

        $this->expectException(ValidationException::class);
        (new CreatePageRevision)->execute($request);
    }

    /** @test */
    public function it_fails_if_the_wiki_is_not_in_the_company(): void
    {
        $michael = $this->createAdministrator();
        $wiki = Wiki::factory()->create();
        $page = Page::factory()->create([
            'wiki_id' => $wiki->id,
        ]);

        $this->expectException(ModelNotFoundException::class);
        $this->executeService($michael, $wiki, $page);
    }

    /** @test */
    public function it_fails_if_the_page_is_not_in_the_wiki(): void
    {
        $michael = $this->createAdministrator();
        $wiki = Wiki::factory()->create([
            'company_id' => $michael->company_id,
        ]);
        $page = Page::factory()->create([]);

        $this->expectException(ModelNotFoundException::class);
        $this->executeService($michael, $wiki, $page);
    }

    private function executeService(Employee $michael, Wiki $wiki, Page $page): void
    {
        Queue::fake();

        $request = [
            'company_id' => $michael->company_id,
            'author_id' => $michael->id,
            'wiki_id' => $wiki->id,
            'page_id' => $page->id,
        ];

        $pageRevision = (new CreatePageRevision)->execute($request);

        $this->assertDatabaseHas('page_revisions', [
            'page_id' => $page->id,
            'employee_id' => $michael->id,
            'employee_name' => $michael->name,
            'title' => $page->title,
            'content' => $page->content,
        ]);

        $this->assertInstanceOf(
            PageRevision::class,
            $pageRevision
        );
    }
}
