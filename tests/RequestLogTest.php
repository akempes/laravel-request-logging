<?php

namespace Akempes\RequestLogging\Tests;

use Akempes\RequestLogging\LogRequest;
use Carbon\Carbon;
use Config;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use TiMacDonald\Log\LogFake;

class RequestLogTest extends TestCase
{
    use RefreshDatabase;

    private $httpVerbs = ['get', 'put', 'post', 'delete'];

    public function setUp(): void
    {
        parent::setUp();

        \Route::any('/_test/html', function () {
            return '<div>RequestLogging</div';
        })->middleware(LogRequest::class);

        \Route::any('/_test/json', function () {
            return response()->json([
                'package' => 'RequestLogging',
                'class' => 'LogRequest',
            ], 200);
        })->middleware(LogRequest::class);
    }

    /** @test */
    public function it_should_log_any_requests()
    {
        $this->withoutExceptionHandling();

        Log::swap(new LogFake);

        Log::channel('Stack:default_testing_stack_channel.stack')->assertNothingLogged();

        foreach ($this->httpVerbs as $httpVerb) {
            $response = $this->$httpVerb('/_test/html');
            $response = $this->$httpVerb('/_test/json', ['Accept' => 'application/json']);
        }

        Log::channel('Stack:default_testing_stack_channel.stack')
            ->assertLoggedTimes('info', 16);
    }

    /** @test */
    public function it_should_not_log_any_requests_when_disabled()
    {
        $this->withoutExceptionHandling();

        Log::swap(new LogFake);

        Config::set('request-logging.enabled', false);

        Log::channel('Stack:default_testing_stack_channel.stack')->assertNothingLogged();

        foreach ($this->httpVerbs as $httpVerb) {
            $response = $this->$httpVerb('/_test/html');
            $response = $this->$httpVerb('/_test/json', ['Accept' => 'application/json']);
        }

        Log::channel('Stack:default_testing_stack_channel.stack')->assertNothingLogged();
    }

    /** @test */
    public function it_should_not_log_any_requests_when_the_method_is_blacklisted()
    {
        $this->withoutExceptionHandling();

        Log::swap(new LogFake);

        Config::set('request-logging.methods', ['post']);

        Log::channel('Stack:default_testing_stack_channel.stack')->assertNothingLogged();

        foreach ($this->httpVerbs as $httpVerb) {
            $response = $this->$httpVerb('/_test/html');
            $response = $this->$httpVerb('/_test/json', ['Accept' => 'application/json']);
        }

        Log::channel('Stack:default_testing_stack_channel.stack')
            ->assertLoggedTimes('info', 4);
    }

    /** @test */
    public function it_should_not_log_any_requests_when_the_route_is_blacklisted()
    {
        $this->withoutExceptionHandling();

        Log::swap(new LogFake);

        Log::channel('Stack:default_testing_stack_channel.stack')->assertNothingLogged();

        Config::set('request-logging.exclude-routes', ['/_test/html']);

        foreach ($this->httpVerbs as $httpVerb) {
            $response = $this->$httpVerb('/_test/html');
            $response = $this->$httpVerb('/_test/json', ['Accept' => 'application/json']);
        }

        Log::channel('Stack:default_testing_stack_channel.stack')
            ->assertLoggedTimes('info', 8);
    }

    /** @test */
    public function a_request_log_should_contain_meta_data_information()
    {
        $this->withoutExceptionHandling();

        Log::swap(new LogFake);

        Log::channel('Stack:default_testing_stack_channel.stack')->assertNothingLogged();

        $data = ['foo' => 'bar'];

        $response = $this->POST('/_test/html', $data);

        Log::channel('Stack:default_testing_stack_channel.stack')->assertLoggedTimes('info', 2);
        Log::channel('Stack:default_testing_stack_channel.stack')
            ->assertLogged('info', function ($message) {
                return Str::contains($message, '#');
            });
        Log::channel('Stack:default_testing_stack_channel.stack')
            ->assertLogged('info', function ($message) {
                return Str::contains($message, 'POST');
            });
        Log::channel('Stack:default_testing_stack_channel.stack')
            ->assertLogged('info', function ($message) {
                return Str::contains($message, '/_test/html');
            });
    }

