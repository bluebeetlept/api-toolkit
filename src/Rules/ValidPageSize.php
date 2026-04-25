<?php

declare(strict_types = 1);

namespace Eufaturo\ApiToolkit\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final readonly class ValidPageSize implements ValidationRule
{
    public function __construct(
        private array $validSizes = [
            10, 20, 40, 80, 100,
        ],
    ) {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $exists = in_array((int) $value, $this->validSizes, true);

        if ($exists === false) {
            $fail("The [{$value}] is not a valid page size value.");
        }
    }
}
