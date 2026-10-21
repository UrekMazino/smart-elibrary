<?php

namespace Tests\Feature;

use Tests\TestCase;

class QuickReviewTest extends TestCase
{
    public function test_guests_can_not_use_the_quick_review_endpoints()
    {
        $this->getJson('/review/quick/1')->assertUnauthorized();
        $this->postJson('/review/quick', ['catalog_id' => 1, 'rating' => 5])->assertUnauthorized();
    }
}
