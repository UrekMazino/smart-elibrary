<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    /**
     * A made-up password that meets the password policy (12+ characters, not common), for tests that
     * register or set a password. Not a secret: the comment tells the secret scanner so.
     */
    protected const GOOD_PASSWORD = 'river-lantern-mango-71'; // gitleaks:allow

    /**
     * Safety net: several tests use RefreshDatabase, which drops every table. Refuse to run
     * unless the configured database is a dedicated *_test database (see phpunit.xml), so a
     * misconfiguration fails here instead of wiping the real catalogue.
     */
    public function createApplication()
    {
        $app = require __DIR__.'/../bootstrap/app.php';
        $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

        $connection = $app['config']->get('database.default');
        $database = (string) $app['config']->get("database.connections.$connection.database");
        if ($connection !== 'sqlite' && ! str_ends_with($database, '_test')) {
            throw new \RuntimeException(
                "Refusing to run tests against the database \"$database\". "
                . 'Tests drop every table; point phpunit.xml DB_DATABASE at a *_test database.'
            );
        }

        return $app;
    }
}
