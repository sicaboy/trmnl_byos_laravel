<?php

namespace App\Providers;

use App\Http\Middleware\LogRequests;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\ServiceProvider;

class RequestLoggingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $kernel = $this->app->make(Kernel::class);
        $kernel->pushMiddleware(LogRequests::class);
    }
}