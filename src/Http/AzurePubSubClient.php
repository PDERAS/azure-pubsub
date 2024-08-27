<?php

namespace Pderas\AzurePubSub\Http;

use Firebase\JWT\JWT;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class AzurePubSubClient
{
    /**
     * The API version
     */
    private static $api_version = '2024-01-01';

    /**
     * The API key
     */
    private $key;

    /**
     * The API endpoint
     */
    private $endpoint;

    /**
     * The HTTP client
     */
    private $client;

    /**
     * Cache for authentication tokens
     */
    private $token_cache = [];

    public function __construct($key, $endpoint)
    {
        $this->key = $key;
        $this->endpoint = $endpoint;
        $this->client = new Client([
            'http_errors' => false
        ]);
    }

    public function sendGroupMessage(string $hub, string $group, $payload): void
    {
        $url = "{$this->endpoint}/api/hubs/{$hub}/groups/{$group}/:send?api-version={$this->getApiVersion()}";

        \Log::info('Sending message to ' . $url);

        $this->client->post($url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->getAuthToken($url)
            ],
            'json' => $payload
        ]);
    }

    /**
     * Perform a health check on the API
     */
    public function checkApiHealth(): bool
    {
        $url = "{$this->endpoint}/api/health?api-version={$this->getApiVersion()}";

        $response = $this->client->get($url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->getAuthToken($url)
            ]
        ]);

        return $response->getStatusCode() === 200;
    }

    /**
     * Get the API version
     */
    protected function getApiVersion(): string
    {
        return self::$api_version;
    }

    /**
     * Get the authentication token for the given URL
     */
    protected function getAuthToken($url)
    {
        $cache_key = md5($url);

        // Check if we have a valid token in the cache
        if (isset($this->token_cache[$cache_key]) && time() < $this->token_cache[$cache_key]['expires_at']) {
            return $this->token_cache[$cache_key]['token'];
        }

        // Payload required by Azure
        $payload = [
            'iat' => time(),        // issued at
            'exp' => time() + 60,   // expiry
            'aud' => $url,          // audience
        ];

        $token = JWT::encode($payload, $this->key, 'HS256');

        $this->token_cache[$cache_key] = [
            'token'      => $token,
            'expires_at' => $payload['exp']
        ];

        return $token;
    }
}
