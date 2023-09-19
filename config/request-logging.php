<?php

return [

    'enabled' => env('REQUEST_LOGGING_ENABLED', true),

    'methods' => [
        'GET',
        'POST',
        'PUT',
        'PATCH',
        'DELETE',
    ],

    'exclude-routes' => [
    ],

    'exclude-request-fields' => [
        'password',
        'password_confirmation',
    ],

    'request-duration-limit' => false,
    'show-response-html' => false,

    'exclude-response-fields' => [
    ],

    'log-channels' => [
        'stack'
    ],
    'log-level' => 'info',

    'warning-log-channels' => [
        'stack'
    ],
    'warning-log-level' => 'warning',

    'database-logging' => [
        'enabled' => false,
        'table' => 'requests',
        'persistence' => 2,
        'limit-response' => 2000,
    ],

    'request-log-format' => '#{requestId} IP: {ip} {method} {uri} - Body: {requestBody} - Files: {files}',

    'response-log-format' => '#{requestId} User: #{userId} IP: {ip} DB: #{databaseId} {responseStatusCode} - Duration: {duration} - Body: {responseBody} {isRedirecting}',

];
