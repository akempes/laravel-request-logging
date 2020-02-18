<?php

return [

    'enabled' => env('REQUEST_LOGGING_ENABLED', true),

    'log-channels' => [
        'stack'
    ],

    'methods' => [
        'GET',
        'POST',
        'PUT',
        'DELETE',
    ],

    'show-response-html' => false,

    'request-duration-limit' => false,

    'exclude-routes' => [
    ],

    'exclude-request-fields' => [
        'password',
        'password_confirmation',
    ],

    'exclude-response-fields' => [
    ],

];
