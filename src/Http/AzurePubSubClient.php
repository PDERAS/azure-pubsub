<?php

namespace Pderas\AzurePubSub\Http;

use Firebase\JWT\JWT;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Pderas\AzurePubSub\Services\AzurePubSubConfig;

class AzurePubSubClient
{
    /**
     * The API key
     */
    private $config;

    /**
     * The HTTP client
     */
    private $client;

    /**
     * Cache for authentication tokens
     */
    private $token_cache = [];

    public function __construct()
    {
        $this->config = new AzurePubSubConfig();

        $this->client = new Client([
            'http_errors' => false
        ]);
    }

    /**
     * Send a message to a specific group in a hub
     */
    public function sendGroupMessage(string $hub, string $group, $payload): void
    {
        $url = "{$this->config->getEndpoint()}/api/hubs/{$hub}/groups/{$group}/:send?api-version={$this->getApiVersion()}";

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
        $url = "{$this->config->getEndpoint()}/api/health?api-version={$this->getApiVersion()}";

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
        return $this->config->getApiVersion();
    }

    /**
     * Generate a client access token for a specific hub and group.
     */
    public function getClientAccessToken(string $hub, string $group): string
    {
        if ($this->config->getAuthMethod() === AzurePubSubConfig::MANAGED_AUTH) {
            return $this->getClientTokenByManagedIdentity($hub, $group);
        }

        // Default to key-based authentication
        return $this->getClientTokenByKey($hub, $group);
    }

    /**
     * Get the client token using key-based authentication.
     */
    private function getClientTokenByKey(string $hub, string $group): string
    {
        $key = $this->config->getKey();
        if (empty($key)) {
            Log::error('Azure PubSub key is not set in the configuration.');
            return '';
        }

        $exp = $this->config->getExpiry();

        $payload = [
            'iat'   => time(),                      // issued at
            'exp'   => time() + $exp,               // expires at
            'aud'   => $this->config->getUrl($hub), // audience
            'role'  => [
                "webpubsub.joinLeaveGroup.{$group}"
            ]
        ];

        return JWT::encode($payload, $key, 'HS256');
    }

    /**
     * Get the client token using Managed Identity.
     */
    private function getClientTokenByManagedIdentity(string $hub, string $group): string
    {
        $url = "{$this->config->getEndpoint()}/api/hubs/{$hub}/:generateToken?api-version={$this->getApiVersion()}";
        $roles = ["webpubsub.joinLeaveGroup", "webpubsub.sendToGroup"];
        
        // Append to url 
        $url .= '&role=' . implode(',', $roles);
        $url .= '&group=' . $group;

        $client_response = $this->client->post($url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->getAuthToken($url)
            ],
        ]);
        $contents = $client_response->getBody()->getContents();
        $access_code = json_decode($contents, true);

        return $access_code['token'] ?? '';
    }

    /**
     * Get the authentication token for the given URL
     */
    private function getAuthToken($url): string
    {
        $cache_key = md5($url);

        // Check if we have a valid token in the cache
        if (isset($this->token_cache[$cache_key]) && time() < $this->token_cache[$cache_key]['expires_at']) {
            return $this->token_cache[$cache_key]['token'];
        }

        if ($this->config->getAuthMethod() === AzurePubSubConfig::MANAGED_AUTH) {
            $token = $this->getAuthKeyByManagedIdentity();
        } else {
            $token = $this->getAuthTokenByKey($url);
        }

        if (!$token) {
            Log::error('Failed to generate authentication token', [
                'url' => $url,
                'auth_method' => $this->config->getAuthMethod()
            ]);
            return '';
        }

        // Add 30 seconds buffer to the expiry time
        // This is to ensure that the token does not expire while in use
        $expiry_with_buffer = time() + $this->config->getExpiry() - 30;

        $this->token_cache[$cache_key] = [
            'token'      => $token,
            'expires_at' => $expiry_with_buffer,
        ];

        return $token;
    }

    /**
     * Generate a JWT token using the provided key
     */
    private function getAuthTokenByKey(string $url): ?string
    {
        $key = $this->config->getKey();
        if (empty($key)) {
            Log::error('Azure PubSub key is not set in the configuration.');
            return null;
        }

        // Payload required by Azure
        $payload = [
            'iat' => time(),                                // issued at
            'exp' => time() + $this->config->getExpiry(),   // expiry
            'aud' => $url,                                  // audience
        ];

        return JWT::encode($payload, $this->config->getKey(), 'HS256');
    }

    /**
     * Get an access token using Managed Identity
     */
    private function getAuthKeyByManagedIdentity(): ?string
    {
        $url = "http://169.254.169.254/metadata/identity/oauth2/token?api-version=2023-11-15&resource=https://webpubsub.azure.com/";
        $curl = curl_init($url);

        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Metadata: true'
            ],
        ]);

        $response = curl_exec($curl);
        curl_close($curl);
        $response_data = json_decode($response, true);

        if (isset($response_data['access_token'])) {
            $token = $response_data['access_token'];
        } else {
            Log::error('Failed to retrieve access token from Azure metadata service', [
                'response' => $response_data
            ]);
        }

        return $token ?? null;
    }
}
