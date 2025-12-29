<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Sockeon Server Configuration
    |--------------------------------------------------------------------------
    |
    | This configuration file contains settings for the Sockeon WebSocket
    | server. The server runs independently from Laravel's HTTP server.
    |
    */

    'host' => env('SOCKEON_HOST', '0.0.0.0'),

    'port' => env('SOCKEON_PORT', 8080),

    'debug' => env('APP_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Log File
    |--------------------------------------------------------------------------
    |
    | Specify the log file path for Sockeon WebSocket server logs.
    | If null, logs will be written to the default Laravel log file.
    |
    */

    'log_file' => env('SOCKEON_LOG_FILE', storage_path('logs/sockeon-websocket.log')),

    /*
    |--------------------------------------------------------------------------
    | WebSocket Controllers
    |--------------------------------------------------------------------------
    |
    | Register your WebSocket controllers here. Each controller should extend
    | Sockeon\Sockeon\Controllers\SocketController and use attributes to
    | define event handlers.
    |
    */

    'controllers' => [
        \App\WebSocket\ChatController::class,
    ],
];
