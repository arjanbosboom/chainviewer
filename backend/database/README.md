# Database Migrations

This directory contains the Phase 1 database schema and the minimal migration runner used to evolve it.

## Layout

- `migrations/` stores timestamped PHP migration classes.
- `migrate.php` runs pending migrations against the configured database.
- `src/Database/` contains the migration runner and supporting classes.

## Convention

Each migration must implement the shared migration interface and define:

- `up(PDO $pdo): void` for applying schema changes
- `down(PDO $pdo): void` for rolling them back

The first migration creates the base multi-chain explorer schema, including `chains` and chain-scoped explorer tables.