    /** @test */
    public function request_parameters_should_be_logged()
    {
        $this->withoutExceptionHandling();

        Log::swap(new LogFake);

        Log::channel('Stack:default_testing_stack_channel.stack')->assertNothingLogged();

        $data = ['foo' => 'bar'];

        $response = $this->POST('/_test/html', $data);

        Log::channel('Stack:default_testing_stack_channel.stack')->assertLoggedTimes('info', 2);
        Log::channel('Stack:default_testing_stack_channel.stack')
            ->assertLogged('info', function ($message) use($data) {
                return Str::contains($message, json_encode($data));
            });
    }

    /** @test */
    public function blacklisted_parameters_should_not_be_logged()
    {
        $this->withoutExceptionHandling();

        Log::swap(new LogFake);

        Log::channel('Stack:default_testing_stack_channel.stack')->assertNothingLogged();

        Config::set('request-logging.exclude-request-fields', ['foobar']);

        $data = ['foo' => 'bar', 'foobar' => 'f00b@r'];

        $response = $this->POST('/_test/html', $data);

        Log::channel('Stack:default_testing_stack_channel.stack')->assertLoggedTimes('info', 2);
        Log::channel('Stack:default_testing_stack_channel.stack')
            ->assertLogged('info', function ($message) use($data) {
                return Str::contains($message, json_encode(Arr::only($data, ['foo'])));
            });
    }

    /** @test */
    public function a_single_file_upload_request_should_file_meta_data()
    {
        $this->withoutExceptionHandling();

        Log::swap(new LogFake);

        Log::channel('Stack:default_testing_stack_channel.stack')->assertNothingLogged();

        $data = [
            'avatar' => UploadedFile::fake()->image('avatar.jpg'),
        ];

        $response = $this->POST('/_test/html', $data);

        Log::channel('Stack:default_testing_stack_channel.stack')->assertLoggedTimes('info', 2);
        Log::channel('Stack:default_testing_stack_channel.stack')
            ->assertLogged('info', function ($message) {
                return Str::contains($message, 'Files: avatar.jpg');
            });
    }

    /** @test */
    public function a_multi_file_upload_request_should_file_meta_data_array()
    {
        $this->withoutExceptionHandling();

        Log::swap(new LogFake);

        Log::channel('Stack:default_testing_stack_channel.stack')->assertNothingLogged();

        $data = [
            'photos' => [
                UploadedFile::fake()->image('photo_1.jpg'),
                UploadedFile::fake()->image('photo_2.jpg'),
            ]
        ];

        $response = $this->POST('/_test/html', $data);

        Log::channel('Stack:default_testing_stack_channel.stack')->assertLoggedTimes('info', 2);
        Log::channel('Stack:default_testing_stack_channel.stack')
            ->assertLogged('info', function ($message) {
                return Str::contains($message, 'Files: photo_1.jpg,photo_2.jpg');
            });
    }


    /** @test */
    public function a_response_log_should_contain_meta_data_information()
    {
        $this->withoutExceptionHandling();

        Log::swap(new LogFake);

        Log::channel('Stack:default_testing_stack_channel.stack')->assertNothingLogged();

        $data = ['foo' => 'bar'];

        $response = $this->POST('/_test/html', $data);

        Log::channel('Stack:default_testing_stack_channel.stack')->assertLoggedTimes('info', 2);
        Log::channel('Stack:default_testing_stack_channel.stack')
            ->assertLogged('info', function ($message) {
                return Str::contains($message, '#');
            });
        Log::channel('Stack:default_testing_stack_channel.stack')
            ->assertLogged('info', function ($message) {
                return Str::contains($message, '200');
            });
        Log::channel('Stack:default_testing_stack_channel.stack')
            ->assertLogged('info', function ($message) {
                return Str::contains($message, 'Duration:');
            });
    }

