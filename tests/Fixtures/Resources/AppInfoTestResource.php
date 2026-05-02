<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Tests\Fixtures\Resources;

use BlueBeetle\ApiToolkit\Resources\Resource;

class AppInfoTestResource extends Resource
{
    protected string $type = 'app-info';

    public function attributes($info): array
    {
        return [
            'name' => $info->name,
            'version' => $info->version,
        ];
    }

    public function self($info): string | null
    {
        return '/api/v1/info';
    }

    public function links($info): array
    {
        return [
            'docs' => 'https://docs.example.com',
        ];
    }

    public function meta($info): array
    {
        return [
            'uptime' => '99.9%',
        ];
    }
}
