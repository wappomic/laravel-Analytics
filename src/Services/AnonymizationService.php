<?php

namespace Wappomic\Analytics\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class AnonymizationService
{
    protected array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function anonymizeIp(string $ip): string
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return $this->anonymizeIpv6($ip);
        }

        return $this->anonymizeIpv4($ip);
    }

    public function hashUserAgent(string $userAgent): string
    {
        if (empty($userAgent)) {
            return '';
        }

        return hash('sha256', $userAgent);
    }

    public function getBrowserFamily(string $userAgent): ?string
    {
        if (empty($userAgent)) {
            return null;
        }

        $patterns = [
            'Chrome' => '/Chrome\/[0-9.]+/',
            'Firefox' => '/Firefox\/[0-9.]+/',
            'Safari' => '/Safari\/[0-9.]+/',
            'Edge' => '/Edg\/[0-9.]+/',
            'Opera' => '/Opera\/[0-9.]+/',
            'Internet Explorer' => '/MSIE [0-9.]+/',
        ];

        foreach ($patterns as $browser => $pattern) {
            if (preg_match($pattern, $userAgent)) {
                return $browser;
            }
        }

        return 'Unknown';
    }

    public function getDeviceType(string $userAgent): ?string
    {
        if (empty($userAgent)) {
            return null;
        }

        $userAgent = strtolower($userAgent);

        if (preg_match('/mobile|android|iphone|ipod|blackberry|windows phone/', $userAgent)) {
            return 'mobile';
        }

        if (preg_match('/tablet|ipad/', $userAgent)) {
            return 'tablet';
        }

        return 'desktop';
    }

    public function getCountryCode(string $anonymizedIp): ?string
    {
        try {
            $geoData = $this->getGeoData($anonymizedIp);
            return $geoData['country_code'] ?? null;
        } catch (\Exception $e) {
            return null;
        }
    }

    public function roundTimestamp(Carbon $timestamp): Carbon
    {
        return $timestamp->startOfHour();
    }

    protected function anonymizeIpv4(string $ip): string
    {
        $parts = explode('.', $ip);
        
        if (count($parts) !== 4) {
            return '0.0.0.0';
        }

        // Always use 255.255.255.0 mask (anonymize last octet)
        $parts[3] = '0';

        return implode('.', $parts);
    }

    protected function anonymizeIpv6(string $ip): string
    {
        $parts = explode(':', $ip);
        
        for ($i = 4; $i < count($parts); $i++) {
            $parts[$i] = '0';
        }

        return implode(':', $parts);
    }

    protected function getGeoData(string $ip): array
    {
        static $cache = [];

        if (isset($cache[$ip])) {
            return $cache[$ip];
        }

        if ($ip === '0.0.0.0' || $this->isPrivateIp($ip)) {
            return $cache[$ip] = ['country_code' => null];
        }

        try {
            $response = Http::timeout(5)->get("http://ip-api.com/json/{$ip}?fields=status,countryCode");
            
            if ($response->successful()) {
                $data = $response->json();
                
                if ($data['status'] === 'success') {
                    return $cache[$ip] = [
                        'country_code' => $data['countryCode'] ?? null,
                    ];
                }
            }
        } catch (\Exception $e) {
            // Fallback: Return null values for geo data on API failure
        }

        return $cache[$ip] = ['country_code' => null];
    }

    protected function isPrivateIp(string $ip): bool
    {
        return !filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );
    }
}