    /** @test */
    public function a_response_log_should_throw_a_warning_when_it_took_too_long_to_respond()
    {
        $this->withoutExceptionHandling();

        Log::swap(new LogFake);

        Log::channel('Stack:default_testing_stack_channel.stack')->assertNothingLogged();

        Config::set('request-logging.request-duration-limit', 0.001);

        $data = ['foo' => 'bar'];

        $response = $this->POST('/_test/html', $data);

        Log::channel('Stack:default_testing_stack_channel.stack')->assertLoggedTimes('info', 2);
        Log::channel('Stack:default_testing_stack_channel.stack')->assertLoggedTimes('warning', 1);
        Log::channel('Stack:default_testing_stack_channel.stack')
            ->assertLogged('warning', function ($message) use($data) {
                return Str::contains($message, 'Request exceeded response duration threshold');
            });
        Log::channel('Stack:default_testing_stack_channel.stack')
            ->assertLogged('warning', function ($message) use($data) {
                return Str::contains($message, '/_test/html');
            });
    }

    /** @test */
    public function a_response_log_should_not_throw_a_warning_when_request_duration_limit_is_disabled()
    {
        $this->withoutExceptionHandling();

        Log::swap(new LogFake);

        Log::channel('Stack:default_testing_stack_channel.stack')->assertNothingLogged();

        Config::set('request-logging.request-duration-limit', false);

        $data = ['foo' => 'bar'];

        $response = $this->POST('/_test/html', $data);

        Log::channel('Stack:default_testing_stack_channel.stack')->assertLoggedTimes('info', 2);
        Log::channel('Stack:default_testing_stack_channel.stack')->assertNotLogged('warning');
    }

    /** @test */
    public function a_none_json_response_body_should_contain_a_placeholder()
    {
        $this->withoutExceptionHandling();

        Log::swap(new LogFake);

        Log::channel('Stack:default_testing_stack_channel.stack')->assertNothingLogged();

        Config::set('request-logging.show-response-html', false);

        $data = ['foo' => 'bar'];

        $response = $this->POST('/_test/html', $data);

        Log::channel('Stack:default_testing_stack_channel.stack')->assertLoggedTimes('info', 2);
        Log::channel('Stack:default_testing_stack_channel.stack')
            ->assertNotLogged('info', function ($message) use($data) {
                return Str::contains($message, '<div>');
            });
        Log::channel('Stack:default_testing_stack_channel.stack')
            ->assertLogged('info', function ($message) use($data) {
                return Str::contains($message, 'Non-JSON content returned');
            });
    }

    /** @test */
    public function a_none_json_response_body_should_contain_the_actual_value_when_specified_in_the_config()
    {
        $this->withoutExceptionHandling();

        Log::swap(new LogFake);

        Log::channel('Stack:default_testing_stack_channel.stack')->assertNothingLogged();

        Config::set('request-logging.show-response-html', true);

        $data = ['foo' => 'bar'];

        $response = $this->POST('/_test/html', $data);

        Log::channel('Stack:default_testing_stack_channel.stack')->assertLoggedTimes('info', 2);
        Log::channel('Stack:default_testing_stack_channel.stack')
            ->assertNotLogged('info', function ($message) use($data) {
                return Str::contains($message, 'Non-JSON content returned');
            });
        Log::channel('Stack:default_testing_stack_channel.stack')
            ->assertLogged('info', function ($message) use($data) {
                return Str::contains($message, '<div>');
            });
    }

    /** @test */
    public function a_json_response_body_should_contain_a_encoded_json_object()
    {
        $this->withoutExceptionHandling();

        Log::swap(new LogFake);

        Log::channel('Stack:default_testing_stack_channel.stack')->assertNothingLogged();

        $data = ['foo' => 'bar'];

        $response = $this->POST('/_test/json', $data, ['Accept' => 'application/json']);

        Log::channel('Stack:default_testing_stack_channel.stack')->assertLoggedTimes('info', 2);
        Log::channel('Stack:default_testing_stack_channel.stack')
            ->assertLogged('info', function ($message) use($data) {
                return Str::contains($message, '{"package":"RequestLogging","class":"LogRequest"}');
            });
    }

