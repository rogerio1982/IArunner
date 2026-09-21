<?php

require __DIR__ . '/config/database.php';

// Reaproveita o mesmo .env/loader já usado por config/database.php,
// em vez de duplicar leitura de credenciais aqui.
load_env();

return [
    'paths' => [
        'migrations' => __DIR__ . '/db/migrations',
        'seeds' => __DIR__ . '/db/seeds',
    ],
    'environments' => [
        'default_migration_table' => 'phinxlog',
        'default_environment' => 'production',
        'production' => [
            'adapter' => 'mysql',
            'host' => env('DB_HOST', 'localhost'),
            'name' => env('DB_NAME', 'ai_runner'),
            'user' => env('DB_USER', 'root'),
            'pass' => env('DB_PASS', ''),
            'port' => 3306,
            'charset' => 'utf8mb4',
        ],
    ],
    'version_order' => 'creation',
];
