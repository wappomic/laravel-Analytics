<?php

namespace Wappomic\Analytics\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ApiClient
{
    protected array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function send(array $data): bool
    {
        if (!$this->isConfigured()) {
            if (config('app.debug')) {
                Log::warning('Analytics API not configured. Skipping data send.', [
                    'missing_api_url' => empty($this->config['api_url']),
                    'missing_api_key' => empty($this->config['api_key']),
                ]);
            }
            return false;
        }

        try {
            $response = Http::timeout(10)
                ->retry(3, 1000)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->config['api_key'],
                    'Content-Type' => 'application/json',
                    'User-Agent' => 'Wappomic-Laravel-Analytics/1.0',
                ])
                ->post($this->config['api_url'], $data);

            if ($response->successful()) {
                return true;
            }

            // Log non-successful responses in debug mode
            if (config('app.debug')) {
                Log::warning('Analytics API request failed', [
                    'status' => $response->status(),
                    'response' => $response->body(),
                    'data' => $data,
                ]);
            }

            return false;

        } catch (\Exception $e) {
            if (config('app.debug')) {
                Log::error('Analytics API request exception', [
                    'message' => $e->getMessage(),
                    'data' => $data,
                ]);
            }

            return false;
        }
    }

    public function sendBatch(array $dataArray): array
    {
        $results = [];

        foreach ($dataArray as $key => $data) {
            $results[$key] = $this->send($data);
        }

        return $results;
    }

    public function isConfigured(): bool
    {
        return !empty($this->config['api_url']) && !empty($this->config['api_key']);
    }

    public function validateConfig(): array
    {
        $errors = [];

        if (empty($this->config['api_url'])) {
            $errors[] = 'ANALYTICS_API_URL environment variable is required';
        } elseif (!filter_var($this->config['api_url'], FILTER_VALIDATE_URL)) {
            $errors[] = 'ANALYTICS_API_URL must be a valid URL';
        }

        if (empty($this->config['api_key'])) {
            $errors[] = 'ANALYTICS_API_KEY environment variable is required';
        }


        return $errors;
    }

    public function testConnection(): bool
    {
        if (!$this->isConfigured()) {
            return false;
        }

        try {
            $response = Http::timeout(5)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->config['api_key'],
                    'User-Agent' => 'Wappomic-Laravel-Analytics/1.0',
                ])
                ->head($this->config['api_url']);

            return $response->successful() || $response->status() === 405; // HEAD might not be allowed

        } catch (\Exception $e) {
            return false;
        }
    }
}