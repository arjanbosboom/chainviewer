<?php

declare(strict_types=1);

namespace ChainViewer\Database;

use PDO;

interface MigrationInterface
{
    public function up(PDO $pdo): void;

    public function down(PDO $pdo): void;
}