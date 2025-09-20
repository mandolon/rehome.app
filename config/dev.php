<?php

/*
|--------------------------------------------------------------------------
| Development Configuration
|--------------------------------------------------------------------------
|
| This configuration file provides settings specific to development
| environment. It includes feature flags, debugging tools, and other
| development-specific options.
|
| Usage: config('dev.feature_flags.new_feature')
|        config('dev.debug_toolbar')
|
*/

return [
    /*
    |--------------------------------------------------------------------------
    | Feature Flags
    |--------------------------------------------------------------------------
    |
    | Toggle features on/off during development. Useful for A/B testing,
    | gradual rollouts, and experimental features.
    |
    */
    'feature_flags' => [
        // Example flags (add as needed):
        // 'new_task_ui' => env('FEATURE_NEW_TASK_UI', false),
        // 'enhanced_search' => env('FEATURE_ENHANCED_SEARCH', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Debug Tools
    |--------------------------------------------------------------------------
    |
    | Configuration for development debugging and profiling tools.
    |
    */
    'debug_toolbar' => env('APP_ENV', 'production') === 'local',

    /*
    |--------------------------------------------------------------------------
    | API Development Settings
    |--------------------------------------------------------------------------
    |
    | Settings specific to API development and testing.
    |
    */
    'api' => [
        'log_requests' => env('LOG_API_REQUESTS', false),
        'mock_external_apis' => env('MOCK_EXTERNAL_APIS', false),
    ],
];