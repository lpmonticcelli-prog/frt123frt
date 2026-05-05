<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Contracts\PefGatewayInterface;
use App\Services\Pef\MockPefGateway;

class PefServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PefGatewayInterface::class, function ($app) {
            $driver = config('services.pef.driver', 'mock');

            return match ($driver) {
                'mock' => new MockPefGateway(),
                default => new MockPefGateway(),
            };
        });
    }

    public function boot(): void
    {
        //
    }
}
