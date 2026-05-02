<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Tests\Feature\Rules;

use BlueBeetle\ApiToolkit\Rules\ValidInclude;
use Illuminate\Support\Facades\Validator;

it('passes for valid includes', function () {
    $validator = Validator::make(
        ['include' => 'category,supplier'],
        ['include' => [new ValidInclude(['category', 'supplier', 'brand'])]],
    );

    expect($validator->passes())->toBeTrue();
});

it('fails for invalid includes', function () {
    $validator = Validator::make(
        ['include' => 'category,secret'],
        ['include' => [new ValidInclude(['category', 'supplier'])]],
    );

    expect($validator->passes())->toBeFalse();
});

it('accepts array values', function () {
    $validator = Validator::make(
        ['include' => ['category', 'supplier']],
        ['include' => [new ValidInclude(['category', 'supplier'])]],
    );

    expect($validator->passes())->toBeTrue();
});

it('provides error message with invalid include names', function () {
    $validator = Validator::make(
        ['include' => 'invalid'],
        ['include' => [new ValidInclude(['category'])]],
    );

    expect($validator->errors()->first('include'))->toContain('invalid');
});
