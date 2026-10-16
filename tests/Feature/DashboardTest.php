<?php

namespace Tests\Feature;

use Tests\TestCase;

class DashboardTest extends TestCase
{
    public function test_guests_can_not_open_the_dashboard_or_its_data()
    {
        $this->get('/admin/dashboard')->assertRedirect(route('login'));
        $this->getJson('/admin/dashboard/activity?range=30d')->assertUnauthorized();
    }
}
