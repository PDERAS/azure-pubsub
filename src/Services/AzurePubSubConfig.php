<?php

namespace Pderas\AzurePubSub\Services;

class AzurePubSubConfig
{
    /**
     * Get the Azure PubSub endpoint.
     */
    public function getEndpoint(): string
    {
        return config('broadcasting.connections.azure.endpoint');
    }

    /**
     * Get the Azure PubSub key.
     */
    public function getKey(): string
    {
        return config('broadcasting.connections.azure.key');
    }

    /**
     * Get the Azure PubSub expiry.
     */
    public function getExpiry(): int
    {
        return config('broadcasting.connections.azure.expiry', 3600);
    }

    /**
     * Get the Azure PubSub base URL.
     */
    public function getBaseUrl(): string
    {
        $endpoint = $this->getEndpoint();
        return str_replace('https', 'wss', $endpoint);
    }

    /**
     * Get the Azure PubSub URL.
     */
    public function getUrl($hub): string
    {
        $base_url = $this->getBaseUrl();

        return "{$base_url}/client/hubs/{$hub}";
    }
}
