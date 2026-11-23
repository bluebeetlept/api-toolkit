<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Tests\Acceptance\Http\Requests\Stubs;

use BlueBeetle\ApiToolkit\Http\Requests\FormRequest;

final class FormRequestWithAllowedIncludes extends FormRequest
{
    public function allowedIncludes(): array
    {
        return ['category', 'supplier'];
    }
}
