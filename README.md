# Laravel Redis Bloom

[![Latest Version on Packagist](https://img.shields.io/packagist/v/iperamuna/laravel-redis-bloom.svg?style=flat-square)](https://packagist.org/packages/iperamuna/laravel-redis-bloom)
[![Total Downloads](https://img.shields.io/packagist/dt/iperamuna/laravel-redis-bloom.svg?style=flat-square)](https://packagist.org/packages/iperamuna/laravel-redis-bloom)
[![License](https://img.shields.io/packagist/l/iperamuna/laravel-redis-bloom.svg?style=flat-square)](https://packagist.org/packages/iperamuna/laravel-redis-bloom)

A high-performance RedisBloom wrapper for Laravel with auto-rotation, metrics, and diagnostics.

## Features

- ✅ **RedisBloom Integration**: Native support for `BF.ADD`, `BF.EXISTS`, etc.
- ✅ **Auto-Rotation**: Automatically creates new filter versions when capacity is reached.
- ✅ **Metrics Tracking**: Monitor hits, misses, and false positives.
- ✅ **Artisan Diagnostics**: `php artisan bloom:doctor` to check system health.
- ✅ **Bulk Loading**: `php artisan bloom:fill` to populate filters from Eloquent models.
- ✅ **Validation & Middleware**: Easy-to-use validation rules and route middleware.
- ✅ **OS-Aware Errors**: Actionable instructions for installing RedisBloom on macOS, Linux, and Windows.

## Installation

1. Install the package via composer:

```bash
composer require iperamuna/laravel-redis-bloom
```

2. Publish the config file:

```bash
php artisan vendor:publish --tag=bloom-config
```

3. Ensure you have the **RedisBloom** module installed on your Redis server. Use the doctor command to verify:

```bash
php artisan bloom:doctor
```

## Configuration

Define your filters in `config/bloom.php`:

```php
return [
    'error_rate' => 0.01,
    'capacity' => 1000000,
    'filters' => [
        'emails' => 'bf:emails',
    ],
];
```

## Usage

### Basic API

```php
use Iperamuna\LaravelRedisBloom\Facades\Bloom;

// Add an item
Bloom::filter('emails')->add('user@example.com');

// Check existence
if (Bloom::filter('emails')->exists('user@example.com')) {
    // Probable match
}

// Check with DB fallback (truth check)
$exists = Bloom::filter('emails')->check($email, function ($email) {
    return User::where('email', $email)->exists();
});
```

### Validation

```php
$request->validate([
    'email' => 'required|email|bloom:emails',
]);
```

### Middleware

```php
Route::middleware('bloom:emails,email')->post('/signup', ...);
```

### Artisan Commands

```bash
# Populate filter from database
php artisan bloom:fill emails "App\Models\User" email

# View statistics
php artisan bloom:stats emails

# Diagnose installation
php artisan bloom:doctor
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Indunil Peramuna](https://github.com/iperamuna)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
