<?php

declare(strict_types = 1);

namespace Eufaturo\ApiToolkit\Tests\Acceptance\Http\Requests\Stubs;

use Eufaturo\ApiToolkit\Http\Requests\FormRequest;

final class FormRequestWithFormInputRules extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required'],
        ];
    }
}
