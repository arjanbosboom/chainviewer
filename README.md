# ChainViewer

ChainViewer is an open-source multi-chain blockchain explorer, with Munt as the first supported network.

The project is designed around a chain-agnostic core, per-chain RPC adapters, and a REST API consumed by a responsive web frontend.

## Goals

- Modern explorer UI
- Fast indexed search
- Docker-first development and deployment
- Chain-aware architecture from day one
- Open-source collaboration

## Planned Stack

- PHP 8.4+
- Composer
- Slim Framework
- MariaDB / MySQL
- Nginx
- Docker Compose
- Bootstrap 5

## Initial Repository Structure

```text
backend/                 PHP application
backend/public/          Web entrypoint
backend/src/             Application source
backend/config/          App and chain configuration
docker/nginx/            Nginx configuration
docker/php/              PHP image definition
docs/                    Project documentation
```

## Current Status

This repository currently contains the Phase 1 project structure, development container setup, and open-source baseline files.

## Quick Start

1. Install Docker and Docker Compose.
2. Install Composer locally if you want to manage PHP dependencies outside the container.
3. Copy `backend/config/chains/munt.php` and adjust it for your environment if needed.
4. Start the stack with `docker compose up --build`.

## Open Source

This repository is licensed under the MIT License. See [LICENSE](LICENSE).

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md) for contribution guidelines.