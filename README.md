# Wappomic Laravel Analytics v1

[![en](https://img.shields.io/badge/lang-en-blue.svg)](README.md)
[![de](https://img.shields.io/badge/lang-de-green.svg)](README.de.md)


**Cookie-free, GDPR-compliant analytics package for Laravel**

Collects anonymized website data and sends it to your own API. No cookies, no banner - just install and go.

## 🎯 Features

- 🍪 **Cookie-free** - No consent required
- 🔒 **GDPR-compliant** - Immediate anonymization of all data  
- 🌐 **API-based** - Sends data to your own analytics API
- ⚡ **Performance** - < 2ms overhead, asynchronous processing
- 🎛️ **Multi-App Support** - One dashboard for multiple apps/websites
- 🔧 **Plug & Play** - Automatic tracking after installation
- 🔄 **Session Tracking** - Cookie-free unique visitor identification

## 📦 Installation

```bash
composer require wappomic/laravel-analytics
php artisan vendor:publish --tag=analytics-config
```

### .env Configuration

```env
# REQUIRED
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
```

That's it! 🎉 The package now automatically tracks all web requests.

## 🚫 Excluded Routes Configuration

By default, certain routes are automatically excluded from tracking (admin pages, APIs, static files, etc.). You can customize this list or disable exclusions entirely.

### Default Excluded Routes

```php
// config/analytics.php
'excluded_routes' => [
    '/admin*',
    '/api*',
    '/broadcasting*',  // 🎯 Solves Laravel Broadcasting auth issues
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

### Customization Examples

```php
// Track everything (no exclusions)
'excluded_routes' => [],

// Only exclude specific routes
'excluded_routes' => ['/admin*', '/broadcasting*'],

// Add your custom routes to defaults
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
    '/my-private-section*',  // Your custom exclusions
    '/internal-api/*',
    '*.pdf',
],
```

### Wildcard Patterns

The excluded routes support wildcard patterns using `fnmatch()`:

| Pattern | Matches | Examples |
|---------|---------|----------|
| `/admin*` | Routes starting with `/admin` | `/admin`, `/admin/users`, `/admin/dashboard/settings` |
| `*.json` | Routes ending with `.json` | `/data.json`, `/api/users.json` |
| `/api/*` | Routes under `/api/` | `/api/users`, `/api/v1/posts` |
| `*broadcasting*` | Routes containing `broadcasting` | `/broadcasting/auth`, `/laravel/broadcasting/auth` |

### Common Use Cases

**Laravel Broadcasting (WebSocket Auth)**:
```php
'excluded_routes' => ['/broadcasting*'],
// Prevents tracking of '/broadcasting/auth' routes
```

**SPA Applications**:
```php
'excluded_routes' => ['/api*', '*.json'],
// Only track page views, not API calls
```

**Track Everything**:
```php
'excluded_routes' => [],
// No automatic exclusions - track all routes
```

### Combined with Route Middleware

You have two options to exclude routes:

```php
// Option 1: Global config (recommended)
'excluded_routes' => ['/admin*', '/broadcasting*'],

// Option 2: Per-route basis
Route::get('/admin', AdminController::class)->withoutMiddleware('analytics.tracking');
```

## 📊 Data Format

Your API receives POST requests with this JSON payload:

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

### 🔑 Multi-App Setup (Recommended)

**One dashboard for all your apps:**

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

All apps send to the same `ANALYTICS_API_URL` but with different `api_key` - perfect data separation.

## 🛠️ Analytics Dashboard Implementation

### 1. Create API Endpoint

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
            'session_hash' => 'nullable|string',
            'is_new_session' => 'nullable|boolean',
            'pageview_count' => 'nullable|integer',
            'session_duration' => 'nullable|integer',
            'custom_data' => 'nullable|array',
        ]);

        // Find app by API key
        $app = App::where('api_key', $data['api_key'])->first();
        
        if (!$app) {
            return response()->json(['error' => 'Invalid API key'], 401);
        }

        // Store analytics data
        AnalyticsData::create([
            'app_id' => $app->id,
            'timestamp' => $data['timestamp'],
            'url' => $data['url'],
            'referrer' => $data['referrer'],
            'anonymized_ip' => $data['anonymized_ip'],
            'browser' => $data['browser'],
            'device' => $data['device'],
            'country' => $data['country'],
            'session_hash' => $data['session_hash'] ?? null,
            'is_new_session' => $data['is_new_session'] ?? false,
            'pageview_count' => $data['pageview_count'] ?? 1,
            'session_duration' => $data['session_duration'] ?? 0,
            'custom_data' => $data['custom_data'],
        ]);

        // Update app name on first request (optional)
        if ($data['app_name'] && $app->name !== $data['app_name']) {
            $app->update(['name' => $data['app_name']]);
        }

        return response()->json(['status' => 'success']);
    }
}
```

### 2. Database Schema

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
    $table->string('session_hash', 64)->nullable();
    $table->boolean('is_new_session')->default(false);
    $table->integer('pageview_count')->default(1);
    $table->integer('session_duration')->default(0);
    $table->json('custom_data')->nullable();
    $table->timestamps();
    
    $table->index(['app_id', 'timestamp']);
    $table->index(['app_id', 'url']);
    $table->index(['app_id', 'session_hash']);
    $table->index(['app_id', 'is_new_session']);
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
        'anonymized_ip', 'browser', 'device', 'country', 
        'session_hash', 'is_new_session', 'pageview_count', 'session_duration',
        'custom_data'
    ];
    
    protected $casts = [
        'timestamp' => 'datetime',
        'is_new_session' => 'boolean',
        'pageview_count' => 'integer',
        'session_duration' => 'integer',
        'custom_data' => 'array',
    ];
    
    public function app()
    {
        return $this->belongsTo(App::class);
    }
}
```

### 4. Dashboard Controller

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
            'total_pageviews' => $app->analyticsData()->count(),
            'unique_visitors' => $app->analyticsData()->where('is_new_session', true)->count(),
            'today_pageviews' => $app->analyticsData()->whereDate('timestamp', today())->count(),
            'today_visitors' => $app->analyticsData()
                ->whereDate('timestamp', today())
                ->where('is_new_session', true)
                ->count(),
            'top_pages' => $app->analyticsData()
                ->select('url')
                ->selectRaw('COUNT(*) as pageviews')
                ->selectRaw('COUNT(CASE WHEN is_new_session = 1 THEN 1 END) as unique_visitors')
                ->groupBy('url')
                ->orderByDesc('pageviews')
                ->limit(10)
                ->get(),
            'countries' => $app->analyticsData()
                ->select('country')
                ->selectRaw('COUNT(CASE WHEN is_new_session = 1 THEN 1 END) as unique_visitors')
                ->selectRaw('COUNT(*) as pageviews')
                ->whereNotNull('country')
                ->groupBy('country')
                ->orderByDesc('unique_visitors')
                ->limit(10)
                ->get(),
            'avg_session_duration' => $app->analyticsData()
                ->where('session_duration', '>', 0)
                ->avg('session_duration'),
        ];
        
        return view('dashboard.app', compact('app', 'stats'));
    }
}
```

## 🔐 GDPR Compliance

### ✅ Why no consent is required:

- **No cookies** - Package sets no cookies
- **Immediate anonymization** - IP becomes `192.168.1.0` 
- **No user tracking** - No persistent user identification
- **Data minimization** - Only necessary data
- **Legitimate interest** - Art. 6 Para. 1 lit. f GDPR
- **Session hashing** - Anonymous daily session hashes, not traceable

### 🛡️ Anonymization:

| Original | Anonymized |
|----------|-------------|
| `192.168.1.123` | `192.168.1.0` |
| `Mozilla/5.0 Chrome/91.0...` | `Chrome` |
| `2025-08-04 14:23:45` | `2025-08-04 14:00:00` |
| `Munich, Bavaria` | `DE` |
| Session ID | `abc123def456...` (daily hash) |

## ⚙️ Advanced Usage

### Manual Tracking

```php
use Wappomic\Analytics\Facades\Analytics;

// Track custom event
Analytics::track([
    'url' => '/newsletter-signup',
    'custom_data' => ['campaign' => 'summer-sale']
]);

// Check status
if (Analytics::isEnabled() && Analytics::isConfigured()) {
    // Analytics is running
}

// Test API connection
if (Analytics::testConnection()) {
    echo "✅ API reachable";
} else {
    echo "❌ API problem - check config";
}
```

### Manual Middleware Control

```php
// routes/web.php

// Automatic tracking for all routes (default)
Route::get('/', HomeController::class);

// Disable tracking for specific routes
Route::get('/admin', AdminController::class)->withoutMiddleware('analytics.tracking');

// Track only specific routes
Route::group(['middleware' => 'analytics.tracking'], function () {
    Route::get('/shop', ShopController::class);
    Route::get('/products', ProductController::class);
});
```

## 🚀 Performance & Monitoring

- **Middleware overhead**: < 2ms
- **Asynchronous**: Via Laravel Queues (recommended)
- **Retry logic**: 3x retry on API failures
- **Timeout**: 10 seconds
- **Error handling**: Logs when `APP_DEBUG=true`

## 🔧 Troubleshooting

### No data received?

1. **Check config**:
```bash
php artisan tinker
>>> Analytics::validateConfig()
>>> Analytics::testConnection()
```

2. **Queue running?**:
```bash
php artisan queue:work
# Or temporarily disable:
ANALYTICS_QUEUE_ENABLED=false
```

### Queue problems with Redis?

If you're using Redis and the queue isn't working:

1. **Check Redis connection**:
```bash
php artisan tinker
>>> Redis::ping()  # Should return "PONG"
```

2. **Configure Redis queue explicitly**:
```env
ANALYTICS_QUEUE_CONNECTION=redis
ANALYTICS_QUEUE_NAME=analytics
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

3. **Check failed jobs**:
```bash
php artisan queue:failed
php artisan queue:retry all  # Retry failed jobs
```

4. **Monitor queue in real-time**:
```bash
php artisan queue:work --verbose --tries=3 --timeout=30
```

3. **Check logs**:
```bash
tail -f storage/logs/laravel.log
```

### API Debugging

```php
// Your analytics API should return:
HTTP/1.1 200 OK
Content-Type: application/json

{"status": "success"}

// On errors:
HTTP/1.1 400 Bad Request
{"error": "Invalid data", "details": [...]}
```

## 📈 Next Steps

1. **Generate API keys** for your apps
2. **Implement dashboard** with examples above
3. **Add charts** (Chart.js, ApexCharts)
4. **Real-time updates** with WebSockets
5. **Export functions** (PDF, Excel)

## 📄 License

MIT License - See [LICENSE](LICENSE) for details.

---

**Happy Analytics! 🎉**  
Feel free to create an issue on GitHub if you have questions.

---

## 🇩🇪 Deutsche Version

Die vollständige deutsche Dokumentation finden Sie in der [README.de.md](README.de.md) Datei.

*For the complete German documentation, please see [README.de.md](README.de.md).*