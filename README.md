# Request logging
[![Latest Stable Version](https://poser.pugx.org/akempes/laravel-request-logging/v/stable)](https://packagist.org/packages/akempes/laravel-request-logging)
[![Total Downloads](https://poser.pugx.org/akempes/laravel-request-logging/downloads)](https://packagist.org/packages/akempes/laravel-request-logging)
[![License](https://poser.pugx.org/akempes/laravel-request-logging/license)](https://packagist.org/packages/akempes/laravel-request-logging)

This Laravel package contains middleware to log requests and there responses including all parameters. This will allow you to monitor and replay requests what can be extremely helpful for debugging purpose.

## Installation

You can install using [composer](https://getcomposer.org/) from [Packagist](https://packagist.org/packages/akempes/laravel-request-logging)

```
composer require akempes/laravel-request-logging
```

Next step is to add the middleware in your app/Http/Kernel.php file.

Add the request logging to all routes:
```
protected $middleware = [
    ...
    \Akempes\RequestLogging\LogRequest::class,
    ...
];
```

Or you only for specific route(group)s.
```
protected $routeMiddleware = [
    ...
    'logRequest' => \Akempes\RequestLogging\LogRequest::class,
    ...
];
```

Finally, although optionally, you can publish the configuration file:

```
php artisan vendor:publish --provider="Akempes\RequestLogging\RequestLoggingServiceProvider"
```


## Configuration

#### enabled
You probably guessed it already, the requests are logged when enabled is `true`. You can add `REQUEST_LOGGING_ENABLED` to your `.env` for maximal flexibility.

#### methods
Array of the request methods you want to log.

#### exclude-routes
Some routes may not need any logging. Enter the full path: `/first-segment/second-segment` under the hood Laravel `Request::is()` method is used.

#### exclude-request-fields
Like the field `password`, some field may contain sensitive information and are not suitable for logging. These can be excluded from the logging.

#### request-duration-limit
You may want to log request that take a long time to complete. This can help you optimizing you application. Enter the maximum allowed duration in milliseconds or `false` to disable. Note: the request isn't aborted, just a extra log record is created.
Also see: warning-log-channels & warning-log-level below.

#### show-response-html
By default only the json response body is logged to prevent a log file full of HTML.
But you can turn this feature off by setting it to `false`.

#### exclude-response-fields
Actually the same as `exclude-request-fields`, some response data may contain sensitive information and are not suitable for logging.

#### log-channels
You can specify where the logging should be put by adding the log channels as defined in you logging config. Multiple log channels are allowed.

#### log-level
By default the log level `info` is used but you are free to choose your own. (As of Laravel: Emergency, alert, critical, error, warning, notice, info, and debug.)

#### warning-log-channels
Same as log-channel except this channel will only be used for logs of requests exceeding the `request-duration-limit`.

#### warning-log-level
Same as log-level except the default log-level is `warning`. This log level will only be used for logs of requests exceeding the `request-duration-limit`.
