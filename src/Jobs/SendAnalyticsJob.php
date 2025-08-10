<?php

namespace Wappomic\Analytics\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Wappomic\Analytics\Services\ApiClient;

class SendAnalyticsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2; // Reduced from 3 to prevent spam
    public int $maxExceptions = 1;
    public int $timeout = 30;
    public array $backoff = [5, 15]; // Exponential backoff: 5s, 15s

    protected array $data;
    protected array $config;

    public function __construct(array $data, array $config = [])
    {
        $this->data = $data;
        $this->config = $config ?: config('analytics', []);
    }

    public function handle(): void
    {
        $requestId = $this->data['request_id'] ?? uniqid('job_', true);
        
        if (config('analytics.verbose_logging', false)) {
            Log::debug('Analytics job started', [
                'request_id' => $requestId,
                'attempt' => $this->attempts(),
                'max_tries' => $this->tries,
                'job_id' => $this->job->getJobId() ?? 'unknown',
                'queue' => $this->queue ?? 'default',
                'connection' => $this->connection ?? 'default',
            ]);
        }

        // Validate config in job context
        if (!$this->isValidConfig()) {
            Log::error('Analytics job failed: Invalid configuration', [
                'request_id' => $requestId,
                'config_errors' => $this->getConfigErrors(),
                'attempt' => $this->attempts(),
            ]);
            
            // Don't retry if config is invalid
            $this->fail(new \Exception('Analytics API configuration is invalid'));
            return;
        }

        $apiClient = new ApiClient($this->config);
        
        $success = $apiClient->send($this->data);
        
        if (!$success) {
            $message = 'Failed to send analytics data to API';
            
            Log::error('Analytics job attempt failed', [
                'request_id' => $requestId,
                'attempt' => $this->attempts(),
                'max_tries' => $this->tries,
                'will_retry' => $this->attempts() < $this->tries,
                'next_retry_delay' => $this->backoff[$this->attempts() - 1] ?? 0,
            ]);
            
            throw new \Exception($message);
        }

        Log::info('Analytics job completed successfully', [
            'request_id' => $requestId,
            'attempt' => $this->attempts(),
        ]);
    }

    protected function isValidConfig(): bool
    {
        return !empty($this->config['api_url']) && !empty($this->config['api_key']);
    }

    protected function getConfigErrors(): array
    {
        $errors = [];

        if (empty($this->config['api_url'])) {
            $errors[] = 'ANALYTICS_API_URL is not set or empty';
        } elseif (!filter_var($this->config['api_url'], FILTER_VALIDATE_URL)) {
            $errors[] = 'ANALYTICS_API_URL is not a valid URL: ' . $this->config['api_url'];
        }

        if (empty($this->config['api_key'])) {
            $errors[] = 'ANALYTICS_API_KEY is not set or empty';
        } elseif (strlen($this->config['api_key']) < 10) {
            $errors[] = 'ANALYTICS_API_KEY appears to be too short (less than 10 characters)';
        }

        return $errors;
    }

    public function failed(\Throwable $exception): void
    {
        $requestId = $this->data['request_id'] ?? 'unknown';
        
        Log::error('Analytics job finally failed after all retries', [
            'request_id' => $requestId,
            'exception' => $exception->getMessage(),
            'exception_class' => get_class($exception),
            'data' => $this->data,
            'config_status' => [
                'has_api_url' => !empty($this->config['api_url']),
                'has_api_key' => !empty($this->config['api_key']),
                'api_url' => $this->config['api_url'] ?? 'NOT_SET',
                'api_key_length' => !empty($this->config['api_key']) ? strlen($this->config['api_key']) : 0,
            ],
            'queue_info' => [
                'queue' => $this->queue ?? 'unknown',
                'connection' => $this->connection ?? 'unknown',
                'total_attempts' => $this->attempts(),
                'max_tries' => $this->tries,
            ],
        ]);
    }
}