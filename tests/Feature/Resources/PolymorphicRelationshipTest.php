<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Tests\Feature\Resources;

use BadMethodCallException;
use BlueBeetle\ApiToolkit\Resources\Resource;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Models\Category;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Models\Product;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Resources\CategoryResource;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Resources\MorphStubResource;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Resources\ProductResource;

afterEach(function () {
    Resource::resetResolvers();
});

beforeEach(function () {
    Resource::map([
        Category::class => CategoryResource::class,
        Product::class => ProductResource::class,
    ]);

    $this->morph = new class() extends Resource {};

    $this->category = fn () => Category::create(['public_id' => 'c1', 'name' => 'Tools', 'slug' => 'tools']);

    $this->product = fn () => Product::create([
        'public_id' => 'p1',
        'name' => 'Widget',
        'code' => 'W01',
        'price_in_cents' => 1000,
        'featured' => false,
    ]);
});

it('serializes whichever model it is given through that model resource', function () {
    $category = ($this->category)();

    expect($this->morph->toArray($category))->toBe(new CategoryResource()->toArray($category));
});

it('serializes a different model through a different resource', function () {
    $product = ($this->product)();

    expect($this->morph->toArray($product))->toBe(new ProductResource()->toArray($product));
});

it('gives each model the type its own resource would', function () {
    $category = ($this->category)();
    $product = ($this->product)();

    expect($this->morph->resolveType($category))->toBe('categories')
        ->and($this->morph->resolveType($product))->toBe('products')
    ;
});

it('keeps the links the real resource defines', function () {
    $category = ($this->category)();

    expect($this->morph->toArray($category)['links'])->toHaveKey('self');
});

it('is used for a relationship without the owning resource knowing the type', function () {
    $owner = new class() extends Resource {
        public function attributes($model): array
        {
            return ['name' => $model->name];
        }

        public function relationships(): array
        {
            return ['thing' => MorphStubResource::class];
        }
    };

    $category = ($this->category)();
    $product = ($this->product)();
    $product->setRelation('thing', $category);

    expect($owner->toArray($product)['relationships']['thing']['data'])->toBe([
        'type' => 'categories',
        'id' => (string) $category->getKey(),
    ]);
});

it('still refuses a resource that describes nothing and has no model to fall back on', function () {
    Resource::resetResolvers();

    $category = ($this->category)();

    expect(fn () => $this->morph->toArray($category))
        ->toThrow(BadMethodCallException::class, 'attributes')
    ;
});

it('leaves a resource that declares its own attributes alone', function () {
    $product = ($this->product)();

    $explicit = new class() extends Resource {
        protected string $type = 'explicit';

        public function attributes($model): array
        {
            return ['only' => 'mine'];
        }
    };

    $result = $explicit->toArray($product);

    expect($result['type'])->toBe('explicit')
        ->and($result['attributes'])->toBe(['only' => 'mine'])
    ;
});

it('does not loop when a resource is the one registered for the model', function () {
    $category = ($this->category)();

    expect(new CategoryResource()->toArray($category)['type'])->toBe('categories');
});
