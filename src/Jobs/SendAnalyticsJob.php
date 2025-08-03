<?php

namespace Wappomic\Analytics\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Wappomic\Analytics\Services\ApiClient;

class SendAnalyticsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $maxExceptions = 1;
    public int $timeout = 30;

    protected array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
        
        $this->onQueue(config('analytics.queue_name', 'analytics'));
    }

    public function handle(): void
    {
        $config = config('analytics');
        $apiClient = new ApiClient($config);
        
        $success = $apiClient->send($this->data);
        
        if (!$success) {
            throw new \Exception('Failed to send analytics data to API');
        }
    }

    public function failed(\Throwable $exception): void
    {
        if (config('app.debug')) {
            logger('Analytics API job failed', [
                'data' => $this->data,
                'exception' => $exception->getMessage(),
            ]);
        }
    }
}