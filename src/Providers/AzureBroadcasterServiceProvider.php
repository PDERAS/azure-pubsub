<?php

namespace Pderas\AzurePubSub\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Broadcasting\BroadcastManager;
use Pderas\AzurePubSub\Broadcasting\AzureBroadcaster;

class AzureBroadcasterServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap the application services.
     */
    public function boot(BroadcastManager $manager): void
    {
        $this->loadRoutesFrom(__DIR__.'/../../routes/pubsub.php');

        $manager->extend('azure-broadcaster', function ($app, $config) {
            return new AzureBroadcaster($config);
        });
    }
}
