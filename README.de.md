# Wappomic Laravel Analytics v1

[![en](https://img.shields.io/badge/lang-en-blue.svg)](README.md)
[![de](https://img.shields.io/badge/lang-de-green.svg)](README.de.md)


**Cookie-freies, DSGVO-konformes Analytics Package für Laravel**

Sammelt anonymisierte Website-Daten und sendet sie an Ihre eigene API. Keine Cookies, kein Banner - einfach installieren und loslegen.

## 🎯 Features

- 🍪 **Cookie-frei** - Keine Einwilligung erforderlich
- 🔒 **DSGVO-konform** - Sofortige Anonymisierung aller Daten  
- 🌐 **API-basiert** - Sendet Daten an Ihre eigene Analytics-API
- ⚡ **Performance** - < 2ms Overhead, asynchrone Verarbeitung
- 🎛️ **Multi-App Support** - Ein Dashboard für mehrere Apps/Websites
- 🔧 **Plug & Play** - Automatisches Tracking nach Installation
- 🔄 **Session Tracking** - Cookie-freie Unique-Visitor-Erkennung

## 📦 Installation

```bash
composer require wappomic/laravel-analytics
php artisan vendor:publish --tag=analytics-config
```

### .env Konfiguration

```env
# ERFORDERLICH
ANALYTICS_API_URL=https://your-dashboard.com/api/analytics
ANALYTICS_API_KEY=your-unique-app-key-12345

# OPTIONAL  
ANALYTICS_APP_NAME="My Laravel Shop"
ANALYTICS_ENABLED=true
ANALYTICS_QUEUE_ENABLED=true
ANALYTICS_QUEUE_CONNECTION=redis
ANALYTICS_QUEUE_NAME=analytics
ANALYTICS_SESSION_TRACKING_ENABLED=true
ANALYTICS_SESSION_TTL_HOURS=24
ANALYTICS_QUEUE_CONNECTION=redis
ANALYTICS_QUEUE_NAME=analytics
```

Das war's! 🎉 Das Package trackt jetzt automatisch alle Web-Requests.

## 📝 Changelog

Alle wichtigen Änderungen in diesem Projekt werden in der [CHANGELOG.md](CHANGELOG.md) dokumentiert.

### 🆕 Aktuelle Version: v1.0.3
- 🚀 **Präzisions-Upgrade:** Session-Dauer jetzt in Sekunden (war auf Minuten gerundet)
- 📊 **Daten-Wiederherstellung:** Sessions unter 60s gehen nicht mehr verloren (war 0, jetzt präzise)
- ✅ **Erweiterte Tests:** 5 neue Präzisions-Tests + 7 bestehende Session-Tests
- 🎯 **Bessere Analytics:** Genauere Session-Dauer-Daten für API-Consumer

[→ Vollständigen Changelog anzeigen](CHANGELOG.md)

## 🚫 Ausgeschlossene Routen Konfiguration

Standardmäßig werden bestimmte Routen automatisch vom Tracking ausgeschlossen (Admin-Seiten, APIs, statische Dateien, etc.). Sie können diese Liste anpassen oder Ausschlüsse komplett deaktivieren.

### Standard Ausgeschlossene Routen

```php
// config/analytics.php
'excluded_routes' => [
    '/admin*',
    '/api*',
    '/broadcasting*',  // 🎯 Löst Laravel Broadcasting Auth-Probleme
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
],
```

### Anpassungs-Beispiele

```php
// Alles tracken (keine Ausschlüsse)
'excluded_routes' => [],

// Nur bestimmte Routen ausschließen
'excluded_routes' => ['/admin*', '/broadcasting*'],

// Eigene Routen zu den Standards hinzufügen
'excluded_routes' => [
    '/admin*',
    '/api*',
    '/broadcasting*',
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
    '/mein-privater-bereich*',  // Ihre eigenen Ausschlüsse
    '/interne-api/*',
    '*.pdf',
],
```

### Wildcard-Patterns

Die ausgeschlossenen Routen unterstützen Wildcard-Patterns mit `fnmatch()`:

| Pattern | Entspricht | Beispiele |
|---------|------------|----------|
| `/admin*` | Routen die mit `/admin` beginnen | `/admin`, `/admin/users`, `/admin/dashboard/settings` |
| `*.json` | Routen die mit `.json` enden | `/data.json`, `/api/users.json` |
| `/api/*` | Routen unter `/api/` | `/api/users`, `/api/v1/posts` |
| `*broadcasting*` | Routen die `broadcasting` enthalten | `/broadcasting/auth`, `/laravel/broadcasting/auth` |

### Häufige Anwendungsfälle

**Laravel Broadcasting (WebSocket Auth)**:
```php
'excluded_routes' => ['/broadcasting*'],
// Verhindert Tracking von '/broadcasting/auth' Routen
```

**SPA-Anwendungen**:
```php
'excluded_routes' => ['/api*', '*.json'],
// Nur Seitenaufrufe tracken, keine API-Calls
```

**Alles tracken**:
```php
'excluded_routes' => [],
// Keine automatischen Ausschlüsse - alle Routen tracken
```

### Kombination mit Route-Middleware

Sie haben zwei Optionen um Routen auszuschließen:

```php
// Option 1: Globale Config (empfohlen)
'excluded_routes' => ['/admin*', '/broadcasting*'],

// Option 2: Pro Route
Route::get('/admin', AdminController::class)->withoutMiddleware('analytics.tracking');
```

## 📊 Daten-Format

Ihre API erhält POST-Requests mit diesem JSON-Payload:

```json
{
  "api_key": "your-unique-app-key-12345",
  "app_name": "My Laravel Shop",
  "timestamp": "2025-08-04T14:00:00Z",
  "url": "/products/laptop",
  "referrer": "https://google.com",
  "anonymized_ip": "192.168.1.0",
  "browser": "Chrome", 
  "device": "desktop",
  "country": "DE",
  "session_hash": "abc123def456789abcdef123456789abc",
  "is_new_session": true,
  "pageview_count": 1,
  "session_duration": 0,
  "custom_data": null
}
```

### 🔑 Multi-App Setup (Empfohlen)

**Ein Dashboard für alle Ihre Apps:**

```env
# App 1: Online Shop
ANALYTICS_API_KEY=shop-key-abc123
ANALYTICS_APP_NAME="Online Shop"

# App 2: Blog  
ANALYTICS_API_KEY=blog-key-def456
ANALYTICS_APP_NAME="Tech Blog"

# App 3: Landing Page
ANALYTICS_API_KEY=landing-key-ghi789
ANALYTICS_APP_NAME="Product Landing"
```

Alle Apps senden an die gleiche `ANALYTICS_API_URL`, aber mit unterschiedlichen `api_key` - so können Sie die Daten perfekt zuordnen.

## 🛠️ Analytics-Dashboard Implementation

### 1. API-Endpoint erstellen

```php
// routes/api.php
Route::post('/analytics', [AnalyticsController::class, 'store']);
```

```php
// app/Http/Controllers/AnalyticsController.php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AnalyticsData;
use App\Models\App;

class AnalyticsController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'api_key' => 'required|string',
            'app_name' => 'nullable|string',
            'timestamp' => 'required|date',
            'url' => 'required|string',
            'referrer' => 'nullable|string',
            'anonymized_ip' => 'required|string',
            'browser' => 'nullable|string',
            'device' => 'nullable|string',
            'country' => 'nullable|string|size:2',
            'custom_data' => 'nullable|array',
        ]);

        // App anhand API-Key identifizieren
        $app = App::where('api_key', $data['api_key'])->first();
        
        if (!$app) {
            return response()->json(['error' => 'Invalid API key'], 401);
        }

        // Analytics-Daten speichern
        AnalyticsData::create([
            'app_id' => $app->id,
            'timestamp' => $data['timestamp'],
            'url' => $data['url'],
            'referrer' => $data['referrer'],
            'anonymized_ip' => $data['anonymized_ip'],
            'browser' => $data['browser'],
            'device' => $data['device'],
            'country' => $data['country'],
            'custom_data' => $data['custom_data'],
        ]);

        // App-Name beim ersten Request aktualisieren (optional)
        if ($data['app_name'] && $app->name !== $data['app_name']) {
            $app->update(['name' => $data['app_name']]);
        }

        return response()->json(['status' => 'success']);
    }
}
```

### 2. Datenbank-Schema

```php
// Migration: create_apps_table.php
Schema::create('apps', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('api_key')->unique();
    $table->string('domain')->nullable();
    $table->timestamps();
});

// Migration: create_analytics_data_table.php  
Schema::create('analytics_data', function (Blueprint $table) {
    $table->id();
    $table->foreignId('app_id')->constrained()->onDelete('cascade');
    $table->timestamp('timestamp');
    $table->string('url');
    $table->string('referrer')->nullable();
    $table->string('anonymized_ip');
    $table->string('browser')->nullable();
    $table->string('device')->nullable();
    $table->string('country', 2)->nullable();
    $table->json('custom_data')->nullable();
    $table->timestamps();
    
    $table->index(['app_id', 'timestamp']);
    $table->index(['app_id', 'url']);
});
```

### 3. Models

```php
// app/Models/App.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class App extends Model
{
    protected $fillable = ['name', 'api_key', 'domain'];
    
    public function analyticsData()
    {
        return $this->hasMany(AnalyticsData::class);
    }
    
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($app) {
            if (!$app->api_key) {
                $app->api_key = 'app-' . Str::random(20);
            }
        });
    }
}

// app/Models/AnalyticsData.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnalyticsData extends Model
{
    protected $fillable = [
        'app_id', 'timestamp', 'url', 'referrer', 
        'anonymized_ip', 'browser', 'device', 'country', 'custom_data'
    ];
    
    protected $casts = [
        'timestamp' => 'datetime',
        'custom_data' => 'array',
    ];
    
    public function app()
    {
        return $this->belongsTo(App::class);
    }
}
```

### 4. Dashboard-Controller

```php
// app/Http/Controllers/DashboardController.php
<?php

namespace App\Http\Controllers;

use App\Models\App;

class DashboardController extends Controller
{
    public function index()
    {
        $apps = App::withCount('analyticsData')->get();
        
        return view('dashboard.index', compact('apps'));
    }
    
    public function app(App $app)
    {
        $stats = [
            'total_visits' => $app->analyticsData()->count(),
            'today_visits' => $app->analyticsData()->whereDate('timestamp', today())->count(),
            'top_pages' => $app->analyticsData()
                ->select('url')
                ->selectRaw('COUNT(*) as visits')
                ->groupBy('url')
                ->orderByDesc('visits')
                ->limit(10)
                ->get(),
            'countries' => $app->analyticsData()
                ->select('country')
                ->selectRaw('COUNT(*) as visits')
                ->whereNotNull('country')
                ->groupBy('country')
                ->orderByDesc('visits')
                ->limit(10)
                ->get(),
            'browsers' => $app->analyticsData()
                ->select('browser')  
                ->selectRaw('COUNT(*) as visits')
                ->whereNotNull('browser')
                ->groupBy('browser')
                ->orderByDesc('visits')
                ->get(),
        ];
        
        return view('dashboard.app', compact('app', 'stats'));
    }
}
```

## 🔐 DSGVO-Konformität

### ✅ Warum keine Einwilligung erforderlich ist:

- **Keine Cookies** - Package setzt keine Cookies
- **Sofortige Anonymisierung** - IP wird zu `192.168.1.0` 
- **Keine Nutzer-Verfolgung** - Keine persistente Identifikation
- **Datenminimierung** - Nur notwendige Daten
- **Berechtigtes Interesse** - Art. 6 Abs. 1 lit. f DSGVO
- **Session-Hashing** - Anonyme täglich wechselnde Session-Hashes

### 🛡️ Anonymisierung:

| Original | Anonymisiert |
|----------|-------------|
| `192.168.1.123` | `192.168.1.0` |
| `Mozilla/5.0 Chrome/91.0...` | `Chrome` |
| `2025-08-04 14:23:45` | `2025-08-04 14:00:00` |
| `München, Bayern` | `DE` |
| Session ID | `abc123def456...` (täglich neuer Hash) |

## ⚙️ Erweiterte Nutzung

### Manuelles Tracking

```php
use Wappomic\Analytics\Facades\Analytics;

// Custom Event tracken
Analytics::track([
    'url' => '/newsletter-signup',
    'custom_data' => ['campaign' => 'summer-sale']
]);

// Status prüfen
if (Analytics::isEnabled() && Analytics::isConfigured()) {
    // Analytics läuft
}

// API-Verbindung testen
if (Analytics::testConnection()) {
    echo "✅ API erreichbar";
} else {
    echo "❌ API-Problem - Config prüfen";
}
```

### Middleware manuell steuern

```php
// routes/web.php

// Automatisches Tracking für alle Routes (Standard)
Route::get('/', HomeController::class);

// Tracking für bestimmte Routes deaktivieren
Route::get('/admin', AdminController::class)->withoutMiddleware('analytics.tracking');

// Tracking nur für bestimmte Routes
Route::group(['middleware' => 'analytics.tracking'], function () {
    Route::get('/shop', ShopController::class);
    Route::get('/products', ProductController::class);
});
```

## 🚀 Performance & Monitoring

- **Middleware-Overhead**: < 2ms
- **Asynchron**: Über Laravel Queues (empfohlen)
- **Retry-Logic**: 3x Wiederholung bei API-Fehlern
- **Timeout**: 10 Sekunden
- **Error-Handling**: Logs bei `APP_DEBUG=true`

## 🔧 Troubleshooting

### Keine Daten erhalten?

1. **Config prüfen**:
```bash
php artisan tinker
>>> Analytics::validateConfig()
>>> Analytics::testConnection()
```

2. **Queue läuft?**:
```bash
php artisan queue:work
# Oder temporär deaktivieren:
ANALYTICS_QUEUE_ENABLED=false
```

### Queue-Probleme mit Redis?

Wenn Sie Redis verwenden und die Queue nicht funktioniert:

1. **Redis-Verbindung prüfen**:
```bash
php artisan tinker
>>> Redis::ping()  # Sollte "PONG" zurückgeben
```

2. **Redis-Queue explizit konfigurieren**:
```env
ANALYTICS_QUEUE_CONNECTION=redis
ANALYTICS_QUEUE_NAME=analytics
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

3. **Failed Jobs prüfen**:
```bash
php artisan queue:failed
php artisan queue:retry all  # Failed Jobs wiederholen
```

4. **Queue in Echtzeit überwachen**:
```bash
php artisan queue:work --verbose --tries=3 --timeout=30
```

3. **Logs checken**:
```bash
tail -f storage/logs/laravel.log
```

### API-Debugging

```php
// Ihre Analytics-API sollte zurückgeben:
HTTP/1.1 200 OK
Content-Type: application/json

{"status": "success"}

// Bei Fehlern:
HTTP/1.1 400 Bad Request
{"error": "Invalid data", "details": [...]}
```

## 📈 Nächste Schritte

1. **API-Keys generieren** für Ihre Apps
2. **Dashboard implementieren** mit den Beispielen oben
3. **Charts hinzufügen** (Chart.js, ApexCharts)
4. **Real-time Updates** mit WebSockets
5. **Export-Funktionen** (PDF, Excel)

## 📄 Lizenz

MIT License - Siehe [LICENSE](LICENSE) für Details.

---

**Happy Analytics! 🎉**  
Bei Fragen gerne ein Issue auf GitHub erstellen.

---

## 🇺🇸 English Version

The complete English documentation can be found in the [README.md](README.md) file.

*Die vollständige englische Dokumentation finden Sie in der [README.md](README.md) Datei.*