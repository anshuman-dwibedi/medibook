<?php
/**
 * MediBook Clinic — config.php
 * Place this file at the project root.
 * NEVER commit to Git — add to .gitignore
 *
 * The Database class resolves this as: dirname(__DIR__, 2) . '/config.php'
 * so this file lives alongside the /core/ and /medical-booking-system/ folders.
 *
 * Folder layout expected:
 *   your-project/
 *   ├── config.php          ← this file
 *   ├── core/               ← devcore shared library
 *   └── medical-booking-system/
 */
return [
    // ── Database ──────────────────────────────────────────────
    'db_host' => 'localhost',
    'db_name' => 'medibook',
    'db_user' => 'root',
    'db_pass' => '',

    // ── App ───────────────────────────────────────────────────
    'app_name' => 'MediBook Clinic',
    'app_url'  => 'http://localhost/medical-booking-system',
    'debug'    => true, // Set false in production

    // ── API ───────────────────────────────────────────────────
    'api_secret' => 'change-this-to-a-random-secret-string',

    // ── Storage ───────────────────────────────────────────────
    // Driver options: 'local' | 's3' | 'r2'
    'storage' => [
        'driver' => 'local',

        'local' => [
            'root'     => __DIR__ . '/medical-booking-system/uploads',
            'base_url' => 'http://localhost/medical-booking-system/uploads',
        ],

        's3' => [
            'key'      => '',
            'secret'   => '',
            'bucket'   => '',
            'region'   => 'us-east-1',
            'base_url' => '',
            'acl'      => 'public-read',
        ],

        'r2' => [
            'account_id' => '',
            'key'        => '',
            'secret'     => '',
            'bucket'     => '',
            'base_url'   => '',
        ],
    ],
];
