# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### 🔄 Coming Soon
- Future improvements and features will be documented here

---

## [1.0.4] - 2025-08-10

### 🚀 Production Hardening Release

#### ✨ Added
- **Request Deduplication:** SHA256-based request signatures with Redis cache prevent duplicate tracking (fixes 3x tracking issues in production)
- **Production Request Filtering:** Automatic detection and filtering of load balancer health checks, internal requests, and proxy requests
- **Verbose Logging Control:** New `ANALYTICS_VERBOSE_LOGGING` config option for optional detailed debugging logs
- **Enhanced Error Diagnostics:** Detailed API failure logging with HTTP status codes, response bodies, and connection error details
- **Job Configuration Pass-through:** Config is now passed directly to queue jobs, preventing config loading issues in queue worker context

#### 🛠️ Improved  
- **Tiered Logging Strategy:** Clean production logs (ERROR/INFO) with optional verbose debugging (DEBUG level)
- **Queue Job Reliability:** Reduced retries from 3 to 2 attempts with exponential backoff (5s, 15s delays)
- **API Client Error Handling:** Specific exception types distinguish connection failures from request failures
- **Request Signature Algorithm:** Time-bucketed signatures (10-second windows) for efficient deduplication

#### 🔧 Technical Improvements
- **Request ID Correlation:** Unique request IDs track requests from middleware → queue job → API call
- **Production Environment Detection:** Smart detection of internal IP ranges (RFC 1918), proxy headers, and health check user agents
- **Config Validation in Jobs:** Pre-execution config validation prevents invalid API calls and provides clear error messages  
- **Cache-based Deduplication:** 60-second TTL Redis cache prevents rapid duplicate request processing

#### 📊 Monitoring Features
- **Request Flow Tracking:** End-to-end request correlation with unique IDs for debugging production issues
- **Performance Metrics:** Microsecond-precision timing for middleware execution and API call durations
- **Production-Ready Logging:** Configurable log verbosity suitable for high-traffic production environments
- **Health Check Filtering:** Prevents analytics pollution from load balancers (AWS ELB, Nginx, Apache, Laravel Forge)

#### 🎯 Production Fixes
- **Eliminates 3x Duplicate Tracking:** Root cause analysis and fix for production duplicate analytics entries
- **Queue Worker Compatibility:** Improved reliability in multi-worker queue processing environments  
- **Load Balancer Integration:** Seamless operation behind reverse proxies and load balancers
- **Error Transparency:** Detailed API debugging information for rapid production issue resolution

**Migration Notes:** Fully backward compatible. Add `ANALYTICS_VERBOSE_LOGGING=false` to `.env` for explicit log level control.

---

## [1.0.3] - 2025-08-09

### 🚀 Improved
- **Session Duration Precision:** Upgraded from minute-only to seconds precision for better analytics accuracy
- **Data Recovery:** Sessions under 60 seconds are no longer lost (previously showed as `session_duration: 0`)
- **API Data Quality:** More precise session duration values sent to analytics API

### ✅ Added
- **Precision Test Suite:** New `SessionDurationSecondsTest.php` with 5 comprehensive test scenarios
- **Accuracy Validation:** Tests for short sessions, long sessions, and exact API payload verification
- **Recovery Testing:** Validates previously lost sessions (< 60s) are now properly tracked

### 🛠️ Changed
- **Calculation Method:** `diffInMinutes()` → `diffInSeconds()` for granular time tracking
- **API Payload Format:** Session durations now sent as seconds instead of rounded minutes
- **Test Expectations:** Updated existing tests to validate seconds precision instead of minutes

### 📊 Precision Improvement Examples

| Session Duration | Before (Minutes) | After (Seconds) | Impact |
|------------------|------------------|-----------------|---------|
| 30 seconds | `0` ❌ Lost | `30` ✅ Preserved | **Session recovered!** |
| 75 seconds | `1` (imprecise) | `75` ✅ Precise | **25% more accurate** |
| 150 seconds | `2` (imprecise) | `150` ✅ Exact | **Perfect precision** |
| 300 seconds | `5` (rounded) | `300` ✅ Precise | **Eliminates rounding** |

### 🔧 Technical Implementation
- **Root Cause:** `diffInMinutes()` was rounding down all session durations, causing precision loss
- **Solution:** `abs($now->diffInSeconds($created))` provides exact second-level precision
- **Benefit:** Analytics APIs receive precise data for accurate average session time calculations

