<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Tests\Acceptance\Http\Requests\Stubs;

use BlueBeetle\ApiToolkit\Http\Requests\FormRequest;

final class FormRequestWithQueryParamRules extends FormRequest
{
    public function queryParamRules(): array
    {
        return [
            'include' => ['required', 'array'],
            'include.*' => ['string'],
        ];
    }
}
