<?php

namespace Pderas\AzurePubSub\Http\Controllers;

use Pderas\AzurePubSub\Http\AzurePubSubClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Pderas\AzurePubSub\Services\AzurePubSubConfig;

class AzurePubSubController
{
    private $config;

    private $client;

    private $token_manager;

    /**
     * Controller constructor with injected config dependency
     */
    public function __construct(AzurePubSubClient $client)
    {
        $this->client = $client;
    }

    /**
     * Negotiate an URL and access token for the given group.
     */
    public function negotiate(Request $request, string $hub, string $group): JsonResponse
    {
        $validator = Validator::make([
            'hub'   => $hub,
            'group' => $group
        ], [
            'hub'   => 'required|regex:/^[a-zA-Z0-9,._`\\\\]+$/',
            'group' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        $access_token = $this->client->getClientAccessToken($hub, $group);

        $full_url = AzurePubSubConfig::getUrl($hub) . '?access_token=' . $access_token;

        return response()->json([
            'url'         => $full_url,
            'accessToken' => $access_token
        ]);
    }
}
