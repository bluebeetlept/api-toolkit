<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class ETag
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $this->shouldProcess($request, $response)) {
            return $response;
        }

        $etag = $this->generateETag($response);
        $response->headers->set('ETag', $etag);

        if ($this->isNotModified($request, $etag)) {
            $response->setStatusCode(304);
            $response->setContent('');
        }

        return $response;
    }

    private function shouldProcess(Request $request, Response $response): bool
    {
        if (! $request->isMethodSafe()) {
            return false;
        }

        if (! $response->isSuccessful()) {
            return false;
        }

        $content = $response->getContent();

        return $content !== false && $content !== '';
    }

    private function generateETag(Response $response): string
    {
        return '"'.md5($response->getContent()).'"';
    }

    private function isNotModified(Request $request, string $etag): bool
    {
        $ifNoneMatch = $request->headers->get('If-None-Match');

        if ($ifNoneMatch === null) {
            return false;
        }

        return $ifNoneMatch === $etag;
    }
}
