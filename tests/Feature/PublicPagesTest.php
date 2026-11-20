<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    use RefreshDatabase;

    #[TestWith(['/', 'Know where it goes.'])]
    #[TestWith(['/features', 'What Accountant does'])]
    #[TestWith(['/pricing', 'Simple pricing'])]
    public function test_public_pages_are_visible_without_logging_in(string $path, string $heading): void
    {
        $this->get($path)
            ->assertOk()
            ->assertSee($heading)
            ->assertSee('<link rel="canonical"', false);
    }

    public function test_sitemap_lists_public_pages(): void
    {
        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml')
            ->assertSee(route('features'))
            ->assertSee(route('pricing'))
            ->assertDontSee('/app');
    }
}
