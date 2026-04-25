<?php

declare(strict_types = 1);

namespace Eufaturo\ApiToolkit\Tests\Feature\Rules;

use Eufaturo\ApiToolkit\Rules\ValidInclude;
use Eufaturo\ApiToolkit\Tests\TestCase;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;

final class ValidIncludeTest extends TestCase
{
    #[Test]
    #[TestDox('it passes for valid includes')]
    public function it_passes_valid_includes(): void
    {
        $validator = Validator::make(
            ['include' => 'category,supplier'],
            ['include' => [new ValidInclude(['category', 'supplier', 'brand'])]],
        );

        $this->assertTrue($validator->passes());
    }

    #[Test]
    #[TestDox('it fails for invalid includes')]
    public function it_fails_invalid_includes(): void
    {
        $validator = Validator::make(
            ['include' => 'category,secret'],
            ['include' => [new ValidInclude(['category', 'supplier'])]],
        );

        $this->assertFalse($validator->passes());
    }

    #[Test]
    #[TestDox('it accepts array values')]
    public function it_accepts_array_values(): void
    {
        $validator = Validator::make(
            ['include' => ['category', 'supplier']],
            ['include' => [new ValidInclude(['category', 'supplier'])]],
        );

        $this->assertTrue($validator->passes());
    }

    #[Test]
    #[TestDox('it provides error message with invalid include names')]
    public function it_provides_error_message(): void
    {
        $validator = Validator::make(
            ['include' => 'invalid'],
            ['include' => [new ValidInclude(['category'])]],
        );

        $this->assertStringContainsString('invalid', $validator->errors()->first('include'));
    }
}
