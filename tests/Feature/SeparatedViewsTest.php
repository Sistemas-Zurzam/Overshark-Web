<?php

namespace Tests\Feature;

use Tests\TestCase;

class SeparatedViewsTest extends TestCase
{
    public function test_public_storefront_is_available(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Diseñado para destacar.');
    }

    public function test_admin_dashboard_is_available(): void
    {
        $this->get('/admin')
            ->assertOk()
            ->assertSee('Administración de Overshark');
    }
}
