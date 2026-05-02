<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Tests\Feature\Testing;

use BlueBeetle\ApiToolkit\Testing\TestDataResponse;
use BlueBeetle\ApiToolkit\Testing\TestResponse;
use Illuminate\Http\JsonResponse;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

final class TestResponseTest extends TestCase
{
    #[Test]
    #[TestDox('it returns errors as TestDataResponse')]
    public function it_returns_errors(): void
    {
        $response = $this->makeResponse([
            'errors' => [
                ['status' => '400', 'title' => 'Bad Request', 'detail' => 'Something went wrong'],
            ],
        ]);

        $errors = $response->errors();

        $this->assertInstanceOf(TestDataResponse::class, $errors);
        $errors->item(0)->assertSame('title', 'Bad Request');
    }

    #[Test]
    #[TestDox('it returns data as TestDataResponse')]
    public function it_returns_data(): void
    {
        $response = $this->makeResponse([
            'data' => ['type' => 'products', 'id' => '1', 'attributes' => ['name' => 'Widget']],
        ]);

        $data = $response->data();

        $this->assertInstanceOf(TestDataResponse::class, $data);
        $data->assertSame('type', 'products');
    }

    #[Test]
    #[TestDox('it returns attributes as shortcut')]
    public function it_returns_attributes(): void
    {
        $response = $this->makeResponse([
            'data' => ['type' => 'products', 'id' => '1', 'attributes' => ['name' => 'Widget']],
        ]);

        $response->attributes()->assertSame('name', 'Widget');
    }

    #[Test]
    #[TestDox('it returns included as TestDataResponse')]
    public function it_returns_included(): void
    {
        $response = $this->makeResponse([
            'data' => [],
            'included' => [
                ['type' => 'categories', 'id' => '1', 'attributes' => ['name' => 'Electronics']],
            ],
        ]);

        $response->included()->assertCount(1);
        $response->included()->item(0)->assertSame('type', 'categories');
    }

    #[Test]
    #[TestDox('it returns empty TestDataResponse when included is missing')]
    public function it_handles_missing_included(): void
    {
        $response = $this->makeResponse(['data' => []]);

        $response->included()->assertEmpty();
    }

    #[Test]
    #[TestDox('it returns meta as TestDataResponse')]
    public function it_returns_meta(): void
    {
        $response = $this->makeResponse([
            'data' => [],
            'meta' => ['page' => ['currentPage' => 1, 'total' => 50]],
        ]);

        $response->meta()->assertHasKey('page');
    }

    #[Test]
    #[TestDox('it asserts error title')]
    public function it_asserts_error_title(): void
    {
        $response = $this->makeResponse([
            'errors' => [['status' => '400', 'title' => 'Bad Request']],
        ]);

        $response->assertErrorTitle('Bad Request');
    }

    #[Test]
    #[TestDox('it asserts error detail')]
    public function it_asserts_error_detail(): void
    {
        $response = $this->makeResponse([
            'errors' => [['status' => '400', 'title' => 'Bad Request', 'detail' => 'Missing field: name']],
        ]);

        $response->assertErrorDetail('Missing field: name');
    }

    #[Test]
    #[TestDox('it asserts error detail contains substring')]
    public function it_asserts_error_detail_contains(): void
    {
        $response = $this->makeResponse([
            'errors' => [['status' => '400', 'detail' => 'Missing required field: name']],
        ]);

        $response->assertErrorDetailContains('required field');
    }

    #[Test]
    #[TestDox('it fails assertErrorDetailContains when substring not found')]
    public function it_fails_error_detail_contains(): void
    {
        $this->expectException(AssertionFailedError::class);

        $response = $this->makeResponse([
            'errors' => [['detail' => 'Something else']],
        ]);

        $response->assertErrorDetailContains('not present');
    }

    #[Test]
    #[TestDox('it asserts error code')]
    public function it_asserts_error_code(): void
    {
        $response = $this->makeResponse([
            'errors' => [['code' => 'validation_error']],
        ]);

        $response->assertErrorCode('validation_error');
    }

    #[Test]
    #[TestDox('it asserts validation error with field and message')]
    public function it_asserts_validation_error(): void
    {
        $response = $this->makeResponse([
            'errors' => [
                ['detail' => 'The name field is required.', 'source' => ['pointer' => '/name']],
                ['detail' => 'The email must be valid.', 'source' => ['pointer' => '/email']],
            ],
        ]);

        $response->assertValidationError('name', 'The name field is required.');
        $response->assertValidationError('email', 'The email must be valid.');
    }

    #[Test]
    #[TestDox('it asserts validation error with nested field pointer')]
    public function it_asserts_nested_validation_error(): void
    {
        $response = $this->makeResponse([
            'errors' => [
                ['detail' => 'Invalid value.', 'source' => ['pointer' => '/data/attributes/name']],
            ],
        ]);

        $response->assertValidationError('data.attributes.name', 'Invalid value.');
    }

    #[Test]
    #[TestDox('it fails assertValidationError when field not found')]
    public function it_fails_validation_error(): void
    {
        $this->expectException(AssertionFailedError::class);

        $response = $this->makeResponse([
            'errors' => [
                ['detail' => 'Required.', 'source' => ['pointer' => '/name']],
            ],
        ]);

        $response->assertValidationError('email', 'Required.');
    }

    #[Test]
    #[TestDox('it asserts resource type')]
    public function it_asserts_resource_type(): void
    {
        $response = $this->makeResponse([
            'data' => ['type' => 'products', 'id' => '1'],
        ]);

        $response->assertResourceType('products');
    }

    #[Test]
    #[TestDox('it asserts resource id')]
    public function it_asserts_resource_id(): void
    {
        $response = $this->makeResponse([
            'data' => ['type' => 'products', 'id' => 'abc-123'],
        ]);

        $response->assertResourceId('abc-123');
    }

    #[Test]
    #[TestDox('it returns self from assertion methods for chaining')]
    public function it_supports_chaining(): void
    {
        $response = $this->makeResponse([
            'errors' => [
                ['status' => '422', 'code' => 'validation_error', 'title' => 'Validation Error', 'detail' => 'Name is required.'],
            ],
        ]);

        $result = $response
            ->assertErrorTitle('Validation Error')
            ->assertErrorDetail('Name is required.')
            ->assertErrorCode('validation_error')
        ;

        $this->assertInstanceOf(TestResponse::class, $result);
    }

    private function makeResponse(array $data, int $status = 200): TestResponse
    {
        $jsonResponse = new JsonResponse($data, $status);

        return TestResponse::fromBaseResponse($jsonResponse);
    }
}
