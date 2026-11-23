<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Tests\Fixtures\Requests;

use BlueBeetle\ApiToolkit\Http\Requests\FormRequest;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Enums\OpenApiStubStatus;
use Illuminate\Validation\Rule;

class RequestBodyStubQueryFormRequest extends FormRequest
{
    public function queryParamRules(): array
    {
        return [
            /*
             * Only the products in these states.
             */
            'statuses' => ['array', 'min:1'],
            'statuses.*' => [Rule::enum(OpenApiStubStatus::class)],
            /*
             * Only archived products (true), or only the others (false).
             *
             * @example true
             */
            'archived' => ['boolean'],
            'warehouse' => ['required', 'string'],
        ];
    }
}
