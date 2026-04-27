<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Tests\Fixtures\Resources;

use BlueBeetle\ApiToolkit\Resources\Resource;
use Carbon\CarbonInterface;

final class TimestampResource extends Resource
{
    protected string $type = 'timestamps';

    public function attributes(CarbonInterface $date): array
    {
        return [
            'human' => $date->diffForHumans(),
            'string' => $date->toDateTimeString(),
            'timestamp' => $date->timestamp,
        ];
    }

    public function schema(): array
    {
        return [
            'human' => 'string',
            'string' => 'datetime',
            'timestamp' => 'integer',
        ];
    }
}
