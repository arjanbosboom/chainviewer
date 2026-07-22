<?php

declare(strict_types=1);

return [
    'app_name' => 'ChainViewer',
    'environment' => getenv('APP_ENV') ?: 'development',
    'database' => [
        'host' => getenv('DB_HOST') ?: 'db',
        'port' => (int) (getenv('DB_PORT') ?: 3306),
        'database' => getenv('DB_DATABASE') ?: 'chainviewer',
        'username' => getenv('DB_USERNAME') ?: 'chainviewer',
        'password' => getenv('DB_PASSWORD') ?: 'chainviewer',
    ],
];