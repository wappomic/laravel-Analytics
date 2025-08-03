<?php

namespace Wappomic\Analytics;

use Wappomic\Analytics\Services\AnalyticsService;

class AnalyticsPackage
{
    protected AnalyticsService $service;

    public function __construct(AnalyticsService $service)
    {
        $this->service = $service;
    }

    public function track(string $url, array $customData = []): void
    {
        $this->service->track([
            'url' => $url,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'referrer' => request()->header('referer'),
            'custom_data' => $customData,
        ]);
    }

    public function isEnabled(): bool
    {
        return $this->service->isEnabled();
    }

    public function isConfigured(): bool
    {
        return $this->service->isConfigured();
    }

    public function validateConfig(): array
    {
        return $this->service->validateConfig();
    }

    public function testConnection(): bool
    {
        return $this->service->testConnection();
    }
}