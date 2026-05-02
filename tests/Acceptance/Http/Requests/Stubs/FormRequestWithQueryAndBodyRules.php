<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Tests\Acceptance\Http\Requests\Stubs;

use BlueBeetle\ApiToolkit\Http\Requests\FormRequest;

final class FormRequestWithQueryAndBodyRules extends FormRequest
{
    public function queryParamRules(): array
    {
        return [
            'include' => ['sometimes', 'array'],
            'include.*' => ['string'],
        ];
    }

    public function rules(): array
    {
        return [
            'name' => ['required'],
        ];
    }
}
