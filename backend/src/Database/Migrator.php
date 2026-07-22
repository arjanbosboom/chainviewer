<?php

declare(strict_types=1);

namespace ChainViewer\Database;

use PDO;
use RuntimeException;

final class Migrator
{
    /**
     * The migrator applies pending schema files in filename order.
     */
    public function __construct(
        private readonly PDO $pdo,
        private readonly string $migrationsPath,
    ) {
    }

    /**
     * Apply every migration that has not already been recorded.
     */
    public function run(): void
    {
        // Keep a local record so repeated runs stay idempotent.
        $this->ensureRepositoryTable();

        $appliedMigrations = $this->getAppliedMigrations();
        $migrationFiles = glob($this->migrationsPath . DIRECTORY_SEPARATOR . '*.php') ?: [];

        foreach ($migrationFiles as $migrationFile) {
            $migrationName = basename($migrationFile);

            if (in_array($migrationName, $appliedMigrations, true)) {
                continue;
            }

            $migration = require $migrationFile;

            if (!$migration instanceof MigrationInterface) {
                throw new RuntimeException(sprintf('Migration %s must return an instance of %s.', $migrationName, MigrationInterface::class));
            }

            // Each migration is wrapped in a transaction to avoid partial schema state.
            $this->pdo->beginTransaction();

            try {
                $migration->up($this->pdo);
                $this->recordMigration($migrationName);
                $this->pdo->commit();
            } catch (\Throwable $throwable) {
                if ($this->pdo->inTransaction()) {
                    $this->pdo->rollBack();
                }

                throw $throwable;
            }
        }
    }

    private function ensureRepositoryTable(): void
    {
        // Track which migration files have already been applied.
        $this->pdo->exec(
            <<<SQL
            CREATE TABLE IF NOT EXISTS schema_migrations (
                migration VARCHAR(255) NOT NULL PRIMARY KEY,
                applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL
        );
    }

    /**
     * @return string[]
     */
    private function getAppliedMigrations(): array
    {
        $statement = $this->pdo->query('SELECT migration FROM schema_migrations ORDER BY migration ASC');

        return $statement?->fetchAll(PDO::FETCH_COLUMN) ?: [];
    }

    private function recordMigration(string $migrationName): void
    {
        $statement = $this->pdo->prepare('INSERT INTO schema_migrations (migration) VALUES (:migration)');
        $statement->execute(['migration' => $migrationName]);
    }
}