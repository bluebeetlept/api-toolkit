<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Tests\Feature\Resources;

use BadMethodCallException;
use BlueBeetle\ApiToolkit\Resources\Resource;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Models\Product;
use stdClass;

it('serializes a model to JSON:API resource object', function () {
    $resource = new class() extends Resource {
        protected string $type = 'products';

        public function attributes($model): array
        {
            return [
                'name' => $model->name,
                'code' => $model->code,
            ];
        }
    };

    $model = new stdClass();
    $model->id = 'abc-123';
    $model->name = 'Widget';
    $model->code = 'W01';

    $result = $resource->toArray($model);

    expect($result['type'])->toBe('products');
    expect($result['id'])->toBe('abc-123');
    expect($result['attributes']['name'])->toBe('Widget');
    expect($result['attributes']['code'])->toBe('W01');
    expect($result['relationships'])->toBe([]);
    expect($result['links'])->toBe([]);
    expect($result['meta'])->toBe([]);
});

it('returns null when model is null', function () {
    $resource = new class() extends Resource {
        public function attributes($model): array
        {
            return [];
        }
    };

    expect($resource->toArray(null))->toBeNull();
});

it('resolves id from public_id when available', function () {
    $resource = new class() extends Resource {
        protected string $type = 'items';

        public function attributes($model): array
        {
            return [];
        }
    };

    $model = new stdClass();
    $model->public_id = 'pub-456';

    $result = $resource->toArray($model);

    expect($result['id'])->toBe('pub-456');
});

it('uses the static make method for quick transformation', function () {
    $resourceClass = new class() extends Resource {
        protected string $type = 'items';

        public function attributes($model): array
        {
            return ['name' => $model->name];
        }
    };

    $model = new stdClass();
    $model->id = '1';
    $model->name = 'Test';

    $result = $resourceClass::make($model);

    expect($result['type'])->toBe('items');
    expect($result['attributes']['name'])->toBe('Test');
});

it('includes self link and meta when defined', function () {
    $resource = new class() extends Resource {
        protected string $type = 'items';

        public function attributes($model): array
        {
            return ['name' => $model->name];
        }

        public function self($model): string | null
        {
            return '/items/'.$model->id;
        }

        public function meta($model): array
        {
            return ['version' => 1];
        }
    };

    $model = new stdClass();
    $model->id = '42';
    $model->name = 'Test';

    $result = $resource->toArray($model);

    expect($result['links']['self'])->toBe('/items/42');
    expect($result['meta']['version'])->toBe(1);
});

it('derives type from class name when not set', function () {
    $resource = new class() extends Resource {
        public function attributes($model): array
        {
            return [];
        }
    };

    $model = new stdClass();
    $model->id = '1';

    $result = $resource->toArray($model);

    expect($result['type'])->toBe('std-class');
});

it('merges self with additional links', function () {
    $resource = new class() extends Resource {
        protected string $type = 'products';

        public function attributes($model): array
        {
            return ['name' => $model->name];
        }

        public function self($model): string | null
        {
            return '/api/v1/products/'.$model->id;
        }

        public function links($model): array
        {
            return ['inventory' => '/api/v1/products/'.$model->id.'/inventory'];
        }
    };

    $model = new stdClass();
    $model->id = 'abc-123';
    $model->name = 'Widget';

    $result = $resource->toArray($model);

    expect($result['links']['self'])->toBe('/api/v1/products/abc-123');
    expect($result['links']['inventory'])->toBe('/api/v1/products/abc-123/inventory');
});

it('has no self link when self returns null', function () {
    $resource = new class() extends Resource {
        protected string $type = 'items';

        public function attributes($model): array
        {
            return [];
        }
    };

    $model = new stdClass();
    $model->id = '1';

    $result = $resource->toArray($model);

    expect($result['links'])->toBe([]);
});

it('has only additional links when self is not defined', function () {
    $resource = new class() extends Resource {
        protected string $type = 'items';

        public function attributes($model): array
        {
            return [];
        }

        public function links($model): array
        {
            return ['related' => '/related/'.$model->id];
        }
    };

    $model = new stdClass();
    $model->id = '1';

    $result = $resource->toArray($model);

    expect($result['links'])->not->toHaveKey('self');
    expect($result['links']['related'])->toBe('/related/1');
});

it('uses global ID resolver', function () {
    Resource::resolveIdUsing(fn ($model) => 'custom-'.$model->id);

    $resource = new class() extends Resource {
        protected string $type = 'items';

        public function attributes($model): array
        {
            return [];
        }
    };

    $model = new stdClass();
    $model->id = '42';

    $result = $resource->toArray($model);

    expect($result['id'])->toBe('custom-42');

    Resource::resetResolvers();
});

it('uses global type resolver', function () {
    Resource::resolveTypeUsing(fn ($model) => 'custom-type');

    $resource = new class() extends Resource {
        public function attributes($model): array
        {
            return [];
        }
    };

    $model = new stdClass();
    $model->id = '1';

    $result = $resource->toArray($model);

    expect($result['type'])->toBe('custom-type');

    Resource::resetResolvers();
});

it('gives precedence to explicit type property over global resolver', function () {
    Resource::resolveTypeUsing(fn ($model) => 'global-type');

    $resource = new class() extends Resource {
        protected string $type = 'explicit-type';

        public function attributes($model): array
        {
            return [];
        }
    };

    $model = new stdClass();
    $model->id = '1';

    $result = $resource->toArray($model);

    expect($result['type'])->toBe('explicit-type');

    Resource::resetResolvers();
});

it('restores default behavior after reset resolvers', function () {
    Resource::resolveIdUsing(fn ($model) => 'custom');
    Resource::resetResolvers();

    $resource = new class() extends Resource {
        protected string $type = 'items';

        public function attributes($model): array
        {
            return [];
        }
    };

    $model = new stdClass();
    $model->id = '99';

    $result = $resource->toArray($model);

    expect($result['id'])->toBe('99');
});

it('throws when attributes method is not implemented', function () {
    $resource = new class() extends Resource {
        protected string $type = 'items';
    };

    $model = new stdClass();
    $model->id = '1';

    $resource->toArray($model);
})->throws(BadMethodCallException::class);

it('resolves type from model instance table name', function () {
    $resource = new class() extends Resource {
        public function attributes($model): array
        {
            return [];
        }
    };

    $model = new Product();
    $model->id = 1;
    $model->public_id = 'prod-1';

    $result = $resource->toArray($model);

    expect($result['type'])->toBe('products');
});

it('returns empty type when no model and no type set', function () {
    $resource = new class() extends Resource {
        public function attributes($model): array
        {
            return [];
        }
    };

    expect($resource->resolveType())->toBe('');
});
