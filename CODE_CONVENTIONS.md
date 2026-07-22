# Code Conventions

## Goals

The codebase should stay easy to read, easy to review, and easy to extend for additional coins.

## General Rules

- Keep changes small and focused.
- Prefer explicit names over clever shortcuts.
- Keep shared code chain-agnostic unless a chain-specific adapter is required.
- Use comments to explain intent, not to restate obvious syntax.
- Add docblocks when they clarify public methods, array shapes, or important assumptions.

## PHP Rules

- Use `declare(strict_types=1);` in all PHP files.
- Prefer typed properties, typed parameters, and typed return values.
- Use namespaces that reflect the directory structure.
- Keep transport, configuration, and chain logic separated.
- Use transactions for schema and data mutations when partial failure would be harmful.

## PSR-1 Baseline

- Follow PSR-1 as the minimum coding standard for all PHP source files.
- Use `<?php` tags only in PHP files.
- Keep class names in `StudlyCaps`.
- Keep method names in `camelCase`.
- Ensure source files either declare symbols or execute side effects, but not both.

## PSR-12 Formatting Style

- Follow PSR-12 for formatting and code layout across the PHP codebase.
- Use 4 spaces for indentation and never use tabs.
- Keep opening braces on their own line for classes and methods.
- Keep one statement per line.
- Add one blank line after the namespace declaration and between logical code blocks.
- Keep line length reasonable; prefer readability over compactness.
- Keep import (`use`) statements grouped and sorted for clarity.
- Use trailing commas in multiline arrays and argument lists where valid, to reduce diff noise.

## Database Rules

- Every chain-aware table should include a `chain_id` or equivalent foreign key.
- Use unique keys to enforce chain isolation.
- Keep the first migration as the canonical baseline schema.

## Commenting Rules

- Add a short comment when a block of code performs a non-obvious job.
- Use comments to explain why a decision exists, not what the line literally does.
- Avoid redundant comments on self-explanatory lines.

## Naming Rules

- Use descriptive filenames for migrations and service classes.
- Use stable names for chain adapters and keep the chain identifier in configuration.
- Name tables in lowercase plural form.

## Review Checklist

- The code reads cleanly without needing external context.
- The chain-specific behavior is isolated.
- The schema remains safe for multiple networks.
- New comments add value instead of noise.