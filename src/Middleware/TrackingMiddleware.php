<?php

namespace Wappomic\Analytics\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Wappomic\Analytics\Services\AnalyticsService;
use Wappomic\Analytics\Services\SessionTrackingService;

class TrackingMiddleware
{
    protected AnalyticsService $analyticsService;
    protected SessionTrackingService $sessionTrackingService;

    public function __construct(AnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
        $this->sessionTrackingService = new SessionTrackingService(config('analytics', []));
    }

    public function handle(Request $request, Closure $next): SymfonyResponse
    {
        $requestId = uniqid('analytics_', true);
        $startTime = microtime(true);
        
        // Verbose logging for debugging (only when enabled)
        if (config('analytics.verbose_logging', false)) {
            Log::debug('Analytics middleware triggered', [
                'request_id' => $requestId,
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'user_agent' => $request->userAgent(),
                'ip' => $request->ip(),
                'is_ajax' => $request->ajax(),
                'wants_json' => $request->wantsJson(),
                'headers' => [
                    'x-forwarded-for' => $request->header('x-forwarded-for'),
                    'x-real-ip' => $request->header('x-real-ip'),
                    'x-forwarded-proto' => $request->header('x-forwarded-proto'),
                    'cf-connecting-ip' => $request->header('cf-connecting-ip'),
                ]
            ]);
        }

        $response = $next($request);

        if ($this->shouldTrack($request, $response)) {
            try {
                if (config('analytics.verbose_logging', false)) {
                    Log::debug('Analytics tracking started', [
                        'request_id' => $requestId,
                        'url' => $request->fullUrl(),
                        'response_status' => $response->getStatusCode(),
                    ]);
                }
                
                $this->analyticsService->track($this->prepareTrackingData($request, $requestId));
                
                if (config('analytics.verbose_logging', false)) {
                    Log::debug('Analytics tracking completed', [
                        'request_id' => $requestId,
                        'duration_ms' => round((microtime(true) - $startTime) * 1000, 2),
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Analytics tracking failed', [
                    'request_id' => $requestId,
                    'url' => $request->fullUrl(),
                    'exception' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'duration_ms' => round((microtime(true) - $startTime) * 1000, 2),
                ]);
            }
        } else {
            if (config('analytics.verbose_logging', false)) {
                Log::debug('Analytics tracking skipped', [
                    'request_id' => $requestId,
                    'url' => $request->fullUrl(),
                    'reason' => $this->getSkipReason($request, $response),
                    'response_status' => $response->getStatusCode(),
                ]);
            }
        }

        return $response;
    }

    protected function shouldTrack(Request $request, SymfonyResponse $response): bool
    {
        if (!config('analytics.enabled', true)) {
            return false;
        }

        if (!$this->analyticsService->isConfigured()) {
            return false;
        }

        if (!$this->isSuccessfulResponse($response)) {
            return false;
        }

        if ($this->isExcludedPath($request->path())) {
            return false;
        }

        if ($this->isExcludedUserAgent($request->userAgent())) {
            return false;
        }

        if ($this->isAjaxRequest($request)) {
            return false;
        }

        if ($this->isProductionInternalRequest($request)) {
            return false;
        }

        // Request deduplication to prevent multiple tracking of same request
        if ($this->isDuplicateRequest($request)) {
            return false;
        }

        return true;
    }

    protected function isSuccessfulResponse(SymfonyResponse $response): bool
    {
        return $response->getStatusCode() >= 200 && $response->getStatusCode() < 300;
    }

    protected function isExcludedPath(string $path): bool
    {
        $excludedPaths = config('analytics.excluded_routes', []);

        foreach ($excludedPaths as $pattern) {
            if (fnmatch($pattern, $path)) {
                return true;
            }
        }

        return false;
    }

    protected function isExcludedUserAgent(?string $userAgent): bool
    {
        if (!$userAgent) {
            return false;
        }

        $excludedAgents = [
            '*bot*',
            '*crawler*',
            '*spider*',
            '*scraper*',
            'Googlebot',
            'Bingbot',
            'YandexBot',
            'facebookexternalhit',
            'Twitterbot',
            'WhatsApp',
        ];

        foreach ($excludedAgents as $pattern) {
            if (fnmatch(strtolower($pattern), strtolower($userAgent))) {
                return true;
            }
        }

        return false;
    }

    protected function isAjaxRequest(Request $request): bool
    {
        return $request->ajax() || $request->wantsJson() || $request->expectsJson();
    }

    protected function prepareTrackingData(Request $request, string $requestId): array
    {
        $trackingData = [
            'url' => $request->fullUrl(),
            'referrer' => $request->header('referer'),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'method' => $request->method(),
            'request_id' => $requestId,
        ];

        // Add session tracking data if enabled
        if ($this->sessionTrackingService->isSessionTrackingEnabled()) {
            $sessionHash = $this->sessionTrackingService->generateSessionHash(
                $request->ip(),
                $request->userAgent()
            );
            
            $sessionData = $this->sessionTrackingService->trackSession($sessionHash);
            $trackingData['session_data'] = $sessionData;
        }

        return $trackingData;
    }

    protected function isProductionInternalRequest(Request $request): bool
    {
        // Load balancer health checks
        $healthCheckUserAgents = [
            'ELB-HealthChecker*',
            'Amazon*',
            'kube-probe*',
            'Go-http-client*', // Common for k8s health checks
            'nginx*',
            'apache*',
            '*healthcheck*',
            '*monitor*',
            '*check*',
            'Forge*', // Laravel Forge health checks
        ];

        $userAgent = $request->userAgent();
        if ($userAgent) {
            foreach ($healthCheckUserAgents as $pattern) {
                if (fnmatch(strtolower($pattern), strtolower($userAgent))) {
                    return true;
                }
            }
        }

        // Internal IP ranges (production load balancers)
        $clientIp = $request->ip();
        $internalIpRanges = [
            '10.0.0.0/8',     // RFC 1918 private networks
            '172.16.0.0/12',  // RFC 1918 private networks  
            '192.168.0.0/16', // RFC 1918 private networks
            '127.0.0.0/8',    // Localhost
            '::1',            // IPv6 localhost
        ];

        foreach ($internalIpRanges as $range) {
            if ($this->ipInRange($clientIp, $range)) {
                return true;
            }
        }

        // Load balancer forwarded headers without real client IP
        $forwardedFor = $request->header('x-forwarded-for');
        if (empty($forwardedFor) && $request->header('x-forwarded-proto')) {
            return true; // Likely internal request through load balancer
        }

        // No referrer and direct IP access (common for health checks)
        if (!$request->header('referer') && filter_var($request->getHost(), FILTER_VALIDATE_IP)) {
            return true;
        }

        return false;
    }

    protected function ipInRange(string $ip, string $cidr): bool
    {
        if (str_contains($cidr, '/')) {
            [$subnet, $mask] = explode('/', $cidr);
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) && 
                filter_var($subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                return (ip2long($ip) & (~((1 << (32 - $mask)) - 1))) === ip2long($subnet);
            }
        }
        return $ip === $cidr;
    }

