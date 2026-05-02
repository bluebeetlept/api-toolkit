<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Tests\Fixtures\Requests;

use BlueBeetle\ApiToolkit\Http\Requests\FormRequest;

class DocBuilderStubEmptyFormRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
