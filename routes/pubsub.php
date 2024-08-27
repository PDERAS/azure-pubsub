<?php

use Illuminate\Support\Facades\Route;
use Pderas\AzurePubSub\Http\Controllers\AzurePubSubController;

Route::get('/negotiate/{hub}/{group}', [AzurePubSubController::class, 'negotiate']);
