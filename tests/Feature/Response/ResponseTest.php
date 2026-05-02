<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Tests\Feature\Response;

use BlueBeetle\ApiToolkit\Http\Response;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Resources\StubItemResource;
use Illuminate\Http\Request;
use stdClass;

it('creates a success response with a single resource', function () {
    $response = new Response();

    $model = new stdClass();
    $model->id = '1';
    $model->name = 'Widget';

    $result = $response->success($model, StubItemResource::class)->respond();

    expect($result->getStatusCode())->toBe(200);
    expect($result->headers->get('Content-Type'))->toBe('application/vnd.api+json');

    $data = json_decode($result->getContent(), true);

    expect($data['data']['type'])->toBe('items');
    expect($data['data']['id'])->toBe('1');
    expect($data['data']['attributes']['name'])->toBe('Widget');
});

it('creates a success response with null data', function () {
    $response = new Response();

    $result = $response->success()->respond();
    $data = json_decode($result->getContent(), true);

    expect($data['data'])->toBeNull();
});

it('creates a success response with custom status code', function () {
    $response = new Response();

    $model = new stdClass();
    $model->id = '1';
    $model->name = 'Created';

    $result = $response->success($model, StubItemResource::class)->respond(201);

    expect($result->getStatusCode())->toBe(201);
});

it('creates a success response with extra meta', function () {
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

    expect($data['meta']['request_id'])->toBe('req-abc');
});

it('creates a success response with a collection', function () {
    $response = new Response();

    $items = [
        (object) ['id' => '1', 'name' => 'Widget'],
        (object) ['id' => '2', 'name' => 'Gadget'],
    ];

    $result = $response->success($items, StubItemResource::class)->respond();
    $data = json_decode($result->getContent(), true);

    expect($data['data'])->toHaveCount(2);
    expect($data['data'][0]['attributes']['name'])->toBe('Widget');
    expect($data['data'][1]['attributes']['name'])->toBe('Gadget');
});

it('creates an error response', function () {
    $response = new Response();

    $result = $response->error('Bad Request', 'Missing required field: name', 400)->respond();

    expect($result->getStatusCode())->toBe(400);
    expect($result->headers->get('Content-Type'))->toBe('application/vnd.api+json');

    $data = json_decode($result->getContent(), true);

    expect($data['errors'][0]['status'])->toBe('400');
    expect($data['errors'][0]['title'])->toBe('Bad Request');
    expect($data['errors'][0]['detail'])->toBe('Missing required field: name');
});

it('creates an error response with code and source', function () {
    $response = new Response();

    $result = $response
        ->error('Validation Error', 'Name is required', 422)
        ->code('validation_error')
        ->source(['pointer' => '/data/attributes/name'])
        ->respond()
    ;

    $data = json_decode($result->getContent(), true);

    expect($data['errors'][0]['code'])->toBe('validation_error');
    expect($data['errors'][0]['source']['pointer'])->toBe('/data/attributes/name');
});

it('creates a success response without a resource class', function () {
    $response = new Response();

    $result = $response->success(['raw' => 'data'])->respond();
    $data = json_decode($result->getContent(), true);

    expect($data['data'])->toBe(['raw' => 'data']);
});

it('creates a success response with links', function () {
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

    expect($data['links']['self'])->toBe('/api/v1/items/1');
});

it('creates a success response with custom headers', function () {
    $response = new Response();

    $result = $response->success()->respond(200, ['X-Request-Id' => 'req-123']);

    expect($result->headers->get('X-Request-Id'))->toBe('req-123');
    expect($result->headers->get('Content-Type'))->toBe('application/vnd.api+json');
});

it('creates an error response with meta', function () {
    $response = new Response();

    $result = $response
        ->error('Bad Request', 'Something went wrong', 400)
        ->meta(['request_id' => 'req-abc'])
        ->respond()
    ;

    $data = json_decode($result->getContent(), true);

    expect($data['errors'][0]['meta']['request_id'])->toBe('req-abc');
});

it('creates an error response with custom headers', function () {
    $response = new Response();

    $result = $response
        ->error('Too Many Requests', 'Rate limit exceeded', 429)
        ->respond(null, ['Retry-After' => '60'])
    ;

    expect($result->getStatusCode())->toBe(429);
    expect($result->headers->get('Retry-After'))->toBe('60');
});

it('creates an error response with status override', function () {
    $response = new Response();

    $result = $response
        ->error('Error', 'detail', 400)
        ->respond(503)
    ;

    expect($result->getStatusCode())->toBe(503);

    $data = json_decode($result->getContent(), true);
    expect($data['errors'][0]['status'])->toBe('400');
});

it('implements Responsable for success response', function () {
    $response = new Response();

    $successResponse = $response->success(['test' => true]);
    $result = $successResponse->toResponse(Request::create('/'));

    expect($result->getStatusCode())->toBe(200);
});

it('implements Responsable for error response', function () {
    $response = new Response();

    $errorResponse = $response->error('Bad Request', 'detail', 400);
    $result = $errorResponse->toResponse(Request::create('/'));

    expect($result->getStatusCode())->toBe(400);
});

it('omits optional fields when not set in error response', function () {
    $response = new Response();

    $result = $response->error('Bad Request')->respond();
    $data = json_decode($result->getContent(), true);
    $error = $data['errors'][0];

    expect($error)->not->toHaveKey('code');
    expect($error)->not->toHaveKey('detail');
    expect($error)->not->toHaveKey('source');
    expect($error)->not->toHaveKey('meta');
});
