<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomepageTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_homepage_renders_successfully(): void
    {
        // Landing page memakai @vite(), sedangkan lingkungan test tidak punya
        // hasil build frontend. Vite dinonaktifkan supaya yang diuji hanya
        // sisi server (rute, basis data pengaturan, dan view).
        $this->withoutVite();

        $response = $this->get('/');

        $response->assertOk();
    }
}
