<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Hermes agent gateway (OPAC "AI mode")
    |--------------------------------------------------------------------------
    | The local RAG agent. Lives here rather than in the settings table on
    | purpose (RAG_INTEGRATION_PLAN.md §10.3): the gateway binds to 127.0.0.1
    | only, Laravel is the sole internet-facing surface and calls it
    | server-side, and the shared token must stay out of the database and
    | never reach a browser. Rotate by editing .env.
    |
    | The on/off switch for AI mode is NOT here — that's an operational
    | setting an admin flips (settings table, `ai_mode_enabled`).
    */
    'hermes' => [
        'url'     => env('HERMES_GATEWAY_URL', 'http://127.0.0.1:8090'),
        'token'   => env('HERMES_GATEWAY_TOKEN'),
        'timeout' => (int) env('HERMES_GATEWAY_TIMEOUT', 60),
    ],

];
