<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Tests\Fixtures\Requests;

use BlueBeetle\ApiToolkit\Http\Requests\FormRequest;

class DocBuilderStubTypedFormRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'age' => ['required', 'integer'],
            'active' => ['boolean'],
            'email' => ['required', 'email'],
            'website' => ['url'],
            'birthday' => ['date'],
            'tags' => ['array'],
            'description' => ['nullable', 'string'],
        ];
    }
}
