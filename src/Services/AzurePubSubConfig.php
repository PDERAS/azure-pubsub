<?php

namespace Pderas\AzurePubSub\Services;

class AzurePubSubConfig
{
    /**
     * Managed Identity authentication method
     */
    public const MANAGED_AUTH = 'managed';

    /**
     * Key-based authentication method
     */
    public const KEY_AUTH = 'key';

    /**
     * The API version
     */
    private static $api_version = '2024-01-01';

    /**
     * Get the Azure PubSub endpoint.
     */
    public static function getEndpoint(): string
    {
        return config('broadcasting.connections.azure.endpoint');
    }

    /**
     * Get the Azure PubSub key.
     */
    public static function getKey(): string
    {
        return config('broadcasting.connections.azure.key') ?? '';
    }

    /**
     * Get the Azure PubSub authentication method.
     */
    public static function getAuthMethod(): ?string
    {
        return config('broadcasting.connections.azure.auth_method', self::KEY_AUTH);
    }

    /**
     * Get the Azure PubSub expiry.
     */
    public static function getExpiry(): int
    {
        return config('broadcasting.connections.azure.expiry', 3600);
    }

    /**
     * Get the Azure PubSub API version.
     */
    public static function getApiVersion(): string
    {
        return self::$api_version;
    }

    /**
     * Get the Azure PubSub base URL.
     */
    public static function getBaseUrl(): string
    {
        $endpoint = self::getEndpoint();
        return str_replace('https', 'wss', $endpoint);
    }

    /**
     * Get the Azure PubSub URL.
     */
    public static function getUrl($hub): string
    {
        $base_url = self::getBaseUrl();

        return "{$base_url}/client/hubs/{$hub}";
    }
}
