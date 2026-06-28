<?php

return [
    'version' => env('APP_VERSION', '1.0.0'),
    'late_cutoff' => env('ATTENDANCE_LATE_CUTOFF', '08:00:00'),
    'school_timezone' => env('SCHOOL_TIMEZONE', 'Asia/Manila'),

    /*
    |--------------------------------------------------------------------------
    | Role Configuration
    |--------------------------------------------------------------------------
    |
    | Central role identifiers used across authorization, seeders, and setup.
    | Super Admin is the highest-privilege role: full access, role management,
    | and visibility of all accounts including other super admins.
    |
    */
    'roles' => [
        'super_admin' => env('CLASSESYNC_SUPER_ADMIN_ROLE', 'super_admin'),
        'administrator' => env('CLASSESYNC_ADMINISTRATOR_ROLE', 'administrator'),
        'setup_default_role' => env('CLASSESYNC_SETUP_DEFAULT_ROLE', 'super_admin'),
        'manage_roles_ability' => 'manageRoles',
    ],

    /*
    |--------------------------------------------------------------------------
    | Application Packaging
    |--------------------------------------------------------------------------
    |
    | PHP (Apache/Laragon web) often cannot see Node.js on PATH. Set explicit
    | binary paths when building assets or Electron installers from the UI.
    |
    */
    'deployment' => [
        'npm_binary' => env('NPM_BINARY'),
        'node_binary' => env('NODE_BINARY'),
        'electron_mirror' => env('ELECTRON_MIRROR'),
        'electron_download_timeout' => (int) env('ELECTRON_DOWNLOAD_TIMEOUT', 7200),
        'electron_build_timeout' => (int) env('ELECTRON_BUILD_TIMEOUT', 7200),
    ],
];