### 🎯 Analytics Impact
- **Before:** Average of `[0, 1, 2, 3]` minutes = 1.5 minutes (data loss due to rounding)
- **After:** Average of `[30, 75, 150, 180]` seconds = 108.75s = 1.81 minutes (true precision)

### 🔄 Backward Compatibility
- ✅ **Fully Compatible:** No breaking changes for existing installations
- 🔧 **API Migration:** API consumers can convert to minutes if needed: `minutes = session_duration / 60`
- 🚀 **Automatic Improvement:** All existing installations benefit immediately

**Migration Notes:** This is an automatic data quality improvement. No configuration changes required.

---

## [1.0.2] - 2025-08-09

### 🐛 Fixed
- **Critical:** Session duration calculation returning 0 for all sessions due to negative `diffInMinutes()` values
- **Middleware:** Added config fallback `config('analytics', [])` to prevent null pointer exceptions during testing
- **Type Safety:** Improved error handling with fallback values for debug mode

### ✅ Added  
- **Comprehensive Test Suite:** New `SessionTrackingServiceTest.php` with 7 detailed test scenarios:
  - ✅ New session creation (duration = 0)
  - ✅ Existing session duration calculation  
  - ✅ User scenario reproduction test with exact cache data
  - ✅ Microsecond precision timestamp handling
  - ✅ Timezone compatibility across different formats
  - ✅ Negative duration protection tests
  - ✅ Future timestamp edge case handling
- **Documentation:** Complete `CHANGELOG.md` with full version history from beta.1 to stable
- **README Integration:** Changelog links and current version highlights in both EN/DE versions

### 🛠️ Changed
- **Session Duration Logic:** Now uses `abs($now->diffInMinutes($created))` instead of raw negative values
- **Future Timestamp Protection:** Added `$created->isFuture()` safety check
- **Error Logging:** Enhanced debug logging with better context information
- **Type Consistency:** Explicit `(int)` casting for duration return values

### 🔧 Technical Details
**Root Cause:** `diffInMinutes()` returns negative values when `$created` timestamp is in the past (which is always the case for session tracking). The previous `max(0, $duration)` converted all negative values to 0.

**Solution:** Using `abs()` wrapper ensures positive duration regardless of timestamp order, while maintaining all safety checks for edge cases.

**Impact:** Fixes the core session tracking functionality - sessions now show correct durations instead of always 0.

**Backward Compatibility:** ✅ Fully compatible - no breaking changes, automatic fix for existing installations

---

## [1.0.1] - 2025-08-09

### 🐛 Fixed
- **Session Duration Bug:** Corrected session duration calculation that was returning 0 for all sessions
- **Timestamp Parsing:** Improved error handling for parsing failures in session tracking
- **Negative Duration Protection:** Enhanced logic to handle edge cases where timestamps might cause negative durations

### ✅ Added
- Better error handling and logging for session duration calculations
- Fallback protection for future timestamp edge cases

### 🛠️ Changed
- Session duration calculation now uses absolute time differences
- Improved robustness of timestamp parsing with microsecond precision

**Migration Notes:** This is a backward-compatible bug fix. No changes required to existing installations.

---

## [1.0.0] - 2025-08-09

### 🎉 First Stable Release
- **Production Ready:** Removed all beta labels and warnings
- **Stable API:** All core features are now stable and production-tested

### 🛠️ Changed
- Removed beta version warnings from README files
- Updated package metadata to remove "beta" keyword
- Finalized documentation for production use

### 📚 Documentation
- Clean, production-ready README in English and German
- Removed development warnings and beta disclaimers

---

## [1.0.0-beta.4] - 2025-08-09

### ✅ Added
- **Configurable Route Exclusions:** Routes can now be configured via `config/analytics.php`
- **Flexible Wildcard Patterns:** Support for complex route exclusion patterns

### 🐛 Fixed
- **Session Duration:** Ensure session duration is never negative in payload calculations
- **Route Filtering:** Improved route exclusion logic with better pattern matching

### 🛠️ Changed
- Switched from hardcoded excluded routes to configurable array in `config/analytics.php`
- Enhanced route exclusion system with more flexible pattern matching

---

## [1.0.0-beta.3] - 2025-08-09

