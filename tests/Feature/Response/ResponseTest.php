<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Tests\Feature\Response;

use BlueBeetle\ApiToolkit\Http\Response;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Resources\StubItemResource;
use BlueBeetle\ApiToolkit\Tests\TestCase;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use stdClass;

final class ResponseTest extends TestCase
{
    #[Test]
    #[TestDox('it creates a success response with a single resource')]
    public function it_creates_success_response(): void
    {
        $response = new Response();

        $model = new stdClass();
        $model->id = '1';
        $model->name = 'Widget';

        $result = $response->success($model, StubItemResource::class)->respond();

        $this->assertSame(200, $result->getStatusCode());
        $this->assertSame('application/vnd.api+json', $result->headers->get('Content-Type'));

        $data = json_decode($result->getContent(), true);

        $this->assertSame('items', $data['data']['type']);
        $this->assertSame('1', $data['data']['id']);
        $this->assertSame('Widget', $data['data']['attributes']['name']);
    }

    #[Test]
    #[TestDox('it creates a success response with null data')]
    public function it_creates_success_with_null(): void
    {
        $response = new Response();

        $result = $response->success()->respond();
        $data = json_decode($result->getContent(), true);

        $this->assertNull($data['data']);
    }

    #[Test]
    #[TestDox('it creates a success response with custom status code')]
    public function it_creates_success_with_custom_status(): void
    {
        $response = new Response();

        $model = new stdClass();
        $model->id = '1';
        $model->name = 'Created';

        $result = $response->success($model, StubItemResource::class)->respond(201);

        $this->assertSame(201, $result->getStatusCode());
    }

    #[Test]
    #[TestDox('it creates a success response with extra meta')]
    public function it_creates_success_with_meta(): void
    {
        $response = new Response();

        $model = new stdClass();
        $model->id = '1';
        $model->name = 'Widget';

        $result = $response
            ->success($model, StubItemResource::class)
            ->meta(['request_id' => 'req-abc'])
            ->respond()
        ;

        $data = json_decode($result->getContent(), true);

        $this->assertSame('req-abc', $data['meta']['request_id']);
    }

    #[Test]
    #[TestDox('it creates a success response with a collection')]
    public function it_creates_success_with_collection(): void
    {
        $response = new Response();

        $items = [
            (object) ['id' => '1', 'name' => 'Widget'],
            (object) ['id' => '2', 'name' => 'Gadget'],
        ];

        $result = $response->success($items, StubItemResource::class)->respond();
        $data = json_decode($result->getContent(), true);

        $this->assertCount(2, $data['data']);
        $this->assertSame('Widget', $data['data'][0]['attributes']['name']);
        $this->assertSame('Gadget', $data['data'][1]['attributes']['name']);
    }

    #[Test]
    #[TestDox('it creates an error response')]
    public function it_creates_error_response(): void
    {
        $response = new Response();

        $result = $response->error('Bad Request', 'Missing required field: name', 400)->respond();

        $this->assertSame(400, $result->getStatusCode());
        $this->assertSame('application/vnd.api+json', $result->headers->get('Content-Type'));

        $data = json_decode($result->getContent(), true);

        $this->assertSame('400', $data['errors'][0]['status']);
        $this->assertSame('Bad Request', $data['errors'][0]['title']);
        $this->assertSame('Missing required field: name', $data['errors'][0]['detail']);
    }

    #[Test]
    #[TestDox('it creates an error response with code and source')]
    public function it_creates_error_with_code_and_source(): void
    {
        $response = new Response();

        $result = $response
            ->error('Validation Error', 'Name is required', 422)
            ->code('validation_error')
            ->source(['pointer' => '/data/attributes/name'])
            ->respond()
        ;

        $data = json_decode($result->getContent(), true);

        $this->assertSame('validation_error', $data['errors'][0]['code']);
        $this->assertSame('/data/attributes/name', $data['errors'][0]['source']['pointer']);
    }

    #[Test]
    #[TestDox('it creates a success response without a resource class')]
    public function it_creates_success_without_resource(): void
    {
        $response = new Response();

        $result = $response->success(['raw' => 'data'])->respond();
        $data = json_decode($result->getContent(), true);

        $this->assertSame(['raw' => 'data'], $data['data']);
    }

    #[Test]
    #[TestDox('it creates a success response with links')]
    public function it_creates_success_with_links(): void
    {
        $response = new Response();

        $model = new stdClass();
        $model->id = '1';
        $model->name = 'Widget';

        $result = $response
            ->success($model, StubItemResource::class)
            ->links(['self' => '/api/v1/items/1'])
            ->respond()
        ;

        $data = json_decode($result->getContent(), true);

        $this->assertSame('/api/v1/items/1', $data['links']['self']);
    }

    #[Test]
    #[TestDox('it creates a success response with custom headers')]
    public function it_creates_success_with_custom_headers(): void
    {
        $response = new Response();

        $result = $response->success()->respond(200, ['X-Request-Id' => 'req-123']);

        $this->assertSame('req-123', $result->headers->get('X-Request-Id'));
        $this->assertSame('application/vnd.api+json', $result->headers->get('Content-Type'));
    }

    #[Test]
    #[TestDox('it creates an error response with meta')]
    public function it_creates_error_with_meta(): void
    {
        $response = new Response();

        $result = $response
            ->error('Bad Request', 'Something went wrong', 400)
            ->meta(['request_id' => 'req-abc'])
            ->respond()
        ;

        $data = json_decode($result->getContent(), true);

        $this->assertSame('req-abc', $data['errors'][0]['meta']['request_id']);
    }

    #[Test]
    #[TestDox('it creates an error response with custom headers')]
    public function it_creates_error_with_custom_headers(): void
    {
        $response = new Response();

        $result = $response
            ->error('Too Many Requests', 'Rate limit exceeded', 429)
            ->respond(null, ['Retry-After' => '60'])
        ;

        $this->assertSame(429, $result->getStatusCode());
        $this->assertSame('60', $result->headers->get('Retry-After'));
    }

    #[Test]
    #[TestDox('it creates an error response with status override')]
    public function it_creates_error_with_status_override(): void
    {
        $response = new Response();

        $result = $response
            ->error('Error', 'detail', 400)
            ->respond(503)
        ;

        $this->assertSame(503, $result->getStatusCode());

        $data = json_decode($result->getContent(), true);
        // toArray still uses the original status
        $this->assertSame('400', $data['errors'][0]['status']);
    }

    #[Test]
    #[TestDox('it implements Responsable for success response')]
    public function it_implements_responsable_for_success(): void
    {
        $response = new Response();

        $successResponse = $response->success(['test' => true]);
        $result = $successResponse->toResponse(Request::create('/'));

        $this->assertSame(200, $result->getStatusCode());
    }

    #[Test]
    #[TestDox('it implements Responsable for error response')]
    public function it_implements_responsable_for_error(): void
    {
        $response = new Response();

        $errorResponse = $response->error('Bad Request', 'detail', 400);
        $result = $errorResponse->toResponse(Request::create('/'));

        $this->assertSame(400, $result->getStatusCode());
    }

    #[Test]
    #[TestDox('error response omits optional fields when not set')]
    public function it_omits_optional_error_fields(): void
    {
        $response = new Response();

        $result = $response->error('Bad Request')->respond();
        $data = json_decode($result->getContent(), true);
        $error = $data['errors'][0];

        $this->assertArrayNotHasKey('code', $error);
        $this->assertArrayNotHasKey('detail', $error);
        $this->assertArrayNotHasKey('source', $error);
        $this->assertArrayNotHasKey('meta', $error);
    }
}
