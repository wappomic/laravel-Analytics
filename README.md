# Wappomic Laravel Analytics v1

**Cookie-freies, DSGVO-konformes Analytics Package für Laravel**

Sammelt anonymisierte Website-Daten und sendet sie an Ihre eigene API. Keine Cookies, kein Banner - einfach installieren und loslegen.

## 🎯 Features

- 🍪 **Cookie-frei** - Keine Einwilligung erforderlich
- 🔒 **DSGVO-konform** - Sofortige Anonymisierung aller Daten  
- 🌐 **API-basiert** - Sendet Daten an Ihre eigene Analytics-API
- ⚡ **Performance** - < 2ms Overhead, asynchrone Verarbeitung
- 🎛️ **Multi-App Support** - Ein Dashboard für mehrere Apps/Websites
- 🔧 **Plug & Play** - Automatisches Tracking nach Installation

## 📦 Installation

```bash
composer require wappomic/laravel-analytics
php artisan vendor:publish --tag=analytics-config
```

### .env Konfiguration

```env
# REQUIRED
ANALYTICS_API_URL=https://your-dashboard.com/api/analytics
ANALYTICS_API_KEY=your-unique-app-key-12345

# OPTIONAL  
ANALYTICS_APP_NAME="My Laravel Shop"
ANALYTICS_ENABLED=true
ANALYTICS_QUEUE_ENABLED=true
```

Das war's! 🎉 Das Package trackt jetzt automatisch alle Web-Requests.

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

### 3. App-Models

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
use Carbon\Carbon;

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

### 🛡️ Anonymisierung:

| Original | Anonymisiert |
|----------|-------------|
| `192.168.1.123` | `192.168.1.0` |
| `Mozilla/5.0 Chrome/91.0...` | `Chrome` |
| `2025-08-04 14:23:45` | `2025-08-04 14:00:00` |
| `München, Bayern` | `DE` |

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