<?php

declare(strict_types=1);

namespace ChainViewer\Chain;

use ChainViewer\Rpc\JsonRpcClient;

interface ChainAdapterInterface
{
    public function getChainId(): string;

    public function getDisplayName(): string;

    public function getRpcClient(): JsonRpcClient;

    /**
     * @return array<string, bool>
     */
    public function getFeatures(): array;

    public function supportsFeature(string $feature): bool;

    /**
     * @return array<string, mixed>
     */
    public function getConfiguration(): array;
}