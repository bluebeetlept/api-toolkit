<?php

declare(strict_types = 1);

namespace Eufaturo\ApiToolkit\Tests\Fixtures\Resources;

use Carbon\CarbonInterface;
use Eufaturo\ApiToolkit\Resources\Resource;

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
