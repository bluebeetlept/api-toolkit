<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Tests\Fixtures\Requests;

use BlueBeetle\ApiToolkit\Http\Requests\FormRequest;

class DocBuilderStubShowFormRequest extends FormRequest
{
    public function allowedIncludes(): array
    {
        return ['category', 'supplier'];
    }
}
