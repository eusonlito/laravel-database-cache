<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cache
    |--------------------------------------------------------------------------
    |
    | Add Cache to Query Results
    |
    */

    'enabled' => (bool)env('DATABASE_CACHE_ENABLED', env('CACHE_ENABLED', true)),
    'driver' => env('DATABASE_CACHE_DRIVER', env('CACHE_DRIVER', 'redis')),
    'ttl' => (int)env('DATABASE_CACHE_TTL', env('CACHE_TTL', 3600)),
    'tag' => env('DATABASE_CACHE_TAG', 'database'),
    'prefix' => env('DATABASE_CACHE_PREFIX', 'database|'),
];
