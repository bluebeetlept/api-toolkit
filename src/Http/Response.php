<?php

declare(strict_types = 1);

namespace Eufaturo\ApiToolkit\Http;

use Eufaturo\ApiToolkit\Resources\Resource;
use Illuminate\Http\Request;

final readonly class Response
{
    public function __construct(
        private Request | null $request = null,
    ) {
    }

    /**
     * @param class-string<resource>|null $resource
     */
    public function success(mixed $data = null, string | null $resource = null): SuccessResponse
    {
        $response = new SuccessResponse(
            data: $data,
            resource: $resource,
        );

        if ($this->request !== null) {
            $response->withRequest($this->request);
        }

        return $response;
    }

    public function error(string $title, string | null $detail = null, int $status = 400): ErrorResponse
    {
        return new ErrorResponse(
            title: $title,
            detail: $detail,
            status: $status,
        );
    }
}
