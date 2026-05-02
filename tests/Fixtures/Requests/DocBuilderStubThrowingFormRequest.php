<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Tests\Fixtures\Requests;

use RuntimeException;

class DocBuilderStubThrowingFormRequest
{
    public function rules(): array
    {
        throw new RuntimeException('Cannot instantiate');
    }
}
