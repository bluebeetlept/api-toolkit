<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Http;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

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

    public function noContent(): SymfonyResponse
    {
        return new SymfonyResponse(
            content: '',
            status: 204,
            headers: ['Content-Type' => 'application/vnd.api+json'],
        );
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
