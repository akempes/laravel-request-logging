<?php

namespace Akempes\RequestLogging\Tests;

use TiMacDonald\Log\LogFake;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class RequestLogTest extends TestCase
{

    public function setUp(): void
    {
        parent::setUp();

        \Route::any('/_test/route', function () {
            return 'OK';
        });
    }

    /** @test */
    public function it_should_log_any_request()
    {
        Log::swap(new LogFake);

        Log::info('Donuts have arrived');

        $this->withoutExceptionHandling();

        $httpVerbs = ['get', 'put', 'patch', 'delete'];

        foreach ($httpVerbs as $httpVerb) {
            $response = $this->$httpVerb('/_test/route');
        }

        print_r(Log::logged('DEBUG', function ($message, $context)
        {
            print '<pre>';
            print_r($message);
            print_r($context);
            print '</pre>';
        }));

        Log::assertLogged('info', function ($message, $context) {
            return Str::contains($message, 'Donuts');
        });

    }
}
