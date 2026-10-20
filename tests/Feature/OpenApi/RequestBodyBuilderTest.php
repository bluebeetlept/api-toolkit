<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Tests\Feature\OpenApi;

use BlueBeetle\ApiToolkit\OpenApi\RequestBodyBuilder;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Models\Product;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Requests\DocBuilderStubObjectRuleFormRequest;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Requests\RequestBodyStubChildFormRequest;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Requests\RequestBodyStubContextFormRequest;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Requests\RequestBodyStubLongEnumFormRequest;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Requests\RequestBodyStubNestedFormRequest;
use Illuminate\Foundation\Auth\User;
use Illuminate\Foundation\Http\FormRequest;

afterEach(function () {
    RequestBodyBuilder::prepareRequestsUsing(null);
});

/**
 * @param class-string $formRequestClass
 *
 * @return array<string, mixed>
 */
function requestBodySchema(string $formRequestClass): array
{
    $body = (new RequestBodyBuilder())->build($formRequestClass, 'POST');

    expect($body)->not->toBeNull();

    return $body['content']['application/json']['schema'];
}

it('describes dotted rules as nested objects', function () {
    $schema = requestBodySchema(RequestBodyStubNestedFormRequest::class);

    $transport = $schema['properties']['transport'];

    expect($transport['type'])->toBe('object')
        ->and($transport['required'])->toBe(['origin'])
        ->and($transport['properties']['origin']['required'])->toBe(['city'])
        ->and($transport['properties']['origin']['properties']['city'])->toBe(['type' => 'string', 'maxLength' => 100])
        ->and($transport['properties']['origin']['properties']['postal_code']['type'])->toBe(['string', 'null'])
    ;
});

it('describes starred rules as arrays of their items', function () {
    $lines = requestBodySchema(RequestBodyStubNestedFormRequest::class)['properties']['lines'];

    expect($lines['type'])->toBe('array')
        ->and($lines['minItems'])->toBe(1)
        ->and($lines['items']['type'])->toBe('object')
        ->and($lines['items']['required'])->toBe(['quantity'])
        ->and($lines['items']['properties']['quantity'])->toBe(['type' => 'number', 'minimum' => 0.0, 'examples' => [2.5]])
    ;
});

it('lists the values an enum or in rule allows', function () {
    $properties = requestBodySchema(RequestBodyStubNestedFormRequest::class)['properties'];

    expect($properties['lines']['items']['properties']['status'])->toBe(['type' => ['string', 'null'], 'enum' => ['active']])
        ->and($properties['priority'])->toBe(['type' => 'integer', 'enum' => [1, 2]])
        ->and($properties['channel'])->toBe(['type' => 'string', 'enum' => ['web', 'pos']])
        ->and($properties['size'])->toBe(['type' => 'string', 'enum' => ['small', 'large']])
    ;
});

it('takes descriptions and examples from the comments above the rules', function () {
    $properties = requestBodySchema(RequestBodyStubNestedFormRequest::class)['properties'];

    expect($properties['transport']['description'])->toBe('Where the goods leave from.')
        ->and($properties['reference']['examples'])->toBe(['PO-2026-001'])
        ->and($properties['count'])->toBe(['type' => 'integer', 'minimum' => 1, 'maximum' => 10, 'examples' => [3]])
    ;
});

it('says when a field is only required sometimes', function () {
    $schema = requestBodySchema(RequestBodyStubNestedFormRequest::class);

    expect($schema['required'])->toBe(['transport', 'lines', 'channel'])
        ->and($schema['properties']['reference']['description'])->toBe("The customer's own reference. Required when `channel` is `web`.")
    ;
});

it('reads comments from the requests a request extends', function () {
    $properties = requestBodySchema(RequestBodyStubChildFormRequest::class)['properties'];

    expect($properties['kind']['examples'])->toBe(['rush'])
        ->and($properties['reference']['examples'])->toBe(['PO-2026-001'])
    ;
});

it('gives requests a stand-in for the context their rules need', function () {
    expect((new RequestBodyBuilder())->build(RequestBodyStubContextFormRequest::class, 'POST'))->toBeNull();

    RequestBodyBuilder::prepareRequestsUsing(function (FormRequest $request): void {
        $request->setUserResolver(fn (): User => new User());
    });

    expect(requestBodySchema(RequestBodyStubContextFormRequest::class)['properties']['note'])->toBe(['type' => 'string']);
});

it('describes nothing for a class that is not a form request', function () {
    expect((new RequestBodyBuilder())->build(Product::class, 'POST'))->toBeNull();
});

it('leaves a field only a rule object knows as an open schema', function () {
    $body = (new RequestBodyBuilder())->build(DocBuilderStubObjectRuleFormRequest::class, 'POST');

    expect(json_encode($body['content']['application/json']['schema']))->toBe('{"type":"object","properties":{"name":{}},"required":["name"]}');
});

it('leaves out an enum too long to read', function () {
    $schema = requestBodySchema(RequestBodyStubLongEnumFormRequest::class);

    expect($schema['properties']['code'])->toBe(['type' => 'string']);
});
