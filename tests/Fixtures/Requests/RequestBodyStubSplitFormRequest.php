<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Tests\Fixtures\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RequestBodyStubSplitFormRequest extends FormRequest
{
    use RequestBodyStubSharedRules;

    public function rules(): array
    {
        return [
            ...$this->sharedRules(),
            ...$this->contactRules(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function contactRules(): array
    {
        return [
            /*
             * @example ana@example.com
             */
            'email' => ['nullable', 'email'],
        ];
    }
}
