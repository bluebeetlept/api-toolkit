<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Tests\Fixtures\Requests;

use BlueBeetle\ApiToolkit\Http\Requests\FormRequest;
use RuntimeException;

class RequestBodyStubContextFormRequest extends FormRequest
{
    public function rules(): array
    {
        if ($this->user() === null) {
            throw new RuntimeException('The rules need to know who is asking.');
        }

        return [
            'note' => ['required', 'string'],
        ];
    }
}
