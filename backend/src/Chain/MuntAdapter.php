<?php

declare(strict_types=1);

namespace ChainViewer\Chain;

final class MuntAdapter implements \ChainViewer\Chain\ChainAdapterInterface
{
    /**
     * Store the raw chain configuration so the adapter can remain thin.
     *
     * @param array<string, mixed> $configuration
     */
    public function __construct(private readonly array $configuration)
    {
    }

    /**
     * Create a Munt adapter from the parsed chain configuration file.
     *
     * @param array<string, mixed> $configuration
     */
    public static function fromConfiguration(array $configuration): self
    {
        return new self($configuration);
    }

    public function getChainId(): string
    {
        return (string) ($this->configuration['chain_id'] ?? 'munt');
    }

    public function getDisplayName(): string
    {
        return (string) ($this->configuration['display_name'] ?? 'Munt');
    }

    public function getRpcClient(): \ChainViewer\Rpc\JsonRpcClient
    {
        $rpcConfiguration = $this->configuration['rpc'] ?? [];

        // The adapter owns the connection details for its specific chain.
        return new \ChainViewer\Rpc\JsonRpcClient(
            (string) ($rpcConfiguration['scheme'] ?? 'http'),
            (string) ($rpcConfiguration['host'] ?? 'localhost'),
            (int) ($rpcConfiguration['port'] ?? 11981),
            (string) ($rpcConfiguration['username'] ?? ''),
            (string) ($rpcConfiguration['password'] ?? ''),
        );
    }

    /**
     * Feature flags let the shared application adapt to chain capabilities.
     *
     * @return array<string, bool>
     */
    public function getFeatures(): array
    {
        $features = $this->configuration['features'] ?? [];

        return array_map(static fn ($value): bool => (bool) $value, $features);
    }

    /**
     * Small convenience wrapper used by the service layer.
     */
    public function supportsFeature(string $feature): bool
    {
        return $this->getFeatures()[$feature] ?? false;
    }

    /**
     * Expose the raw configuration for bootstrap and debugging.
     *
     * @return array<string, mixed>
     */
    public function getConfiguration(): array
    {
        return $this->configuration;
    }
}