<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_visitante_vai_para_o_login(): void
    {
        $this->get('/')->assertRedirect('/login');
        $this->get('/fluxos')->assertRedirect('/login');
        $this->get('/resolucoes')->assertRedirect('/login');
        $this->get('/login')->assertOk();
    }
}
