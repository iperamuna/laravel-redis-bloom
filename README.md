# Laravel Redis Bloom

<p align="center">
    <img src="https://raw.githubusercontent.com/iperamuna/laravel-redis-bloom/main/art/banner.png" alt="Laravel Redis Bloom Banner" style="width: 100%; max-width: 800px;">
</p>

[![Latest Version on Packagist](https://img.shields.io/packagist/v/iperamuna/laravel-redis-bloom.svg?style=for-the-badge&color=blue)](https://packagist.org/packages/iperamuna/laravel-redis-bloom)
[![Total Downloads](https://img.shields.io/packagist/dt/iperamuna/laravel-redis-bloom.svg?style=for-the-badge&color=green)](https://packagist.org/packages/iperamuna/laravel-redis-bloom)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=for-the-badge)](LICENSE)
[![Build Status](https://img.shields.io/github/actions/workflow/status/iperamuna/laravel-redis-bloom/run-tests.yml?branch=main&style=for-the-badge)](https://github.com/iperamuna/laravel-redis-bloom/actions)
[![Laravel Compatibility](https://badge.laravel.cloud/badge/iperamuna/laravel-redis-bloom?style=for-the-badge)](https://packagist.org/packages/iperamuna/laravel-redis-bloom)

**Laravel Redis Bloom** is an industrial-grade probabilistic infrastructure layer for Laravel. It provides a highly optimized, developer-friendly wrapper around the RedisBloom module, enabling sub-millisecond membership checks at massive scale with near-zero memory overhead.

Whether you are deduplicating billion-row datasets, protecting signup APIs from spam, or implementing high-speed caching logic, this package provides the tools to do it with confidence.

---

## 🧐 The Problem: Scaling Membership Checks

As your data grows, checking if a value (like an email, IP, or ID) already exists becomes an expensive operation. Traditional approaches hit a wall:
- **Database Lookups**: Querying a 100M+ row table for every request introduces latency and puts massive pressure on your primary database.
- **In-Memory Sets (Redis Sets/Hashes)**: Storing millions of unique strings in Redis consumes significant RAM, leading to high infrastructure costs and "Out of Memory" crashes.
- **The "Exists" Bottleneck**: In high-traffic systems (API protection, deduplication, scrapers), you need a way to say **"No, this definitely doesn't exist"** in constant time without wasting memory.

## 🧠 The Solution: Probabilistic Efficiency

**Laravel Redis Bloom** leverages Bloom Filters—a probabilistic data structure that is incredibly space-efficient. 
- **Near-Zero Memory**: You can track 1 million items with less than 2MB of RAM.
- **Sub-Millisecond Speed**: Checks are performed in $O(k)$ time (where $k$ is the number of hash functions), independent of the number of items stored.
- **The Guarantee**: A Bloom filter can tell you with **100% certainty** if an item is *not* in the set. If it says it *is* in the set, there is a tiny, configurable chance of a false positive—which is where our built-in **Truth Verification** comes in.

---

## 🔥 Key Architectural Features

- 🚀 **Native RedisBloom Power**: First-class support for `BF.ADD`, `BF.EXISTS`, and `BF.RESERVE` commands.
- 🔄 **Intelligent Auto-Rotation**: Never worry about capacity again. The system automatically version-controls and rotates filters when full, ensuring continuous availability.
- 📊 **Observability & Metrics**: Built-in tracking for hits, misses, and false-positive rates to fine-tune your probabilistic models.
- 🩺 **System Diagnostics**: A comprehensive `bloom:doctor` command that analyzes your OS, Redis version, and Bloom module status to provide actionable fixes.
- 📦 **Bulk Ingestion Engine**: High-speed Artisan command to hydrate Bloom filters from your existing database tables using chunked processing.
- 🛡️ **Defensive Guard Logic**: Graceful fallbacks and OS-aware error messages that guide you through setup on macOS, Linux, or Windows.
- 🧩 **Laravel Native Integration**: Includes custom Validation Rules, Route Middleware, and a Filament Dashboard widget out of the box.

---

## 🛠 Installation

### 1. Requirements
- **PHP**: 8.2 or higher
- **Laravel**: 11.x, 12.x, or 13.x
- **Redis**: 4.0+ with the [RedisBloom module](https://redis.io/docs/latest/operate/oss_and_stack/install/install-stack/) installed.

### 2. Composer
```bash
composer require iperamuna/laravel-redis-bloom
```

### 3. Publish Configuration
```bash
php artisan vendor:publish --tag=bloom-config
```

### 4. Run System Check
```bash
php artisan bloom:doctor
```

---

## ⚙️ Configuration

Define your specialized filters in `config/bloom.php`. You can tune the error rate and capacity per filter if needed, or use the global defaults.

```php
return [
    'error_rate' => 0.001, // 0.1% false positive rate
    'capacity'   => 1000000, // Initial capacity of 1 million items
    'redis_connection' => 'default', // Redis connection name from database.php
    
    'filters' => [
        'emails' => 'bf:users:emails',
        'ips'    => 'bf:security:blocked_ips',
        'phones' => 'bf:customers:phones',
    ],
    
    'rotation_keep_versions' => 3, // Keep last 3 rotated versions for safety
];
```

> [!TIP]
> **Automatic Prefixing**: This package automatically detects and honors the `prefix` defined in your `config/database.php`. Your Bloom keys will be correctly namespaced (e.g., `laravel_database_bf:emails`).

---

## 🚀 Advanced Usage

### 🧠 The Fluent API
The `Bloom` facade provides a clean, chainable interface for interacting with your filters.

```php
use Iperamuna\LaravelRedisBloom\Facades\Bloom;

// Single entry ingestion
Bloom::filter('emails')->add('founder@example.com');

// High-speed membership check
if (Bloom::filter('emails')->exists('founder@example.com')) {
    // This value probably exists (Probabilistic result)
}

// Bulk ingestion (Pipelined for performance)
Bloom::filter('ips')->addMany([
    '127.0.0.1',
    '192.168.1.1',
    '10.0.0.1'
]);
```

### 🛡️ Smart Validation (Truth Verification)
Bloom filters are probabilistic. While a `false` result is 100% certain, a `true` result is "probably exists." We provide a `check` method to handle the "truth" verification via a fallback closure.

```php
// Check Bloom first, then verify against the database only if Bloom says "maybe"
$exists = Bloom::filter('emails')->check($email, function ($email) {
    return \App\Models\User::where('email', $email)->exists();
});
```

### ✅ Clean Laravel Validation
Use the custom rule in your Form Requests or controllers for ultra-fast duplicate detection.

```php
use Iperamuna\LaravelRedisBloom\Rules\BloomRule;

$request->validate([
    // Standard mode: Fails if Bloom says "maybe exists"
    'email' => ['required', 'email', new BloomRule('emails')],
    
    // Strict mode: Fail immediately with specific "Bloom detected" message
    'phone' => ['required', new BloomRule('phones', strict: true)],
    
    // String syntax support
    'ip' => 'required|bloom:ips',
]);
```

### 🚦 API Guard Middleware
Protect your high-traffic endpoints from duplicate submissions or known spam entries without hitting your database.

```php
// Route definition
Route::middleware('bloom:ips,ip_address')->post('/api/report', ...);
```

---

## 🧰 Management & Monitoring

### 🩺 System Diagnostics (The Doctor)
Setup can be tricky. Our doctor analyzes your environment and gives you the exact commands to run for your specific OS.

```bash
php artisan bloom:doctor
```

### 📈 Real-time Statistics
Monitor the health and efficiency of your filters.

```bash
php artisan bloom:stats emails
```

### 📥 Bulk Hydration
Hydrate a new Bloom filter from your existing production data in seconds.

```bash
# Ingest all user emails into the 'emails' filter
php artisan bloom:fill emails "App\Models\User" email --chunk=5000
```

---

## 🧪 Testing

We use [Pest](https://pestphp.com/) to ensure 100% reliability of the core rotation and metrics logic.

```bash
composer test
```

## 📄 License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## ✨ Credits

Developed with ❤️ by [Indunil Peramuna](https://iperamuna.com).
Check out my [GitHub](https://github.com/iperamuna) for more high-performance Laravel tools.