    /** @test */
    public function a_json_response_body_should_not_contain_any_blacklisted_attributes()
    {
        $this->withoutExceptionHandling();

        Log::swap(new LogFake);

        Log::channel('Stack:default_testing_stack_channel.stack')->assertNothingLogged();

        Config::set('request-logging.exclude-response-fields', ['class']);

        $data = ['foo' => 'bar'];

        $response = $this->POST('/_test/json', $data, ['Accept' => 'application/json']);

        Log::channel('Stack:default_testing_stack_channel.stack')->assertLoggedTimes('info', 2);
        Log::channel('Stack:default_testing_stack_channel.stack')
            ->assertLogged('info', function ($message) use($data) {
                return Str::contains($message, '{"package":"RequestLogging"}');
            });
        Log::channel('Stack:default_testing_stack_channel.stack')
            ->assertNotLogged('info', function ($message) use($data) {
                return Str::contains($message, '"class":"LogRequest"');
            });
    }

    /** @test */
    public function it_should_log_the_request_and_response_in_the_correct_log_channel_as_specified_in_the_config()
    {
        $this->withoutExceptionHandling();

        Log::swap(new LogFake);

        Log::channel('Stack:default_testing_stack_channel.daily')->assertNothingLogged();

        Config::set('request-logging.log-channels', ['daily']);

        $data = ['foo' => 'bar'];

        $response = $this->POST('/_test/json', $data, ['Accept' => 'application/json']);

        Log::channel('Stack:default_testing_stack_channel.daily')->assertLoggedTimes('info', 2);
        Log::channel('Stack:default_testing_stack_channel.daily')
            ->assertLogged('info', function ($message) use($data) {
                return Str::contains($message, '{"package":"RequestLogging","class":"LogRequest"}');
            });
    }

    /** @test */
    public function it_should_log_the_request_and_response_with_the_correct_log_level_as_specified_in_the_config()
    {
        $this->withoutExceptionHandling();

        Log::swap(new LogFake);

        Log::channel('Stack:default_testing_stack_channel.stack')->assertNothingLogged();

        Config::set('request-logging.log-level', 'debug');

        $data = ['foo' => 'bar'];

        $response = $this->POST('/_test/json', $data, ['Accept' => 'application/json']);

        Log::channel('Stack:default_testing_stack_channel.stack')->assertLoggedTimes('debug', 2);
        Log::channel('Stack:default_testing_stack_channel.stack')
            ->assertLogged('debug', function ($message) use($data) {
                return Str::contains($message, '{"package":"RequestLogging","class":"LogRequest"}');
            });
    }

    /** @test */
    public function it_should_log_warnings_in_the_correct_log_channel_as_specified_in_the_config()
    {
        $this->withoutExceptionHandling();

        Log::swap(new LogFake);

        Log::channel('Stack:default_testing_stack_channel.stack')->assertNothingLogged();

        Config::set('request-logging.request-duration-limit', 0.001);
        Config::set('request-logging.warning-log-channels', ['daily']);

        $data = ['foo' => 'bar'];

        $response = $this->POST('/_test/html', $data);

        Log::channel('Stack:default_testing_stack_channel.stack')->assertLoggedTimes('info', 2);
        Log::channel('Stack:default_testing_stack_channel.daily')->assertLoggedTimes('warning', 1);
        Log::channel('Stack:default_testing_stack_channel.daily')
            ->assertLogged('warning', function ($message) use($data) {
                return Str::contains($message, 'Request exceeded response duration threshold');
            });
        Log::channel('Stack:default_testing_stack_channel.daily')
            ->assertLogged('warning', function ($message) use($data) {
                return Str::contains($message, '/_test/html');
            });
    }

