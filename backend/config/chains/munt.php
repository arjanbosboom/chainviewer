<?php

declare(strict_types=1);

return [
    'chain_id' => 'munt',
    'display_name' => 'Munt',
    'rpc' => [
        'scheme' => getenv('MUNT_RPC_SCHEME') ?: 'http',
        'host' => getenv('MUNT_RPC_HOST') ?: 'host.docker.internal',
        'port' => (int) (getenv('MUNT_RPC_PORT') ?: 11981),
        'username' => getenv('MUNT_RPC_USER') ?: '',
        'password' => getenv('MUNT_RPC_PASSWORD') ?: '',
    ],
    'features' => [
        'rich_list' => true,
        'supply_tracking' => true,
    ],
];