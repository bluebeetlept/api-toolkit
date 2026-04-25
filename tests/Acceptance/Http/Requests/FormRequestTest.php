<?php

declare(strict_types = 1);

namespace Eufaturo\ApiToolkit\Tests\Acceptance\Http\Requests;

use Eufaturo\ApiToolkit\Tests\Acceptance\Http\Requests\Stubs\FormRequestWithFormInputRules;
use Eufaturo\ApiToolkit\Tests\Acceptance\Http\Requests\Stubs\FormRequestWithQueryParamRules;
use Eufaturo\ApiToolkit\Tests\TestCase;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use Symfony\Component\HttpFoundation\Response;

final class FormRequestTest extends TestCase
{
    #[Test]
    #[TestDox('it validates query params')]
    public function it_validates_query_params(): void
    {
        Route::get('/', fn (FormRequestWithQueryParamRules $request) => response()->json(['ok' => true]));

        $response = $this->get('/?include[]=category');

        $response->assertStatus(Response::HTTP_OK);
    }

    #[Test]
    #[TestDox('it fails on missing required query param')]
    public function it_fails_on_missing_required(): void
    {
        Route::get('/', fn (FormRequestWithQueryParamRules $request) => response()->json(['ok' => true]));

        $response = $this->getJson('/');

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    #[Test]
    #[TestDox('it rejects unknown query params')]
    public function it_rejects_unknown_query_params(): void
    {
        Route::get('/', fn (FormRequestWithQueryParamRules $request) => response()->json(['ok' => true]));

        $response = $this->getJson('/?include[]=category&unknown=value');

        $response->assertStatus(Response::HTTP_BAD_REQUEST);
    }

    #[Test]
    #[TestDox('it validates form data')]
    public function it_validates_form_data(): void
    {
        Route::post('/', fn (FormRequestWithFormInputRules $request) => response()->json(['ok' => true]));

        $response = $this->postJson('/', ['name' => 'John']);

        $response->assertStatus(Response::HTTP_OK);
    }

    #[Test]
    #[TestDox('it fails on missing required form field')]
    public function it_fails_on_missing_form_field(): void
    {
        Route::post('/', fn (FormRequestWithFormInputRules $request) => response()->json(['ok' => true]));

        $response = $this->postJson('/', []);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    #[Test]
    #[TestDox('it rejects unknown form fields')]
    public function it_rejects_unknown_form_fields(): void
    {
        Route::post('/', fn (FormRequestWithFormInputRules $request) => response()->json(['ok' => true]));

        $response = $this->postJson('/', ['name' => 'John', 'unknown' => 'value']);

        $response->assertStatus(Response::HTTP_BAD_REQUEST);
    }
}
