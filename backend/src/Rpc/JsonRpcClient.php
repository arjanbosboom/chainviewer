<?php

declare(strict_types=1);

namespace ChainViewer\Rpc;

final class JsonRpcClient
{
    /**
     * The client stays transport-only so chain-specific behavior can live in adapters.
     */
    public function __construct(
        private readonly string $scheme,
        private readonly string $host,
        private readonly int $port,
        private readonly string $username = '',
        private readonly string $password = '',
        private readonly int $timeoutSeconds = 30,
    ) {
    }

    /**
     * Send a JSON-RPC request and return the decoded result payload.
     *
     * The method does not try to interpret chain semantics; it only handles
     * request construction, transport, and basic protocol-level errors.
     *
     * @return mixed
     */
    public function call(string $method, array $params = []): mixed
    {
        // JSON-RPC 2.0 requests require an id, method name, and parameter list.
        $payload = [
            'jsonrpc' => '2.0',
            'id' => bin2hex(random_bytes(8)),
            'method' => $method,
            'params' => array_values($params),
        ];

        $response = $this->send($payload);

        if (array_key_exists('error', $response) && $response['error'] !== null) {
            $message = is_array($response['error']) && array_key_exists('message', $response['error'])
                ? (string) $response['error']['message']
                : 'Unknown JSON-RPC error.';

            throw new JsonRpcException($message);
        }

        return $response['result'] ?? null;
    }

    /**
     * Send the HTTP request and decode the JSON-RPC envelope.
     *
     * @return array<string, mixed>
     */
    private function send(array $payload): array
    {
        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
        ];

        // Many coin daemons use HTTP basic auth for RPC access.
        if ($this->username !== '') {
            $headers[] = 'Authorization: Basic ' . base64_encode($this->username . ':' . $this->password);
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => implode("\r\n", $headers),
                'content' => json_encode($payload, JSON_THROW_ON_ERROR),
                'timeout' => $this->timeoutSeconds,
                'ignore_errors' => true,
            ],
        ]);

        $endpoint = sprintf('%s://%s:%d/', $this->scheme, $this->host, $this->port);
        $responseBody = @file_get_contents($endpoint, false, $context);

        if ($responseBody === false) {
            throw new JsonRpcException(sprintf('Failed to call JSON-RPC endpoint at %s.', $endpoint));
        }

        // The daemon should return a JSON-RPC envelope, not raw text.
        $decodedResponse = json_decode($responseBody, true);

        if (!is_array($decodedResponse)) {
            throw new JsonRpcException('JSON-RPC response was not valid JSON.');
        }

        return $decodedResponse;
    }
}