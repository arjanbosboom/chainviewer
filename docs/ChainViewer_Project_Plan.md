# ChainViewer Project Plan

**Project:** ChainViewer\
**Domain:** chainviewer.org\
**Status:** Planning (July 2026)

------------------------------------------------------------------------

# Vision

ChainViewer will be a modern, open-source multi-chain blockchain
explorer, starting with Munt as the first supported network.

The project is intentionally independent from individual coin core
repositories and deployment repositories. It communicates with supported
daemons exclusively through their JSON-RPC interfaces.

Primary goals:

-   Modern and responsive UI
-   Fast indexed searches
-   Docker-first deployment
-   Easy to maintain
-   API-first architecture
-   Support multiple coins from one codebase
-   Completely open source

------------------------------------------------------------------------

# Related Projects

-   Munt Core (initial integration): https://github.com/muntorg/munt-official
-   Development fork (initial integration): https://github.com/arjanbosboom/munt-official
-   Docker deployment (initial integration): https://github.com/arjanbosboom/munt-node-docker

The first implementation should target Munt, but the system design
should allow additional UTXO-based coins to be added through
configuration and chain-specific adapters.

------------------------------------------------------------------------

# High-Level Architecture

``` text
                Browser
                    │
             Bootstrap UI
                    │
              REST API (PHP)
                    │
           Chain Routing Layer
                    │
         Explorer Service Layer
                    │
          MariaDB / MySQL Database
                    ▲
                    │
         Per-Chain Indexer Workers
                    │
          Chain RPC Adapter Layer
                    │
          Supported Coin Daemons
```

Each supported coin daemon remains the source of truth for its own
chain.

All web requests read from the explorer database.

------------------------------------------------------------------------

# Technology Stack

## Backend

-   PHP 8.4+
-   Composer
-   Lightweight framework (Slim preferred)
-   MariaDB / MySQL

## Frontend

-   Bootstrap 5
-   Vanilla JavaScript
-   Font Awesome Free
-   Chart.js

## Infrastructure

-   Docker
-   Docker Compose
-   Nginx
-   PHP-FPM

------------------------------------------------------------------------

# Core Components

## 1. Blockchain Indexer

The indexer is the heart of the system.

It should consist of a shared indexing engine plus a thin chain adapter
per supported coin.

Responsibilities:

-   Initial sync from genesis
-   Detect new blocks
-   Download block data via JSON-RPC
-   Parse transactions
-   Parse inputs and outputs
-   Maintain balances
-   Maintain address history
-   Calculate fees
-   Calculate supply
-   Maintain statistics
-   Detect orphan blocks
-   Handle chain reorganizations
-   Resume after interruption
-   Keep chain data isolated per supported coin

The indexer should be restart-safe and idempotent.

Chain-specific concerns should be isolated behind adapters, such as:

-   RPC method differences
-   Address encoding differences
-   Genesis and network metadata
-   Reward and supply rules
-   Optional feature support

------------------------------------------------------------------------

## 2. Explorer Database

The database is an indexed representation of the blockchain.

Every chain-aware table should include a `chain_id` or equivalent key,
so one deployment can index multiple coins without data collisions.

Suggested tables:

-   chains
-   blocks
-   transactions
-   transaction_inputs
-   transaction_outputs
-   addresses
-   address_balances
-   address_transactions
-   statistics
-   richlist
-   network_status
-   sync_status

------------------------------------------------------------------------

## 3. REST API

Example endpoints:

       GET /api/chains
       GET /api/{chain}/block/{height}
       GET /api/{chain}/block/{hash}
       GET /api/{chain}/tx/{txid}
       GET /api/{chain}/address/{address}
       GET /api/{chain}/search?q=
       GET /api/{chain}/stats

The frontend should consume only this API.

------------------------------------------------------------------------

# Planned Pages

## Home

-   Chain selector
-   Latest blocks
-   Latest transactions
-   Network height
-   Difficulty
-   Supply
-   Peer count
-   Synchronization status

## Block

-   Header
-   Transactions
-   Confirmations
-   Size
-   Reward

## Transaction

-   Inputs
-   Outputs
-   Fees
-   Confirmations

## Address

-   Balance
-   Received
-   Sent
-   Transaction history

## Statistics

-   Blocks/day
-   Difficulty
-   Supply
-   Transaction volume
-   Rich list

Each page should clearly indicate the active chain and allow switching
between supported coins without changing the overall UI structure.

------------------------------------------------------------------------

# Synchronization

## Initial Sync

Index every block from genesis for each enabled chain.

The process must be resumable.

## Live Sync

Continuously monitor each enabled daemon.

For every new block:

1.  Download block
2.  Verify chain continuity
3.  Index transactions
4.  Update balances
5.  Update statistics
6.  Refresh search indexes

Synchronization state, failure handling, and reorg recovery should be
tracked independently per chain.

------------------------------------------------------------------------

# Design Principles

-   Separation of concerns
-   Read-only daemon interaction
-   API-first
-   Docker-first
-   Clean architecture
-   Modular components
-   Chain-agnostic core with small per-coin adapters
-   Restart-safe indexing
-   High performance
-   Easy to extend

------------------------------------------------------------------------

# Development Roadmap

## Phase 1

-   Project structure
-   Docker environment
-   Database schema
-   Configuration
-   JSON-RPC client
-   Chain registry and adapter interface

## Phase 2

-   Blockchain indexer
-   Initial synchronization
-   Live synchronization
-   Sync state management
-   Munt adapter as reference implementation

## Phase 3

-   REST API
-   Search
-   Block endpoint
-   Transaction endpoint
-   Address endpoint
-   Chain-aware routing

## Phase 4

-   Bootstrap frontend
-   Dashboard
-   Block pages
-   Transaction pages
-   Address pages
-   Statistics
-   Chain selector and active-network context

## Phase 5

-   Charts
-   Rich list
-   Health checks
-   Monitoring
-   CI/CD
-   GHCR images
-   Second coin integration to validate abstraction

------------------------------------------------------------------------

# Future Ideas

-   WebSocket updates
-   Public API documentation
-   GraphQL endpoint
-   Multi-language support
-   Dark mode
-   Prometheus metrics
-   OpenAPI documentation

------------------------------------------------------------------------

# Development Philosophy

Build the project incrementally.

Keep commits small and reviewable.

Prefer maintainability over cleverness.

Design the database and indexing engine before implementing the
frontend.

The indexer should be treated as the central component of the entire
application.
