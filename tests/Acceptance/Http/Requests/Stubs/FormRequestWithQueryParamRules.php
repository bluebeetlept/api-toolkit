<?php

declare(strict_types = 1);

namespace Eufaturo\ApiToolkit\Tests\Acceptance\Http\Requests\Stubs;

use Eufaturo\ApiToolkit\Http\Requests\FormRequest;

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
