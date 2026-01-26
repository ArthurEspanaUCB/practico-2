<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Deployment Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for atomic deployments and release management.
    |
    */

    // Number of releases to keep on the server
    'releases_to_keep' => env('DEPLOY_RELEASES_TO_KEEP', 3),

    // Health check timeout in seconds
    'health_check_timeout' => [
        'staging' => env('DEPLOY_HEALTH_TIMEOUT_STAGING', 30),
        'production' => env('DEPLOY_HEALTH_TIMEOUT_PRODUCTION', 60),
    ],

    // Database backup retention in days
    'backup_retention_days' => env('DEPLOY_BACKUP_RETENTION', 7),

    // Deployment paths
    'paths' => [
        'staging' => env('DEPLOY_PATH_STAGING', '/var/www/sistema-ventas-staging'),
        'production' => env('DEPLOY_PATH_PRODUCTION', '/var/www/sistema-ventas-production'),
    ],

    // Services to reload after deployment
    'services' => [
        'php_fpm' => env('DEPLOY_PHP_FPM_SERVICE', 'php8.4-fpm'),
        'nginx' => 'nginx',
        'supervisor' => 'supervisor',
    ],

    // Health check endpoints to validate
    'health_endpoints' => [
        '/api/health',
    ],

    // Critical endpoints for production smoke tests
    'smoke_test_endpoints' => [
        '/api/health',
        '/api/v1/auth/login', // Should return 401/422, not 500
    ],

];
