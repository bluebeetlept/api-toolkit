<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Tests\Fixtures\Requests;

use BlueBeetle\ApiToolkit\Http\Requests\FormRequest;
use BlueBeetle\ApiToolkit\Rules\ValidInclude;

class DocBuilderStubObjectRuleFormRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', new ValidInclude(['a'])],
        ];
    }
}
