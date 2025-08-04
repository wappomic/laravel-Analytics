<?php

namespace Wappomic\Analytics\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static void track(array $data)
 * @method static bool sendToApi(array $data)
 * @method static bool isEnabled()
 * @method static bool isConfigured()
 * @method static array validateConfig()
 * @method static bool testConnection()
 *
 * @see \Wappomic\Analytics\Services\AnalyticsService
 */
class Analytics extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'analytics';
    }
}