<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReadAccessTest extends TestCase
{
    public function test_guests_can_not_use_the_read_feature()
    {
        $this->get('/read/1')->assertRedirect(route('login'));
    }
}
