<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Tests\Fixtures\Enums;

enum OpenApiStubStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
}
