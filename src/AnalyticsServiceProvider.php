<?php

namespace Wappomic\Analytics;

use Illuminate\Support\ServiceProvider;
use Wappomic\Analytics\Middleware\TrackingMiddleware;
use Wappomic\Analytics\Services\AnalyticsService;

class AnalyticsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/analytics.php',
            'analytics'
        );

        $this->app->singleton(AnalyticsService::class, function ($app) {
            return new AnalyticsService(
                $app['config']['analytics']
            );
        });

        $this->app->alias(AnalyticsService::class, 'analytics');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->bootForConsole();
        }

        if (config('analytics.enabled', true)) {
            $this->bootMiddleware();
        }
    }

    protected function bootForConsole(): void
    {
        $this->publishes([
            __DIR__.'/../config/analytics.php' => config_path('analytics.php'),
        ], 'analytics-config');
    }

    protected function bootMiddleware(): void
    {
        $router = $this->app['router'];
        
        $router->aliasMiddleware('analytics.tracking', TrackingMiddleware::class);
        
        $router->pushMiddlewareToGroup('web', TrackingMiddleware::class);
    }

    public function provides(): array
    {
        return [
            AnalyticsService::class,
            'analytics',
        ];
    }
}