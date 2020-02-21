<?php

namespace Akempes\RequestLogging\Tests;

use Carbon\Carbon;
use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Akempes\RequestLogging\RequestLoggingServiceProvider;

abstract class TestCase extends OrchestraTestCase
{

    /** @var \Carbon\Carbon */
    protected $now;

    /** @var \Spatie\TemporaryDirectory\TemporaryDirectory */
    protected $temporaryDirectory;

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