    protected function isDuplicateRequest(Request $request): bool
    {
        // Generate request signature for deduplication
        $signature = $this->generateRequestSignature($request);
        $cacheKey = 'analytics_dedup:' . $signature;
        
        // Check if we've already processed this request in the last 60 seconds
        if (Cache::has($cacheKey)) {
            if (config('analytics.verbose_logging', false)) {
                Log::debug('Analytics duplicate request detected', [
                    'url' => $request->fullUrl(),
                    'signature' => $signature,
                    'cache_key' => $cacheKey,
                ]);
            }
            return true;
        }
        
        // Mark this request as processed
        Cache::put($cacheKey, true, 60); // TTL: 60 seconds
        
        return false;
    }

    protected function generateRequestSignature(Request $request): string
    {
        // Create unique signature based on request characteristics
        $components = [
            $request->fullUrl(),
            $request->ip(),
            $request->userAgent(),
            $request->method(),
            // Round timestamp to nearest 10 seconds to group rapid requests
            floor(time() / 10) * 10
        ];
        
        return hash('sha256', implode('|', array_filter($components)));
    }

    protected function getSkipReason(Request $request, SymfonyResponse $response): string
    {
        if (!config('analytics.enabled', true)) {
            return 'analytics_disabled';
        }

        if (!$this->analyticsService->isConfigured()) {
            return 'not_configured';
        }

        if (!$this->isSuccessfulResponse($response)) {
            return 'non_successful_response_' . $response->getStatusCode();
        }

        if ($this->isExcludedPath($request->path())) {
            return 'excluded_path';
        }

        if ($this->isExcludedUserAgent($request->userAgent())) {
            return 'excluded_user_agent';
        }

        if ($this->isAjaxRequest($request)) {
            return 'ajax_request';
        }

        if ($this->isProductionInternalRequest($request)) {
            return 'production_internal_request';
        }

        if ($this->isDuplicateRequest($request)) {
            return 'duplicate_request';
        }

        return 'unknown';
    }
}