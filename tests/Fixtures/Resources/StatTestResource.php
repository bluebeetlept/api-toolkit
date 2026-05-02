<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Tests\Fixtures\Resources;

use BlueBeetle\ApiToolkit\Resources\Resource;

class StatTestResource extends Resource
{
    protected string $type = 'stats';

    public function attributes($stat): array
    {
        return [
            'label' => $stat->label,
            'value' => $stat->value,
        ];
    }
}
