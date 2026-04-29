# Changelog

All notable changes to `laravel-redis-bloom` will be documented in this file.

## 1.0.0 - 2026-04-29

- Initial release.
- Core Bloom filter implementation with auto-rotation.
- Metrics tracking (hits/misses/false positives).
- Artisan commands: `bloom:fill`, `bloom:stats`, `bloom:doctor`.
- Middleware and Validation Rule support.
- OS-aware diagnostic error messages.
- Added `redis_connection` configuration support.
- Implemented automatic Redis prefix detection and handling.
- Fixed `phpredis` compatibility issues by using `executeRaw` for custom commands.
