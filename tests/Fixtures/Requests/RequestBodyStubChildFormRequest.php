<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Tests\Fixtures\Requests;

class RequestBodyStubChildFormRequest extends RequestBodyStubNestedFormRequest
{
    public function rules(): array
    {
        return [
            /*
             * @example rush
             */
            'kind' => ['required', 'string'],
            ...parent::rules(),
        ];
    }
}
