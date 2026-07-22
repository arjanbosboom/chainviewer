<?php

declare(strict_types=1);

use ChainViewer\Database\Migrator;

require __DIR__ . '/../vendor/autoload.php';

$config = require __DIR__ . '/config/app.php';

$databaseConfig = $config['database'];

$pdo = new PDO(
    sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
        $databaseConfig['host'],
        $databaseConfig['port'],
        $databaseConfig['database'],
    ),
    $databaseConfig['username'],
    $databaseConfig['password'],
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]
);

$migrator = new Migrator($pdo, __DIR__ . '/database/migrations');
$migrator->run();

echo "Migrations applied successfully.\n";