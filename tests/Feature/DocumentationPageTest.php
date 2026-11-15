<?php

namespace Tests\Feature;

use Database\Seeders\DocumentationSeeder;
use Database\Seeders\PlatformDocumentationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentationPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_custom_documentation_page_renders_the_current_endpoints(): void
    {
        $this->seed([DocumentationSeeder::class, PlatformDocumentationSeeder::class]);

        $this->get('/docs')
            ->assertOk()
            ->assertSee('TheaterLink API')
            ->assertSee('Theater Workspace Login')
            ->assertSee('List Team Members')
            ->assertSee('Admin Login')
            ->assertSee('Common errors');
    }
}
