<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Tests\Feature\Console;

beforeEach(function () {
    $this->resourcePath = app_path('Http/Resources');
});

afterEach(function () {
    $files = glob($this->resourcePath.'/*.php');

    foreach ($files as $file) {
        unlink($file);
    }

    if (is_dir($this->resourcePath)) {
        rmdir($this->resourcePath);
    }

    $parentDir = app_path('Http');

    if (is_dir($parentDir) && count(glob($parentDir.'/*')) === 0) {
        rmdir($parentDir);
    }
});

it('creates a resource with a model', function () {
    $this->artisan('api-toolkit:make-resource', ['name' => 'Product'])
        ->assertSuccessful()
    ;

    expect($this->resourcePath.'/ProductResource.php')->toBeFile();

    $content = file_get_contents($this->resourcePath.'/ProductResource.php');
    expect($content)->toContain('class ProductResource extends Resource');
    expect($content)->toContain('App\Models\Product');
    expect($content)->toContain('Product::class');
});

it('creates a resource with explicit model option', function () {
    $this->artisan('api-toolkit:make-resource', [
        'name' => 'Item',
        '--model' => 'App\Domain\Models\Item',
    ])->assertSuccessful();

    $content = file_get_contents($this->resourcePath.'/ItemResource.php');
    expect($content)->toContain('App\Domain\Models\Item');
});

it('creates a plain resource without a model', function () {
    $this->artisan('api-toolkit:make-resource', [
        'name' => 'Timestamp',
        '--plain' => true,
    ])->assertSuccessful();

    $content = file_get_contents($this->resourcePath.'/TimestampResource.php');
    expect($content)->toContain('class TimestampResource extends Resource');
    expect($content)->toContain("type = 'timestamp'");
    expect($content)->not->toContain('$model =');
});

it('appends Resource suffix when not provided', function () {
    $this->artisan('api-toolkit:make-resource', ['name' => 'Category'])
        ->assertSuccessful()
    ;

    expect($this->resourcePath.'/CategoryResource.php')->toBeFile();
});

it('does not duplicate Resource suffix', function () {
    $this->artisan('api-toolkit:make-resource', ['name' => 'CategoryResource'])
        ->assertSuccessful()
    ;

    expect($this->resourcePath.'/CategoryResource.php')->toBeFile();
    expect($this->resourcePath.'/CategoryResourceResource.php')->not->toBeFile();
});

it('fails when resource already exists', function () {
    $this->artisan('api-toolkit:make-resource', ['name' => 'Product'])
        ->assertSuccessful()
    ;

    $this->artisan('api-toolkit:make-resource', ['name' => 'Product'])
        ->assertFailed()
    ;
});

it('derives type name for plain resources', function () {
    $this->artisan('api-toolkit:make-resource', [
        'name' => 'UserProfile',
        '--plain' => true,
    ])->assertSuccessful();

    $content = file_get_contents($this->resourcePath.'/UserProfileResource.php');
    expect($content)->toContain("type = 'user-profile'");
});

it('uses camelCase variable name for model', function () {
    $this->artisan('api-toolkit:make-resource', ['name' => 'ProductCategory'])
        ->assertSuccessful()
    ;

    $content = file_get_contents($this->resourcePath.'/ProductCategoryResource.php');
    expect($content)->toContain('$productCategory');
});
