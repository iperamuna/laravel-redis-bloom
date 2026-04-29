<?php

namespace Iperamuna\LaravelRedisBloom\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;
use Iperamuna\LaravelRedisBloom\Facades\Bloom;

class BloomRule implements ValidationRule
{
    /**
     * Create a new rule instance.
     *
     * @param  bool  $strict  If true, fail immediately on Bloom hit. If false, check DB (if possible).
     */
    public function __construct(
        protected string $filter,
        protected bool $strict = false
    ) {}

    /**
     * Run the validation rule.
     *
     * @param  Closure(string): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        try {
            $bloom = Bloom::filter($this->filter);

            if (! $bloom->exists($value)) {
                return; // Definitely safe
            }

            if ($this->strict) {
                $fail("The {$attribute} is already in use (Bloom detected).");

                return;
            }

            // By default, if not strict, we just warn that it might exist
            // A more advanced version would check the DB here if a table was provided.
            $fail("The {$attribute} might already exist.");

        } catch (\Throwable $e) {
            // If Bloom is unavailable, we pass validation to avoid breaking the app
        }
    }
}
