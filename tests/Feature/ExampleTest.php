<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * El root redirige al panel (login si no hay sesión), nunca una página genérica.
     */
    public function test_the_root_redirects_to_the_panel(): void
    {
        $this->get('/')->assertRedirect();
    }

    /**
     * El health check público responde ok.
     */
    public function test_health_check_responds(): void
    {
        $this->get('/health')->assertOk()->assertJson(['ok' => true]);
    }
}
