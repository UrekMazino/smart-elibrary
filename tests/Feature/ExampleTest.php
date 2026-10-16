<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function test_example()
    {
        // The site root redirects to the OPAC home page (HomeController@index2).
        $this->get('/')->assertRedirect('/home');

        $this->get('/home')->assertStatus(200);
    }
}
