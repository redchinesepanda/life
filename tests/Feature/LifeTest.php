<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LifeTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function test_state()
    {
        $response = $this->post(
            '/api/state',

            [ 'url' => 'https://test.ru/main' ]
        );

        $response->assertStatus(200);
    }
}
