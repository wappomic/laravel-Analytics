<?php

namespace Wappomic\Analytics\Services;

use Illuminate\Support\Facades\Cache;

class SessionTrackingService
{
    protected array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function generateSessionHash(string $ip, ?string $userAgent): string
    {
        $salt = $this->getDailySalt();
        
        return hash('sha256', 
            $this->anonymizeIp($ip) . 
            $this->cleanUserAgent($userAgent) . 
            $salt
        );
    }

    public function trackSession(string $sessionHash): array
    {
        $cacheKey = "analytics_session_{$sessionHash}";
        $sessionData = Cache::get($cacheKey, null);

        if ($sessionData === null) {
            // New session
            $sessionData = [
                'created_at' => now()->toISOString(),
                'pageview_count' => 1,
                'last_seen' => now()->toISOString(),
            ];
            
            $isNewSession = true;
        } else {
            // Existing session
            $sessionData['pageview_count']++;
            $sessionData['last_seen'] = now()->toISOString();
            
            $isNewSession = false;
        }

        // Store session with TTL
        Cache::put($cacheKey, $sessionData, $this->getSessionTtl());

        return [
            'session_hash' => $sessionHash,
            'is_new_session' => $isNewSession,
            'pageview_count' => $sessionData['pageview_count'],
            'session_duration' => $isNewSession ? 0 : $this->calculateDuration($sessionData['created_at']),
        ];
    }

    public function isSessionTrackingEnabled(): bool
    {
        return $this->config['session_tracking_enabled'] ?? true;
    }

    public function cleanOldSessions(): int
    {
        // This method would be called by a scheduled command
        // For now, we rely on cache TTL for cleanup
        return 0;
    }

    protected function getDailySalt(): string
    {
        // Use app key + current date as salt (changes daily)
        return hash('sha256', config('app.key') . date('Y-m-d'));
    }

    protected function anonymizeIp(string $ip): string
    {
        // Same anonymization as in AnonymizationService
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            // IPv6: Keep first 4 groups, zero out the rest
            $parts = explode(':', $ip);
            return implode(':', array_slice($parts, 0, 4)) . '::';
        }
        
        // IPv4: Keep first 3 octets, zero out the last
        $parts = explode('.', $ip);
        if (count($parts) === 4) {
            $parts[3] = '0';
            return implode('.', $parts);
        }
        
        return $ip;
    }

    protected function cleanUserAgent(?string $userAgent): string
    {
        if (!$userAgent) {
            return 'unknown';
        }

        // Extract just browser family for consistency
        if (preg_match('/Chrome\/[\d\.]+/i', $userAgent)) {
            return 'Chrome';
        }
        if (preg_match('/Firefox\/[\d\.]+/i', $userAgent)) {
            return 'Firefox';
        }
        if (preg_match('/Safari\/[\d\.]+/i', $userAgent) && !preg_match('/Chrome/i', $userAgent)) {
            return 'Safari';
        }
        if (preg_match('/Edge\/[\d\.]+/i', $userAgent)) {
            return 'Edge';
        }

        return 'Other';
    }

    protected function getSessionTtl(): int
    {
        // Convert hours to minutes for Laravel Cache
        return ($this->config['session_ttl_hours'] ?? 24) * 60;
    }

    protected function calculateDuration(string $createdAt): int
    {
        $created = \Carbon\Carbon::parse($createdAt);
        $duration = $created->diffInMinutes(now());
        
        // Ensure duration is never negative (can happen due to timezone issues)
        return max(0, $duration);
    }
}