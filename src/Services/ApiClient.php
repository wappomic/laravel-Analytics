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
        $requestId = $data['request_id'] ?? uniqid('api_', true);
        
        Log::info('Analytics API request started', [
            'request_id' => $requestId,
            'api_url' => $this->config['api_url'] ?? 'not_set',
            'has_api_key' => !empty($this->config['api_key']),
            'data_size' => strlen(json_encode($data)),
        ]);

        if (!$this->isConfigured()) {
            Log::warning('Analytics API not configured. Skipping data send.', [
                'request_id' => $requestId,
                'missing_api_url' => empty($this->config['api_url']),
                'missing_api_key' => empty($this->config['api_key']),
                'config_dump' => [
                    'api_url' => $this->config['api_url'] ?? 'NULL',
                    'api_key_length' => !empty($this->config['api_key']) ? strlen($this->config['api_key']) : 0,
                ]
            ]);
            return false;
        }

        try {
            $startTime = microtime(true);
            $response = Http::timeout(10)
                ->retry(3, 1000)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->config['api_key'],
                    'Content-Type' => 'application/json',
                    'User-Agent' => 'Wappomic-Laravel-Analytics/1.0',
                ])
                ->post($this->config['api_url'], $data);

            $duration = round((microtime(true) - $startTime) * 1000, 2);

            if ($response->successful()) {
                Log::info('Analytics API request successful', [
                    'request_id' => $requestId,
                    'status' => $response->status(),
                    'duration_ms' => $duration,
                    'response_size' => strlen($response->body()),
                ]);
                return true;
            }

            // Enhanced logging for production debugging
            Log::error('Analytics API request failed', [
                'request_id' => $requestId,
                'status' => $response->status(),
                'duration_ms' => $duration,
                'response_body' => $response->body(),
                'response_headers' => $response->headers(),
                'request_url' => $this->config['api_url'],
                'request_data' => $data,
                'curl_error' => $response->transferStats?->getHandlerErrorData() ?? null,
            ]);

            return false;

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('Analytics API connection failed', [
                'request_id' => $requestId,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'api_url' => $this->config['api_url'],
                'curl_error' => $e->getPrevious()?->getMessage() ?? 'Unknown connection error',
                'data' => $data,
            ]);
            return false;
        } catch (\Illuminate\Http\Client\RequestException $e) {
            Log::error('Analytics API request exception', [
                'request_id' => $requestId,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'response_status' => $e->response?->status() ?? null,
                'response_body' => $e->response?->body() ?? null,
                'api_url' => $this->config['api_url'],
                'data' => $data,
            ]);
            return false;
        } catch (\Exception $e) {
            Log::error('Analytics API unexpected exception', [
                'request_id' => $requestId,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'api_url' => $this->config['api_url'],
                'data' => $data,
            ]);
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