### ✨ Added
- **Session Tracking:** Cookie-free unique visitor identification system
- **Session Duration:** Track visitor session length without cookies
- **Configurable Session TTL:** Control how long sessions are tracked (default: 24 hours)
- **Privacy-First:** Session hashes are anonymized and change daily

### 🔧 Configuration
```env
ANALYTICS_SESSION_TRACKING_ENABLED=true
ANALYTICS_SESSION_TTL_HOURS=24
```

### 📊 Payload Extensions
- Added `session_hash`, `is_new_session`, `pageview_count`, and `session_duration` to analytics payload
- Session data is included in all tracked requests when enabled

---

## [1.0.0-beta.2] - 2025-08-09

### ✅ Added
- **German Documentation:** Complete German README (`README.de.md`)
- **Multi-language Support:** Language badges and links between EN/DE versions
- **Improved Documentation:** Better examples and clearer installation instructions

### 🌐 Internationalization
- Deutsche Übersetzung der kompletten Dokumentation
- Sprachauswahl-Badges in beiden README-Dateien
- Kulturspezifische Beispiele und DSGVO-Hinweise

---

## [1.0.0-beta.1] - 2025-08-09

### 🎯 Initial Package Release

#### ✨ Core Features
- **Cookie-free Analytics:** No consent banner required
- **GDPR Compliant:** Immediate anonymization of all data
- **API-based Architecture:** Send data to your own analytics API
- **Performance Optimized:** < 2ms middleware overhead
- **Queue Support:** Asynchronous processing with Redis/Database queues

#### 🔒 Privacy Features
- **IP Anonymization:** IPv4 (192.168.1.0) and IPv6 (2001:db8::) support
- **User Agent Cleaning:** Only browser family (Chrome, Firefox, etc.)
- **Timestamp Rounding:** Round to nearest hour for privacy
- **No Local Storage:** Data sent directly to API, no database required

#### 🛠️ Technical Features
- **Automatic Route Exclusion:** Skip admin, API, static files automatically
- **User Agent Filtering:** Exclude bots, crawlers, scrapers
- **AJAX Request Filtering:** Skip AJAX/JSON requests
- **Flexible Configuration:** Comprehensive `.env` and config options

#### 📦 Installation
```bash
composer require wappomic/laravel-analytics
php artisan vendor:publish --tag=analytics-config
```

#### 🔧 Configuration Options
- API URL and API Key (required)
- App name for multi-app setups
- Queue connection and queue name
- Route exclusions and filtering options

#### 🚀 Zero Configuration Required
Works out of the box with just API URL and API Key - automatic tracking starts immediately.

---

## Version Numbering

This project follows [Semantic Versioning](https://semver.org/):

- **MAJOR** version when you make incompatible API changes
- **MINOR** version when you add functionality in a backwards compatible manner  
- **PATCH** version when you make backwards compatible bug fixes

## Support

- 🐛 **Bug Reports:** [GitHub Issues](https://github.com/wappomic/laravel-analytics/issues)
- 💡 **Feature Requests:** [GitHub Discussions](https://github.com/wappomic/laravel-analytics/discussions)
- 📧 **Contact:** info@wappomic.com

[Unreleased]: https://github.com/wappomic/laravel-analytics/compare/v1.0.4...HEAD
[1.0.4]: https://github.com/wappomic/laravel-analytics/compare/v1.0.3...v1.0.4
[1.0.3]: https://github.com/wappomic/laravel-analytics/compare/v1.0.2...v1.0.3
[1.0.2]: https://github.com/wappomic/laravel-analytics/compare/v1.0.1...v1.0.2
[1.0.1]: https://github.com/wappomic/laravel-analytics/compare/v1.0.0...v1.0.1
[1.0.0]: https://github.com/wappomic/laravel-analytics/compare/v1.0.0-beta.4...v1.0.0
[1.0.0-beta.4]: https://github.com/wappomic/laravel-analytics/compare/v1.0.0-beta.3...v1.0.0-beta.4
[1.0.0-beta.3]: https://github.com/wappomic/laravel-analytics/compare/v1.0.0-beta.2...v1.0.0-beta.3
[1.0.0-beta.2]: https://github.com/wappomic/laravel-analytics/compare/v1.0.0-beta.1...v1.0.0-beta.2
[1.0.0-beta.1]: https://github.com/wappomic/laravel-analytics/releases/tag/v1.0.0-beta.1