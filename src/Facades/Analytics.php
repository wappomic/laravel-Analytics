<?php

namespace Wappomic\Analytics\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static void track(string $url, array $data = [])
 * @method static void trackEvent(string $event, array $data = [])
 * @method static bool isEnabled()
 * @method static void optOut(string $ip)
 * @method static bool isOptedOut(string $ip)
 * @method static array getVisits(array $filters = [])
 * @method static array getTopPages(int $limit = 10)
 * @method static array getCountryStats()
 * @method static array getDailyVisits(int $days = 30)
 *
 * @see \Wappomic\Analytics\AnalyticsPackage
 */
class Analytics extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'analytics';
    }
}