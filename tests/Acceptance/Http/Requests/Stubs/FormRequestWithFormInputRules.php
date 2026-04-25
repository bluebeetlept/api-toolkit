<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Tests\Acceptance\Http\Requests\Stubs;

use BlueBeetle\ApiToolkit\Http\Requests\FormRequest;

final class FormRequestWithFormInputRules extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required'],
        ];
    }
}
