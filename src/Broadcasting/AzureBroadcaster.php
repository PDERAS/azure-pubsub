<?php

namespace Pderas\AzurePubSub\Broadcasting;

use Illuminate\Broadcasting\Broadcasters\Broadcaster;
use Illuminate\Contracts\Broadcasting\Broadcaster as BroadcasterContract;
use Pderas\AzurePubSub\Http\AzurePubSubClient;

class AzureBroadcaster extends Broadcaster implements BroadcasterContract
{
    protected $client;

    public function __construct(array $config)
    {
        $this->client = new AzurePubSubClient(
            $config['key'],
            $config['endpoint'],
        );
    }

    public function auth($request)
    {
        //
    }

    public function validAuthenticationResponse($request, $result)
    {
        //
    }

    public function broadcast(array $channels, $event, array $payload = []): void
    {
        foreach ($channels as $channel) {
            $this->client->sendGroupMessage($channel, $event, $payload);
        }
    }
}
