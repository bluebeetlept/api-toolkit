<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final readonly class ValidInclude implements ValidationRule
{
    public function __construct(
        private array $validIncludes,
    ) {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $value = is_string($value) ? explode(',', $value) : $value;

        $value = array_map('trim', $value);

        $invalidIncludes = array_diff($value, $this->validIncludes);

        if (count($invalidIncludes) > 0) {
            $fail(
                sprintf(
                    'The [%s] is not a valid include value.',
                    implode(',', $invalidIncludes),
                ),
            );
        }
    }
}
