<?php

namespace Pderas\AzurePubSub\Http\Controllers;

use Firebase\JWT\JWT;
use Pderas\AzurePubSub\Services\AzurePubSubConfig;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AzurePubSubController
{
    private $config;

    /**
     * Controller constructor with injected config dependency
     */
    public function __construct(AzurePubSubConfig $config)
    {
        $this->config = $config;
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
            ], 422); // 422 Unprocessable Entity
        }

        $access_token = $this->getAccessToken($hub, $group);
        $full_url = $this->config->getUrl($hub) . '?access_token=' . $access_token;

        return response()->json([
            'url'         => $full_url,
            'accessToken' => $access_token
        ]);
    }

    /**
     * Get the access token for the given group.
     */
    private function getAccessToken(string $hub, string $group): string
    {
        $key = $this->config->getKey();
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
}
