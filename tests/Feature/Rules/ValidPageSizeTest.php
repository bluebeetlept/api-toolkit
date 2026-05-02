<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Tests\Feature\Rules;

use BlueBeetle\ApiToolkit\Rules\ValidPageSize;
use Illuminate\Support\Facades\Validator;

it('passes for valid page sizes', function () {
    foreach ([10, 20, 40, 80, 100] as $size) {
        $validator = Validator::make(
            ['size' => $size],
            ['size' => [new ValidPageSize()]],
        );

        expect($validator->passes())->toBeTrue("Expected {$size} to be valid");
    }
});

it('fails for invalid page sizes', function () {
    foreach ([0, 5, 15, 50, 200] as $size) {
        $validator = Validator::make(
            ['size' => $size],
            ['size' => [new ValidPageSize()]],
        );

        expect($validator->passes())->toBeFalse("Expected {$size} to be invalid");
    }
});

it('supports custom valid sizes', function () {
    $validator = Validator::make(
        ['size' => 25],
        ['size' => [new ValidPageSize([25, 50, 75])]],
    );

    expect($validator->passes())->toBeTrue();
});

it('provides error message', function () {
    $validator = Validator::make(
        ['size' => 15],
        ['size' => [new ValidPageSize()]],
    );

    expect($validator->errors()->first('size'))->toContain('15');
});
