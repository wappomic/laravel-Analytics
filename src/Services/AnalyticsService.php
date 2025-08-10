<?php

namespace Wappomic\Analytics\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Queue;
use Wappomic\Analytics\Jobs\SendAnalyticsJob;

class AnalyticsService
{
    protected array $config;
    protected ApiClient $apiClient;
    protected AnonymizationService $anonymizationService;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->apiClient = new ApiClient($config);
        $this->anonymizationService = new AnonymizationService($config);
    }

    public function track(array $data): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        $processedData = $this->processTrackingData($data);
        
        if ($this->config['queue_enabled'] ?? true) {
            $this->queueData($processedData);
        } else {
            $this->sendToApi($processedData);
        }
    }

    public function sendToApi(array $data): bool
    {
        return $this->apiClient->send($data);
    }

    public function isEnabled(): bool
    {
        return $this->config['enabled'] ?? true;
    }

    public function isConfigured(): bool
    {
        return $this->apiClient->isConfigured();
    }

    public function validateConfig(): array
    {
        return $this->apiClient->validateConfig();
    }

    public function testConnection(): bool
    {
        return $this->apiClient->testConnection();
    }

    protected function processTrackingData(array $data): array
    {
        $timestamp = $this->anonymizationService->roundTimestamp(now());
        
        $payload = [
            'api_key' => $this->config['api_key'],
            'timestamp' => $timestamp->toISOString(),
            'url' => $this->cleanUrl($data['url'] ?? ''),
            'referrer' => $this->cleanReferrer($data['referrer'] ?? null),
            'anonymized_ip' => $this->anonymizationService->anonymizeIp($data['ip'] ?? ''),
            'browser' => $this->anonymizationService->getBrowserFamily($data['user_agent'] ?? ''),
            'device' => $this->anonymizationService->getDeviceType($data['user_agent'] ?? ''),
            'country' => $this->anonymizationService->getCountryCode($data['ip'] ?? ''),
            'custom_data' => $data['custom_data'] ?? null,
        ];

        // Add session tracking data if available
        if (isset($data['session_data'])) {
            $payload['session_hash'] = $data['session_data']['session_hash'];
            $payload['is_new_session'] = $data['session_data']['is_new_session'];
            $payload['pageview_count'] = $data['session_data']['pageview_count'];
            $payload['session_duration'] = max(0, $data['session_data']['session_duration'] ?? 0);
        }

        // Add app_name if configured (useful for multi-app setups)
        if (!empty($this->config['app_name'])) {
            $payload['app_name'] = $this->config['app_name'];
        }

        return $payload;
    }

    protected function queueData(array $data): void
    {
        // Pass config to job to avoid config loading issues in queue context
        SendAnalyticsJob::dispatch($data, $this->config)
            ->onConnection($this->config['queue_connection'] ?? 'redis')
            ->onQueue($this->config['queue_name'] ?? 'analytics');
    }

    protected function cleanUrl(string $url): string
    {
        if (empty($url)) {
            return '/';
        }

        $parsed = parse_url($url);
        
        // Only return path, no query strings for privacy
        return $parsed['path'] ?? '/';
    }

    protected function cleanReferrer(?string $referrer): ?string
    {
        if (empty($referrer)) {
            return null;
        }

        $parsed = parse_url($referrer);
        
        if (!isset($parsed['host'])) {
            return null;
        }

        // Return only the domain, not the full URL for privacy
        return $parsed['scheme'] . '://' . $parsed['host'];
    }
}