    /** @test */
    public function it_should_log_warnings_with_the_correct_log_level_as_specified_in_the_config()
    {
        $this->withoutExceptionHandling();

        Log::swap(new LogFake);

        Log::channel('Stack:default_testing_stack_channel.stack')->assertNothingLogged();

        Config::set('request-logging.request-duration-limit', 0.001);
        Config::set('request-logging.warning-log-level', 'debug');

        $data = ['foo' => 'bar'];

        $response = $this->POST('/_test/html', $data);

        Log::channel('Stack:default_testing_stack_channel.stack')->assertLoggedTimes('info', 2);
        Log::channel('Stack:default_testing_stack_channel.stack')->assertLoggedTimes('debug', 1);
        Log::channel('Stack:default_testing_stack_channel.stack')
            ->assertLogged('debug', function ($message) use($data) {
                return Str::contains($message, 'Request exceeded response duration threshold');
            });
        Log::channel('Stack:default_testing_stack_channel.stack')
            ->assertLogged('debug', function ($message) use($data) {
                return Str::contains($message, '/_test/html');
            });
    }

    /** @test */
    public function it_should_log_to_the_database_table_when_enabled()
    {
        $this->withoutExceptionHandling();

        Log::swap(new LogFake);

        $data = ['foo' => 'bar'];

        Config::set('request-logging.database-logging.enabled', false);

        $this->assertDatabaseMissing('requests', [
            'method' => 'post',
            'uri' => '/_test/json',
            'body' => json_encode($data),
            'request_size' => 13,
            'response_size' => 49,
            'status' => 200,
        ]);

        $response = $this->POST('/_test/json', $data, ['Accept' => 'application/json']);

        Config::set('request-logging.database-logging.enabled', true);

        $this->assertDatabaseMissing('requests', [
            'method' => 'POST',
            'uri' => '/_test/json',
            'body' => json_encode($data),
            'request_size' => 13,
            'response_size' => 49,
            'status' => 200,
        ]);

        $response = $this->POST('/_test/json', $data, ['Accept' => 'application/json']);

        $this->assertDatabaseHas('requests', [
            'method' => 'POST',
            'uri' => '/_test/json',
            'body' => json_encode($data),
            'request_size' => 13,
            'response_size' => 49,
            'status' => 200,
        ]);
    }

    /** @test */
    public function it_should_remove_database_records_when_exeeding_the_persistence_limit()
    {
        $this->withoutExceptionHandling();

        Log::swap(new LogFake);

        $data = ['foo' => 'bar'];

        Config::set('request-logging.database-logging.enabled', true);
        Config::set('request-logging.database-logging.persistence', 2);

        $this->assertDatabaseMissing('requests', [
            'method' => 'POST',
            'uri' => '/_test/json',
            'body' => json_encode($data),
            'status' => 200,
        ]);

        $response = $this->POST('/_test/json', $data, ['Accept' => 'application/json']);

        $this->assertDatabaseHas('requests', [
            'method' => 'POST',
            'uri' => '/_test/json',
            'body' => json_encode($data),
            'status' => 200,
        ]);

        Carbon::setTestNow(Carbon::now()->addDays(5));

        $response = $this->GET('/_test/html');

        $this->assertDatabaseMissing('requests', [
            'method' => 'POST',
            'uri' => '/_test/json',
            'body' => json_encode($data),
            'status' => 200,
        ]);

        $this->assertDatabaseHas('requests', [
            'method' => 'GET',
            'uri' => '/_test/html',
            'status' => 200,
        ]);
    }

    /** @test */
    public function it_should_truncate_response_data_when_logging_to_the_database()
    {
        $this->withoutExceptionHandling();

        Log::swap(new LogFake);

        $data = ['foo' => 'bar'];

        Config::set('request-logging.database-logging.enabled', true);
        Config::set('request-logging.database-logging.limit-response', 5);

        $this->assertDatabaseMissing('requests', [
            'method' => 'POST',
            'uri' => '/_test/json',
            'body' => json_encode($data),
            'request_size' => 13,
            'response' => '{"pac...',
            'response_size' => 49,
            'status' => 200,
        ]);

        $response = $this->POST('/_test/json', $data, ['Accept' => 'application/json']);

        $this->assertDatabaseHas('requests', [
            'method' => 'POST',
            'uri' => '/_test/json',
            'body' => json_encode($data),
            'request_size' => 13,
            'response' => '{"pac...',
            'response_size' => 49,
            'status' => 200,
        ]);
    }
}
