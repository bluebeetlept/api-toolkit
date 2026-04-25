<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Parsers;

use Illuminate\Http\Request;

final readonly class FieldParser
{
    /**
     * Parse sparse fieldsets from the request.
     *
     * Expects: ?fields[type]=field1,field2
     *
     * @return array<string, list<string>> Keyed by resource type
     */
    public function parse(Request $request): array
    {
        $fields = $request->query('fields', []);

        if (! is_array($fields)) {
            return [];
        }

        $parsed = [];

        foreach ($fields as $type => $fieldList) {
            if (! is_string($fieldList) || $fieldList === '') {
                continue;
            }

            $parsed[$type] = array_map('trim', explode(',', $fieldList));
        }

        return $parsed;
    }

    /**
     * Filter attributes array to only include requested fields.
     *
     * @param array<string, mixed> $attributes
     * @param list<string>|null    $fields
     *
     * @return array<string, mixed>
     */
    public function filter(array $attributes, array | null $fields): array
    {
        if ($fields === null || $fields === []) {
            return $attributes;
        }

        return array_intersect_key($attributes, array_flip($fields));
    }
}
