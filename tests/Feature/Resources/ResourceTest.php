<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Tests\Feature\Resources;

use BadMethodCallException;
use BlueBeetle\ApiToolkit\Resources\Resource;
use BlueBeetle\ApiToolkit\Tests\Fixtures\Models\Product;
use RuntimeException;
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

it('resolves id from id property on plain objects', function () {
    $resource = new class() extends Resource {
        protected string $type = 'items';

        public function attributes($model): array
        {
            return [];
        }
    };

    $model = new stdClass();
    $model->id = 'obj-456';

    $result = $resource->toArray($model);

    expect($result['id'])->toBe('obj-456');
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

it('resolves id from eloquent model primary key', function () {
    $resource = new class() extends Resource {
        public function attributes($model): array
        {
            return [];
        }
    };

    $model = new Product();
    $model->id = 42;

    $result = $resource->toArray($model);

    expect($result['id'])->toBe('42');
});

it('uses resource-level resolveId override', function () {
    $resource = new class() extends Resource {
        protected string $type = 'items';

        public function resolveId($model): string
        {
            return 'custom-'.$model->slug;
        }

        public function attributes($model): array
        {
            return [];
        }
    };

    $model = new stdClass();
    $model->slug = 'widget';

    $result = $resource->toArray($model);

    expect($result['id'])->toBe('custom-widget');
});

it('gives precedence to resource-level override over global resolver', function () {
    Resource::resolveIdUsing(fn ($model) => 'global-'.$model->id);

    $resource = new class() extends Resource {
        protected string $type = 'items';

        public function resolveId($model): string
        {
            return 'resource-'.$model->id;
        }

        public function attributes($model): array
        {
            return [];
        }
    };

    $model = new stdClass();
    $model->id = '1';

    $result = $resource->toArray($model);

    expect($result['id'])->toBe('resource-1');

    Resource::resetResolvers();
});

it('throws when id cannot be resolved', function () {
    $resource = new class() extends Resource {
        protected string $type = 'items';

        public function attributes($model): array
        {
            return [];
        }
    };

    $model = new stdClass();

    $resource->toArray($model);
})->throws(RuntimeException::class, 'Unable to resolve ID');

it('returns empty type when no model and no type set', function () {
    $resource = new class() extends Resource {
        public function attributes($model): array
        {
            return [];
        }
    };

    expect($resource->resolveType())->toBe('');
});

it('filters attributes with sparse fieldsets from request', function () {
    $resource = new class() extends Resource {
        protected string $type = 'products';

        public function attributes($model): array
        {
            return [
                'name' => $model->name,
                'price' => $model->price,
                'sku' => $model->sku,
            ];
        }
    };

    $request = \Illuminate\Http\Request::create('/', 'GET', [
        'fields' => ['products' => 'name,price'],
    ]);

    $model = new stdClass();
    $model->id = '1';
    $model->name = 'Widget';
    $model->price = 29.99;
    $model->sku = 'WGT-001';

    $result = $resource->withRequest($request)->toArray($model);

    expect($result['attributes'])->toBe(['name' => 'Widget', 'price' => 29.99]);
    expect($result['attributes'])->not->toHaveKey('sku');
});

it('returns all attributes when no sparse fieldset is requested', function () {
    $resource = new class() extends Resource {
        protected string $type = 'products';

        public function attributes($model): array
        {
            return [
                'name' => $model->name,
                'price' => $model->price,
            ];
        }
    };

    $request = \Illuminate\Http\Request::create('/');

    $model = new stdClass();
    $model->id = '1';
    $model->name = 'Widget';
    $model->price = 29.99;

    $result = $resource->withRequest($request)->toArray($model);

    expect($result['attributes'])->toBe(['name' => 'Widget', 'price' => 29.99]);
});

it('caches sparse fieldsets across multiple toArray calls', function () {
    $resource = new class() extends Resource {
        protected string $type = 'products';

        public function attributes($model): array
        {
            return [
                'name' => $model->name,
                'price' => $model->price,
                'sku' => $model->sku,
            ];
        }
    };

    $request = \Illuminate\Http\Request::create('/', 'GET', [
        'fields' => ['products' => 'name'],
    ]);

    $resource->withRequest($request);

    $model1 = new stdClass();
    $model1->id = '1';
    $model1->name = 'Widget';
    $model1->price = 10;
    $model1->sku = 'W01';

    $model2 = new stdClass();
    $model2->id = '2';
    $model2->name = 'Gadget';
    $model2->price = 20;
    $model2->sku = 'G01';

    $result1 = $resource->toArray($model1);
    $result2 = $resource->toArray($model2);

    expect($result1['attributes'])->toBe(['name' => 'Widget']);
    expect($result2['attributes'])->toBe(['name' => 'Gadget']);
});

it('ignores sparse fieldsets for non-matching type', function () {
    $resource = new class() extends Resource {
        protected string $type = 'products';

        public function attributes($model): array
        {
            return [
                'name' => $model->name,
                'price' => $model->price,
            ];
        }
    };

    $request = \Illuminate\Http\Request::create('/', 'GET', [
        'fields' => ['categories' => 'name'],
    ]);

    $model = new stdClass();
    $model->id = '1';
    $model->name = 'Widget';
    $model->price = 29.99;

    $result = $resource->withRequest($request)->toArray($model);

    expect($result['attributes'])->toBe(['name' => 'Widget', 'price' => 29.99]);
});
