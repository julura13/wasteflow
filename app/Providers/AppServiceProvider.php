<?php

namespace App\Providers;

use App\Notifications\Channels\CommunicatorChannel;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            \App\Repositories\OrderStatusHistoryRepository::class,
            function ($app) {
                return new \App\Repositories\OrderStatusHistoryRepository(
                    $app->make(\App\Models\OrderStatusHistory::class)
                );
            }
        );
    }

    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        Notification::extend('communicator', fn ($app) => $app->make(CommunicatorChannel::class));
    }
}
