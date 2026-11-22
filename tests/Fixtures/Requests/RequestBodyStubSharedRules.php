<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Tests\Fixtures\Requests;

trait RequestBodyStubSharedRules
{
    /**
     * @return array<string, mixed>
     */
    protected function sharedRules(): array
    {
        return [
            /*
             * Where the order came from.
             *
             * @example phone
             */
            'channel' => ['nullable', 'string'],
        ];
    }
}
