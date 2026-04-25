<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Tests\Fixtures\Resources;

use BlueBeetle\ApiToolkit\Resources\Resource;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Models\Tag;

final class TagResource extends Resource
{
    protected string $model = Tag::class;

    public function attributes(Tag $tag): array
    {
        return [
            'name' => $tag->name,
        ];
    }

    public function schema(): array
    {
        return [
            'name' => 'string',
        ];
    }
}
