<?php

return [

    'enabled' => env('REQUEST_LOGGING_ENABLED', true),

    'methods' => [
        'GET',
        'POST',
        'PUT',
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

];
