<?php

namespace Iperamuna\LaravelRedisBloom\Exceptions;

use Exception;

class RedisBloomNotAvailableException extends Exception
{
    public static function make(): self
    {
        $os = PHP_OS_FAMILY;
        $arch = php_uname('m');

        return new self(static::buildMessage($os, $arch));
    }

    protected static function buildMessage(string $os, string $arch): string
    {
        $base = "RedisBloom module is not available.\n\n";
        $context = "Detected environment:\n- OS: {$os}\n- Arch: {$arch}\n\n";

        $instructions = match ($os) {
            'Darwin' => self::macInstructions($arch),
            'Linux' => self::linuxInstructions(),
            'Windows' => self::windowsInstructions(),
            default => self::genericInstructions(),
        };

        return $base.$context.$instructions;
    }

    protected static function macInstructions(string $arch): string
    {
        return match ($arch) {
            'arm64' => <<<'TXT'
Recommended fix (Apple Silicon Mac):
1. Install Redis Stack (includes RedisBloom):
   brew install redis-stack
2. Start server:
   redis-stack-server
Alternative:
   brew install redis-stack-server
TXT,
            default => <<<'TXT'
Recommended fix (Intel Mac):
1. Install Redis Stack:
   brew install redis-stack
2. Start:
   redis-stack-server
Alternative manual module:
   brew install redisbloom
TXT,
        };
    }

    protected static function linuxInstructions(): string
    {
        return <<<'TXT'
Recommended fix (Linux):
Option 1 - Redis Stack (recommended):
  https://redis.io/docs/latest/operate/oss_and_stack/install/install-stack/
Option 2 - RedisBloom module:
  git clone https://github.com/RedisBloom/RedisBloom
  make
  loadmodule redisbloom.so
Ensure Redis is started with:
  loadmodule /path/to/redisbloom.so
TXT;
    }

    protected static function windowsInstructions(): string
    {
        return <<<'TXT'
Windows detected:
Recommended approach:
1. Use Docker Redis Stack:
   docker run -p 6379:6379 redis/redis-stack-server
OR
2. Use WSL (recommended for Laravel dev):
   - Install Ubuntu
   - Install Redis Stack inside WSL
Note: RedisBloom is not officially supported natively on Windows.
TXT;
    }

    protected static function genericInstructions(): string
    {
        return <<<'TXT'
Install Redis Stack which includes RedisBloom:
https://redis.io/docs/latest/operate/oss_and_stack/install/install-stack/
Or manually load RedisBloom module.
TXT;
    }
}
