# Request logging
[![Latest Stable Version](https://poser.pugx.org/akempes/laravel-request-logging/v/stable)](https://packagist.org/packages/akempes/laravel-request-logging)
[![Total Downloads](https://poser.pugx.org/akempes/laravel-request-logging/downloads)](https://packagist.org/packages/akempes/laravel-request-logging)
[![License](https://poser.pugx.org/akempes/laravel-request-logging/license)](https://packagist.org/packages/akempes/laravel-request-logging)

This Laravel package contains middleware to log requests and there responses including all parameters. This will allow you to monitor and replay requests which can be extremely helpful for debugging purposes.

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

## Upgrade Guide

### 1.x -> 2.x
It shouldn't be hard, just run `php artisan vendor:publish --provider="Akempes\RequestLogging\RequestLoggingServiceProvider"` to get the new migration. Don't forget to run `php artisan migrate` as well.

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

#### database-logging
Besides logging to a file you may opt for logging to a database table.

*enabled*  
Enable the database logging  

*table*  
The table name, default value is 'requests'.  

*persistence*  
Depending on your settings, application and traffic log files (and database) may consume a fair amount of storage data. To redress this issue, the logged requests are removed form the table after the set amount of days. Default value is 2 days.

*limit-response*  
To prevent a huge database table you may want to limit the stored response data. Then setting this to a value > 0 the data is truncated after the set amount of characters. The default value is 2000.

#### request-log-format
You are able to compose custom log messages using the variables as shown below.

| Variable | Description |
|---|---|
| *{microTimeStamp}* | A very precise timestamp. |
| *{requestId}* | In order keep track of request and response, the last digits of the {microTimeStamp} are used as an ID. Although this ID is not unique, it is in general enough to separate multiple incoming requests. |
| *{ip}* | IP address. |
| *{method}* | HTTP method (GET, POST, PUT, ...) |
| *{url}* | Request path. |
| *{requestBody}* | All request data. |
| *{files}* | When documents are uploaded the filenames (and some metadata) are shown. Note: The content of the file is NOT logged. |

#### response-log-format
You are able to compose custom log messages using the variables as shown below.

| Variable | Description |
|---|---|
| *{microTimeStamp}* | A very precise timestamp. |
| *{requestId}* | Has the same value as the `requestId` used in the request log message. This will help you finding the request belonging to this response. |
| *{userId}* | When the user is authenticated the id will be shown, empty otherwise. |
| *{ip}* | IP address. |
| *{databaseId}* | Shows the record ID when using database-logging. Note: The ID is not available in the request logging format. The log message is first send to the Log facade before an attempt is made to store the message it in the database. |
| *{responseStatusCode}* | HTTP status code to response. |
| *{duration}* | Duration between request and response in milliseconds. |
| *{responseBody}* | Data returned to client. |
| *{targetUrl}* | Empty by default. Contains the target url when the response is a redirect. (`responseStatusCode` in 300-series) |
| *{isRedirecting}* | Same as `{targetUrl}` but with 'Redirecting to' prefix. Default is still empty, only when the response is a redirect. |
