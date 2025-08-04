<?php

namespace Wappomic\Analytics\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Wappomic\Analytics\Services\AnalyticsService;

class TrackingMiddleware
{
    protected AnalyticsService $analyticsService;

    public function __construct(AnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    public function handle(Request $request, Closure $next): SymfonyResponse
    {
        $response = $next($request);

        if ($this->shouldTrack($request, $response)) {
            try {
                $this->analyticsService->track($this->prepareTrackingData($request));
            } catch (\Exception $e) {
                if (config('app.debug')) {
                    logger('Analytics tracking failed: ' . $e->getMessage(), [
                        'url' => $request->fullUrl(),
                        'exception' => $e,
                    ]);
                }
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

        return true;
    }

    protected function isSuccessfulResponse(SymfonyResponse $response): bool
    {
        return $response->getStatusCode() >= 200 && $response->getStatusCode() < 300;
    }

    protected function isExcludedPath(string $path): bool
    {
        $excludedPaths = [
            '/admin*',
            '/api*',
            '/health*',
            '/robots.txt',
            '/sitemap.xml',
            '*.json',
            '*.xml',
            '*.css',
            '*.js',
            '*.ico',
            '*.png',
            '*.jpg',
            '*.jpeg',
            '*.gif',
            '*.svg',
            '*.woff*',
            '*.ttf',
        ];

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

    protected function prepareTrackingData(Request $request): array
    {
        return [
            'url' => $request->fullUrl(),
            'referrer' => $request->header('referer'),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'method' => $request->method(),
        ];
    }
}