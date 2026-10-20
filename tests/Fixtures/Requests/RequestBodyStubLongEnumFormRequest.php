<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Tests\Fixtures\Requests;

use BlueBeetle\ApiToolkit\Http\Requests\FormRequest;
use Illuminate\Validation\Rule;

class RequestBodyStubLongEnumFormRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'code' => [Rule::in(array_map(static fn (int $number): string => "C{$number}", range(1, 101)))],
        ];
    }
}
