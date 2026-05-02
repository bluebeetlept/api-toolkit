<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Tests\Acceptance\JsonApi;

use BlueBeetle\ApiToolkit\Http\Response;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Resources\AppInfoTestResource;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Resources\StatTestResource;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Resources\TimestampTestResource;
use Carbon\Carbon;
use Illuminate\Support\Facades\Route;

it('returns a timestamp resource without a model', function () {
    $date = Carbon::create(2025, 6, 15, 10, 30, 0, 'UTC');

    Route::get('/api/v1/time', function (Response $response) use ($date) {
        return $response->success(
            $date,
            TimestampTestResource::class,
        )->respond();
    });

    $response = $this->getJson('/api/v1/time');

    $response->assertStatus(200);
    $response->assertHeader('Content-Type', 'application/vnd.api+json');
    $response->assertJsonPath('data.type', 'timestamps');
    $response->assertJsonPath('data.attributes.string', '2025-06-15 10:30:00');
    $response->assertJsonPath('data.attributes.timestamp', $date->timestamp);
    expect($response->json('data.attributes'))->toHaveKey('human');
});

it('returns a collection of non-model resources', function () {
    Route::get('/api/v1/stats', function (Response $response) {
        $stats = [
            (object) ['id' => 'daily', 'label' => 'Daily Users', 'value' => 1500],
            (object) ['id' => 'weekly', 'label' => 'Weekly Users', 'value' => 8200],
        ];

        return $response->success($stats, StatTestResource::class)->respond();
    });

    $response = $this->getJson('/api/v1/stats');

    $response->assertStatus(200);
    expect($response->json('data'))->toHaveCount(2);
    $response->assertJsonPath('data.0.type', 'stats');
    $response->assertJsonPath('data.0.id', 'daily');
    $response->assertJsonPath('data.0.attributes.label', 'Daily Users');
    $response->assertJsonPath('data.0.attributes.value', 1500);
});

it('returns raw data without a resource class', function () {
    Route::get('/api/v1/health', function (Response $response) {
        return $response->success(['status' => 'ok', 'version' => '1.0.0'])->respond();
    });

    $response = $this->getJson('/api/v1/health');

    $response->assertStatus(200);
    $response->assertJsonPath('data.status', 'ok');
    $response->assertJsonPath('data.version', '1.0.0');
});

it('returns error responses in JSON:API format', function () {
    Route::get('/api/v1/fail', function (Response $response) {
        return $response
            ->error('Bad Request', 'Something went wrong', 400)
            ->code('invalid_request')
            ->respond()
        ;
    });

    $response = $this->getJson('/api/v1/fail');

    $response->assertStatus(400);
    $response->assertHeader('Content-Type', 'application/vnd.api+json');
    $response->assertJsonStructure([
        'errors' => [
            ['status', 'title', 'detail', 'code'],
        ],
    ]);
    $response->assertJsonPath('errors.0.status', '400');
    $response->assertJsonPath('errors.0.title', 'Bad Request');
    $response->assertJsonPath('errors.0.detail', 'Something went wrong');
    $response->assertJsonPath('errors.0.code', 'invalid_request');
});

it('returns error with source pointer', function () {
    Route::get('/api/v1/fail-with-source', function (Response $response) {
        return $response
            ->error('Validation Error', 'Name is required', 422)
            ->code('validation_error')
            ->source(['pointer' => '/data/attributes/name'])
            ->respond()
        ;
    });

    $response = $this->getJson('/api/v1/fail-with-source');

    $response->assertStatus(422);
    $response->assertJsonPath('errors.0.source.pointer', '/data/attributes/name');
});

it('handles resource with links and meta but no model', function () {
    Route::get('/api/v1/info', function (Response $response) {
        $data = (object) ['id' => 'app', 'name' => 'My App', 'version' => '2.0'];

        return $response->success($data, AppInfoTestResource::class)->respond();
    });

    $response = $this->getJson('/api/v1/info');

    $response->assertStatus(200);
    $response->assertJsonPath('data.type', 'app-info');
    $response->assertJsonPath('data.links.self', '/api/v1/info');
    $response->assertJsonPath('data.links.docs', 'https://docs.example.com');
    $response->assertJsonPath('data.meta.uptime', '99.9%');
});
