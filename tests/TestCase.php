<?php

namespace Akempes\RequestLogging\Tests;

use Akempes\RequestLogging\RequestLoggingServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    /**
     * @param \Illuminate\Foundation\Application $app
     *
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            RequestLoggingServiceProvider::class,
        ];
    }
}
