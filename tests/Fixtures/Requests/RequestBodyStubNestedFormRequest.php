<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Tests\Fixtures\Requests;

use BlueBeetle\ApiToolkit\Http\Requests\FormRequest;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Enums\OpenApiStubPriority;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Enums\OpenApiStubStatus;
use Illuminate\Validation\Rule;

class RequestBodyStubNestedFormRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            /*
             * Where the goods leave from.
             */
            'transport' => ['required', 'array'],
            'transport.origin' => ['required', 'array'],
            'transport.origin.city' => ['required', 'string', 'max:100'],
            'transport.origin.postal_code' => ['nullable', 'string'],
            'lines' => ['required', 'array', 'min:1'],
            /*
             * @example 2.5
             */
            'lines.*.quantity' => ['required', 'numeric', 'min:0'],
            'lines.*.status' => ['nullable', Rule::enum(OpenApiStubStatus::class)->only([OpenApiStubStatus::Active])],
            'priority' => [Rule::enum(OpenApiStubPriority::class)],
            'channel' => ['required', Rule::in(['web', 'pos'])],
            'size' => ['in:small,large'],
            /*
             * The customer's own reference.
             *
             * @example PO-2026-001
             */
            'reference' => ['required_if:channel,web', 'string'],
            /*
             * @example 3
             */
            'count' => ['integer', 'between:1,10'],
        ];
    }
}
