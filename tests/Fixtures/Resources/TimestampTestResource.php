<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Tests\Fixtures\Resources;

use BlueBeetle\ApiToolkit\Resources\Resource;

class TimestampTestResource extends Resource
{
    protected string $type = 'timestamps';

    public function attributes($date): array
    {
        return [
            'human' => $date->diffForHumans(),
            'string' => $date->toDateTimeString(),
            'timestamp' => $date->timestamp,
        ];
    }
}
