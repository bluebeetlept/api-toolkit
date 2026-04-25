<?php

declare(strict_types = 1);

namespace Eufaturo\ApiToolkit\Tests\Feature\Resources;

use Eufaturo\ApiToolkit\Resources\Resource;
use Eufaturo\ApiToolkit\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use stdClass;

final class ResourceTest extends TestCase
{
    #[Test]
    #[TestDox('it serializes a model to JSON:API resource object')]
    public function it_serializes_to_resource_object(): void
    {
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

        $this->assertSame('products', $result['type']);
        $this->assertSame('abc-123', $result['id']);
        $this->assertSame('Widget', $result['attributes']['name']);
        $this->assertSame('W01', $result['attributes']['code']);
        $this->assertSame([], $result['relationships']);
        $this->assertSame([], $result['links']);
        $this->assertSame([], $result['meta']);
    }

    #[Test]
    #[TestDox('it returns null when model is null')]
    public function it_returns_null_for_null_model(): void
    {
        $resource = new class() extends Resource {
            public function attributes($model): array
            {
                return [];
            }
        };

        $this->assertNull($resource->toArray(null));
    }

    #[Test]
    #[TestDox('it resolves id from public_id when available')]
    public function it_resolves_id_from_public_id(): void
    {
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

        $this->assertSame('pub-456', $result['id']);
    }

    #[Test]
    #[TestDox('it uses the static make method for quick transformation')]
    public function it_uses_static_make(): void
    {
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

        $this->assertSame('items', $result['type']);
        $this->assertSame('Test', $result['attributes']['name']);
    }

    #[Test]
    #[TestDox('it includes self link and meta when defined')]
    public function it_includes_self_and_meta(): void
    {
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

        $this->assertSame('/items/42', $result['links']['self']);
        $this->assertSame(1, $result['meta']['version']);
    }

    #[Test]
    #[TestDox('it derives type from class name when not set')]
    public function it_derives_type_from_class_name(): void
    {
        $resource = new class() extends Resource {
            public function attributes($model): array
            {
                return [];
            }
        };

        $model = new stdClass();
        $model->id = '1';

        $result = $resource->toArray($model);

        $this->assertSame('std-class', $result['type']);
    }

    #[Test]
    #[TestDox('it merges self with additional links')]
    public function it_merges_self_with_additional_links(): void
    {
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

        $this->assertSame('/api/v1/products/abc-123', $result['links']['self']);
        $this->assertSame('/api/v1/products/abc-123/inventory', $result['links']['inventory']);
    }

    #[Test]
    #[TestDox('it has no self link when self returns null')]
    public function it_has_no_self_link_when_null(): void
    {
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

        $this->assertSame([], $result['links']);
    }

    #[Test]
    #[TestDox('it has only additional links when self is not defined')]
    public function it_has_only_additional_links(): void
    {
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

        $this->assertArrayNotHasKey('self', $result['links']);
        $this->assertSame('/related/1', $result['links']['related']);
    }
}
