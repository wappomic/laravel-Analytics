# Laravel Analytics Package v1

Ein DSGVO-konformes, cookie-freies Analytics Package für Laravel, das Tracking-Daten anonymisiert und an eine externe API sendet.

## Features

- 🍪 **Cookie-frei** - Keine Cookies, kein Banner erforderlich
- 🔒 **DSGVO-konform** - Sofortige Anonymisierung aller Daten
- 🌐 **API-basiert** - Sendet Daten an Ihre eigene API
- ⚡ **Performance-optimiert** - < 2ms Overhead, asynchrone Verarbeitung
- 🎯 **Präzise Daten** - URL, Referrer, Browser, Gerät, Land
- 🔧 **Einfache Integration** - Plug & Play Installation
- 📊 **Eigenes Dashboard** - Bauen Sie Ihr eigenes Analytics-Dashboard

## Systemanforderungen

- PHP 8.2+
- Laravel 12.0+
- Externe API für Analytics-Daten

## Installation

```bash
composer require wappomic/laravel-analytics
```

Das Package wird automatisch über Laravel Package Discovery registriert.

### Konfiguration publizieren

```bash
php artisan vendor:publish --tag=analytics-config
```

### Environment-Variablen

```env
ANALYTICS_ENABLED=true
ANALYTICS_API_URL=https://your-api.com/analytics
ANALYTICS_API_KEY=your-secret-api-key
ANALYTICS_QUEUE_ENABLED=true
```

## API Payload Format

Das Package sendet JSON-Daten in folgendem Format an Ihre API:

```json
{
  "timestamp": "2025-08-03T14:00:00Z",
  "url": "/page-path",
  "referrer": "https://google.com",
  "anonymized_ip": "192.168.1.0",
  "browser": "Chrome",
  "device": "desktop", 
  "country": "DE",
  "custom_data": null
}
```

## Nutzung

### Automatisches Tracking

Das Package trackt automatisch alle Web-Requests. Keine weitere Konfiguration erforderlich.

### Manuelles Tracking

```php
use Wappomic\Analytics\Facades\Analytics;

// Seite manuell tracken
Analytics::track('/special-page', ['button' => 'signup']);

// Status prüfen
if (Analytics::isEnabled()) {
    // Analytics ist aktiviert
}

// Konfiguration validieren
$errors = Analytics::validateConfig();
if (empty($errors)) {
    // Konfiguration ist korrekt
}

// API-Verbindung testen
if (Analytics::testConnection()) {
    // API ist erreichbar
}
```

## Konfiguration

### Basis-Konfiguration

```php
// config/analytics.php

return [
    'enabled' => env('ANALYTICS_ENABLED', true),
    
    // API-Konfiguration (ERFORDERLICH)
    'api_url' => env('ANALYTICS_API_URL'),
    'api_key' => env('ANALYTICS_API_KEY'),
    'api_timeout' => env('ANALYTICS_API_TIMEOUT', 10),
    
    // Automatisches Tracking
    'auto_track' => env('ANALYTICS_AUTO_TRACK', true),
    
    // Queue-Verarbeitung (empfohlen)
    'queue_enabled' => env('ANALYTICS_QUEUE_ENABLED', true),
    'queue_connection' => env('ANALYTICS_QUEUE_CONNECTION', 'default'),
    'queue_name' => env('ANALYTICS_QUEUE_NAME', 'analytics'),
];
```

### Ausgeschlossene Pfade

```php
'excluded_paths' => [
    '/admin*',
    '/api*',
    '/health*',
    '*.css',
    '*.js',
    '*.png',
    // ...
],
```

### Ausgeschlossene User-Agents

```php
'excluded_user_agents' => [
    '*bot*',
    '*crawler*',
    '*spider*',
    'Googlebot',
    'Bingbot',
    // ...
],
```

## DSGVO-Konformität

### Keine Einwilligung erforderlich

- **Keine Cookies** - Das Package setzt keine Cookies
- **Sofortige Anonymisierung** - IP-Adressen werden sofort anonymisiert (`192.168.1.0`)
- **Datenminimierung** - Nur notwendige Daten werden erfasst
- **Keine Cross-Site-Verfolgung** - Keine persistente Nutzer-Identifikation

### Anonymisierung

- **IP-Adressen**: `192.168.1.123` → `192.168.1.0`
- **User-Agent**: `Mozilla/5.0 (...)` → `Chrome`
- **Zeitstempel**: Auf Stunde gerundet
- **Geo-Location**: Nur Land-Code (`DE`), keine Stadt/Region

### Rechtliche Grundlage

Art. 6 Abs. 1 lit. f DSGVO (Berechtigtes Interesse) für Webseitenbetrieb und -optimierung.

## Performance

- **Middleware-Overhead**: < 2ms pro Request
- **Asynchrone Verarbeitung**: Via Laravel Queues
- **Retry-Logic**: Automatische Wiederholung bei API-Fehlern
- **Timeout**: Kurze Timeouts um Performance zu gewährleisten

## API-Implementation

### Endpoint-Anforderungen

Ihre API sollte folgende Anforderungen erfüllen:

- **Method**: `POST`
- **Content-Type**: `application/json`
- **Authorization**: `Bearer {api_key}`
- **Response**: HTTP 200-299 für Erfolg

### Beispiel-Controller (Laravel)

```php
Route::post('/analytics', function (Request $request) {
    $data = $request->validate([
        'timestamp' => 'required|date',
        'url' => 'required|string',
        'referrer' => 'nullable|string',
        'anonymized_ip' => 'required|ip',
        'browser' => 'nullable|string',
        'device' => 'nullable|string',
        'country' => 'nullable|string|size:2',
        'custom_data' => 'nullable|array',
    ]);
    
    // Daten in Ihrer Datenbank speichern
    AnalyticsData::create($data);
    
    return response()->json(['status' => 'success']);
})->middleware('auth:api');
```

## Troubleshooting

### Häufige Probleme

1. **Keine Daten erhalten**
   - Prüfen Sie `ANALYTICS_API_URL` und `ANALYTICS_API_KEY`
   - Testen Sie mit `Analytics::testConnection()`

2. **Queue-Probleme**
   - Stellen Sie sicher, dass Queue-Worker läuft
   - Oder setzen Sie `ANALYTICS_QUEUE_ENABLED=false`

3. **API-Errors**
   - Prüfen Sie Laravel-Logs bei `APP_DEBUG=true`
   - Überprüfen Sie API-Endpoint und Authentication

## Lizenz

MIT License. Siehe [LICENSE](LICENSE) für Details.

## Support

Bei Fragen oder Problemen erstelle bitte ein Issue auf GitHub.