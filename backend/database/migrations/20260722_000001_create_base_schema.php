<?php

declare(strict_types=1);

use ChainViewer\Database\MigrationInterface;

return new class () implements MigrationInterface {
    /**
     * Build the initial chain-aware explorer schema.
     */
    public function up(PDO $pdo): void
    {
        // One row per supported coin keeps each network isolated.
        $pdo->exec(
            <<<SQL
            CREATE TABLE IF NOT EXISTS chains (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                chain_key VARCHAR(64) NOT NULL,
                display_name VARCHAR(128) NOT NULL,
                network_name VARCHAR(128) NULL,
                rpc_scheme VARCHAR(16) NOT NULL DEFAULT 'http',
                rpc_host VARCHAR(255) NOT NULL,
                rpc_port INT UNSIGNED NOT NULL,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY chains_chain_key_unique (chain_key)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL
        );

        // Blocks are keyed by chain so multiple networks can share the same database.
        $pdo->exec(
            <<<SQL
            CREATE TABLE IF NOT EXISTS blocks (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                chain_id BIGINT UNSIGNED NOT NULL,
                height BIGINT UNSIGNED NOT NULL,
                hash VARCHAR(128) NOT NULL,
                previous_hash VARCHAR(128) NULL,
                merkle_root VARCHAR(128) NULL,
                version INT NOT NULL,
                size_bytes INT UNSIGNED NOT NULL DEFAULT 0,
                bits VARCHAR(32) NULL,
                nonce BIGINT UNSIGNED NULL,
                block_time DATETIME NOT NULL,
                tx_count INT UNSIGNED NOT NULL DEFAULT 0,
                reward DECIMAL(32, 8) NOT NULL DEFAULT 0,
                fee_total DECIMAL(32, 8) NOT NULL DEFAULT 0,
                confirmations INT UNSIGNED NOT NULL DEFAULT 0,
                is_orphan TINYINT(1) NOT NULL DEFAULT 0,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY blocks_chain_height_unique (chain_id, height),
                UNIQUE KEY blocks_chain_hash_unique (chain_id, hash),
                KEY blocks_chain_time_index (chain_id, block_time),
                CONSTRAINT blocks_chain_id_fk FOREIGN KEY (chain_id) REFERENCES chains (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL
        );

        // Transactions link back to their chain and block for fast lookup.
        $pdo->exec(
            <<<SQL
            CREATE TABLE IF NOT EXISTS transactions (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                chain_id BIGINT UNSIGNED NOT NULL,
                block_id BIGINT UNSIGNED NULL,
                txid VARCHAR(128) NOT NULL,
                version INT NOT NULL DEFAULT 1,
                lock_time BIGINT UNSIGNED NOT NULL DEFAULT 0,
                size_bytes INT UNSIGNED NOT NULL DEFAULT 0,
                weight INT UNSIGNED NOT NULL DEFAULT 0,
                fee DECIMAL(32, 8) NOT NULL DEFAULT 0,
                is_coinbase TINYINT(1) NOT NULL DEFAULT 0,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY transactions_chain_txid_unique (chain_id, txid),
                KEY transactions_chain_block_index (chain_id, block_id),
                CONSTRAINT transactions_chain_id_fk FOREIGN KEY (chain_id) REFERENCES chains (id) ON DELETE CASCADE,
                CONSTRAINT transactions_block_id_fk FOREIGN KEY (block_id) REFERENCES blocks (id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL
        );

        // Inputs and outputs are split so balance and history queries stay simple.
        $pdo->exec(
            <<<SQL
            CREATE TABLE IF NOT EXISTS transaction_inputs (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                chain_id BIGINT UNSIGNED NOT NULL,
                transaction_id BIGINT UNSIGNED NOT NULL,
                prev_txid VARCHAR(128) NULL,
                prev_vout INT UNSIGNED NULL,
                address VARCHAR(255) NULL,
                value DECIMAL(32, 8) NOT NULL DEFAULT 0,
                script_sig LONGTEXT NULL,
                sequence_number BIGINT UNSIGNED NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                KEY transaction_inputs_chain_tx_index (chain_id, transaction_id),
                CONSTRAINT transaction_inputs_chain_id_fk FOREIGN KEY (chain_id) REFERENCES chains (id) ON DELETE CASCADE,
                CONSTRAINT transaction_inputs_transaction_id_fk FOREIGN KEY (transaction_id) REFERENCES transactions (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL
        );

        $pdo->exec(
            <<<SQL
            CREATE TABLE IF NOT EXISTS transaction_outputs (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                chain_id BIGINT UNSIGNED NOT NULL,
                transaction_id BIGINT UNSIGNED NOT NULL,
                vout INT UNSIGNED NOT NULL,
                address VARCHAR(255) NULL,
                value DECIMAL(32, 8) NOT NULL DEFAULT 0,
                script_pub_key LONGTEXT NULL,
                is_spent TINYINT(1) NOT NULL DEFAULT 0,
                spent_by_transaction_id BIGINT UNSIGNED NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY transaction_outputs_chain_tx_vout_unique (chain_id, transaction_id, vout),
                KEY transaction_outputs_chain_address_index (chain_id, address),
                CONSTRAINT transaction_outputs_chain_id_fk FOREIGN KEY (chain_id) REFERENCES chains (id) ON DELETE CASCADE,
                CONSTRAINT transaction_outputs_transaction_id_fk FOREIGN KEY (transaction_id) REFERENCES transactions (id) ON DELETE CASCADE,
                CONSTRAINT transaction_outputs_spent_by_fk FOREIGN KEY (spent_by_transaction_id) REFERENCES transactions (id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL
        );

        // Address tables power address pages, balances, and transaction history.
        $pdo->exec(
            <<<SQL
            CREATE TABLE IF NOT EXISTS addresses (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                chain_id BIGINT UNSIGNED NOT NULL,
                address VARCHAR(255) NOT NULL,
                address_type VARCHAR(32) NULL,
                first_seen_at DATETIME NULL,
                last_seen_at DATETIME NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY addresses_chain_address_unique (chain_id, address),
                CONSTRAINT addresses_chain_id_fk FOREIGN KEY (chain_id) REFERENCES chains (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL
        );

        // Cached balances avoid recalculating totals on every request.
        $pdo->exec(
            <<<SQL
            CREATE TABLE IF NOT EXISTS address_balances (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                chain_id BIGINT UNSIGNED NOT NULL,
                address_id BIGINT UNSIGNED NOT NULL,
                confirmed_balance DECIMAL(32, 8) NOT NULL DEFAULT 0,
                unconfirmed_balance DECIMAL(32, 8) NOT NULL DEFAULT 0,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY address_balances_chain_address_unique (chain_id, address_id),
                CONSTRAINT address_balances_chain_id_fk FOREIGN KEY (chain_id) REFERENCES chains (id) ON DELETE CASCADE,
                CONSTRAINT address_balances_address_id_fk FOREIGN KEY (address_id) REFERENCES addresses (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL
        );

        // Address transactions provide a fast history view.
        $pdo->exec(
            <<<SQL
            CREATE TABLE IF NOT EXISTS address_transactions (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                chain_id BIGINT UNSIGNED NOT NULL,
                address_id BIGINT UNSIGNED NOT NULL,
                transaction_id BIGINT UNSIGNED NOT NULL,
                direction ENUM('in', 'out', 'self') NOT NULL,
                value DECIMAL(32, 8) NOT NULL DEFAULT 0,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY address_transactions_unique (chain_id, address_id, transaction_id, direction),
                KEY address_transactions_chain_address_index (chain_id, address_id),
                CONSTRAINT address_transactions_chain_id_fk FOREIGN KEY (chain_id) REFERENCES chains (id) ON DELETE CASCADE,
                CONSTRAINT address_transactions_address_id_fk FOREIGN KEY (address_id) REFERENCES addresses (id) ON DELETE CASCADE,
                CONSTRAINT address_transactions_transaction_id_fk FOREIGN KEY (transaction_id) REFERENCES transactions (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL
        );

        // Statistics are stored as key/value pairs so new metrics can be added incrementally.
        $pdo->exec(
            <<<SQL
            CREATE TABLE IF NOT EXISTS statistics (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                chain_id BIGINT UNSIGNED NOT NULL,
                stat_key VARCHAR(128) NOT NULL,
                stat_value LONGTEXT NOT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY statistics_chain_key_unique (chain_id, stat_key),
                CONSTRAINT statistics_chain_id_fk FOREIGN KEY (chain_id) REFERENCES chains (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL
        );

        // Rich list entries are stored separately so ranking updates stay cheap.
        $pdo->exec(
            <<<SQL
            CREATE TABLE IF NOT EXISTS richlist (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                chain_id BIGINT UNSIGNED NOT NULL,
                address_id BIGINT UNSIGNED NOT NULL,
                rank_position INT UNSIGNED NOT NULL,
                balance DECIMAL(32, 8) NOT NULL DEFAULT 0,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY richlist_chain_rank_unique (chain_id, rank_position),
                UNIQUE KEY richlist_chain_address_unique (chain_id, address_id),
                CONSTRAINT richlist_chain_id_fk FOREIGN KEY (chain_id) REFERENCES chains (id) ON DELETE CASCADE,
                CONSTRAINT richlist_address_id_fk FOREIGN KEY (address_id) REFERENCES addresses (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL
        );

        // Network and sync tables allow the UI to show live status for each chain.
        $pdo->exec(
            <<<SQL
            CREATE TABLE IF NOT EXISTS network_status (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                chain_id BIGINT UNSIGNED NOT NULL,
                best_block_height BIGINT UNSIGNED NOT NULL DEFAULT 0,
                best_block_hash VARCHAR(128) NULL,
                difficulty DECIMAL(32, 8) NULL,
                peer_count INT UNSIGNED NOT NULL DEFAULT 0,
                status_payload LONGTEXT NULL,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY network_status_chain_unique (chain_id),
                CONSTRAINT network_status_chain_id_fk FOREIGN KEY (chain_id) REFERENCES chains (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL
        );

        $pdo->exec(
            <<<SQL
            CREATE TABLE IF NOT EXISTS sync_status (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                chain_id BIGINT UNSIGNED NOT NULL,
                last_indexed_height BIGINT UNSIGNED NOT NULL DEFAULT 0,
                last_indexed_hash VARCHAR(128) NULL,
                is_syncing TINYINT(1) NOT NULL DEFAULT 0,
                last_error LONGTEXT NULL,
                last_synced_at DATETIME NULL,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY sync_status_chain_unique (chain_id),
                CONSTRAINT sync_status_chain_id_fk FOREIGN KEY (chain_id) REFERENCES chains (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL
        );
    }

    /**
     * Roll back the initial schema in reverse dependency order.
     */
    public function down(PDO $pdo): void
    {
        $pdo->exec('DROP TABLE IF EXISTS sync_status');
        $pdo->exec('DROP TABLE IF EXISTS network_status');
        $pdo->exec('DROP TABLE IF EXISTS richlist');
        $pdo->exec('DROP TABLE IF EXISTS statistics');
        $pdo->exec('DROP TABLE IF EXISTS address_transactions');
        $pdo->exec('DROP TABLE IF EXISTS address_balances');
        $pdo->exec('DROP TABLE IF EXISTS addresses');
        $pdo->exec('DROP TABLE IF EXISTS transaction_outputs');
        $pdo->exec('DROP TABLE IF EXISTS transaction_inputs');
        $pdo->exec('DROP TABLE IF EXISTS transactions');
        $pdo->exec('DROP TABLE IF EXISTS blocks');
        $pdo->exec('DROP TABLE IF EXISTS chains');
    }
};