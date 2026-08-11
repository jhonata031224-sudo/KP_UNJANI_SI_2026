<?php

namespace Tests\Feature;

use Tests\TestCase;

class HomepageTest extends TestCase
{
    public function test_the_homepage_renders_successfully(): void
    {
        $response = $this->get('/');

        $response->assertOk();
    }
}
