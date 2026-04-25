<?php

declare(strict_types = 1);

namespace Eufaturo\ApiToolkit\Tests\Feature\Rules;

use Eufaturo\ApiToolkit\Rules\ValidPageSize;
use Eufaturo\ApiToolkit\Tests\TestCase;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;

final class ValidPageSizeTest extends TestCase
{
    #[Test]
    #[TestDox('it passes for valid page sizes')]
    public function it_passes_valid_sizes(): void
    {
        foreach ([10, 20, 40, 80, 100] as $size) {
            $validator = Validator::make(
                ['size' => $size],
                ['size' => [new ValidPageSize()]],
            );

            $this->assertTrue($validator->passes(), "Expected {$size} to be valid");
        }
    }

    #[Test]
    #[TestDox('it fails for invalid page sizes')]
    public function it_fails_invalid_sizes(): void
    {
        foreach ([0, 5, 15, 50, 200] as $size) {
            $validator = Validator::make(
                ['size' => $size],
                ['size' => [new ValidPageSize()]],
            );

            $this->assertFalse($validator->passes(), "Expected {$size} to be invalid");
        }
    }

    #[Test]
    #[TestDox('it supports custom valid sizes')]
    public function it_supports_custom_sizes(): void
    {
        $validator = Validator::make(
            ['size' => 25],
            ['size' => [new ValidPageSize([25, 50, 75])]],
        );

        $this->assertTrue($validator->passes());
    }

    #[Test]
    #[TestDox('it provides error message')]
    public function it_provides_error_message(): void
    {
        $validator = Validator::make(
            ['size' => 15],
            ['size' => [new ValidPageSize()]],
        );

        $this->assertStringContainsString('15', $validator->errors()->first('size'));
    }
}
