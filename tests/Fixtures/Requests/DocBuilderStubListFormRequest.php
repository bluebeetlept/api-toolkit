<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Tests\Fixtures\Requests;

use BlueBeetle\ApiToolkit\Http\Requests\FormRequest;

class DocBuilderStubListFormRequest extends FormRequest
{
    public function queryParamRules(): array
    {
        return [
            'include' => ['string'],
            /*
             * Only archived stubs (true), or only the others (false).
             */
            'archived' => ['boolean'],
        ];
    }
}
