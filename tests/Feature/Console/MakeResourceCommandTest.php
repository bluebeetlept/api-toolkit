<?php

declare(strict_types = 1);

namespace BlueBeetle\ApiToolkit\Tests\Feature\Console;

use BlueBeetle\ApiToolkit\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;

final class MakeResourceCommandTest extends TestCase
{
    private string $resourcePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->resourcePath = app_path('Http/Resources');
    }

    protected function tearDown(): void
    {
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

        parent::tearDown();
    }

    #[Test]
    #[TestDox('it creates a resource with a model')]
    public function it_creates_resource_with_model(): void
    {
        $this->artisan('api-toolkit:make-resource', ['name' => 'Product'])
            ->assertSuccessful()
        ;

        $this->assertFileExists($this->resourcePath.'/ProductResource.php');

        $content = file_get_contents($this->resourcePath.'/ProductResource.php');
        $this->assertStringContainsString('class ProductResource extends Resource', $content);
        $this->assertStringContainsString('App\Models\Product', $content);
        $this->assertStringContainsString('Product::class', $content);
    }

    #[Test]
    #[TestDox('it creates a resource with explicit model option')]
    public function it_creates_with_explicit_model(): void
    {
        $this->artisan('api-toolkit:make-resource', [
            'name' => 'Item',
            '--model' => 'App\Domain\Models\Item',
        ])->assertSuccessful();

        $content = file_get_contents($this->resourcePath.'/ItemResource.php');
        $this->assertStringContainsString('App\Domain\Models\Item', $content);
    }

    #[Test]
    #[TestDox('it creates a plain resource without a model')]
    public function it_creates_plain_resource(): void
    {
        $this->artisan('api-toolkit:make-resource', [
            'name' => 'Timestamp',
            '--plain' => true,
        ])->assertSuccessful();

        $content = file_get_contents($this->resourcePath.'/TimestampResource.php');
        $this->assertStringContainsString('class TimestampResource extends Resource', $content);
        $this->assertStringContainsString("type = 'timestamp'", $content);
        $this->assertStringNotContainsString('$model =', $content);
    }

    #[Test]
    #[TestDox('it appends Resource suffix when not provided')]
    public function it_appends_resource_suffix(): void
    {
        $this->artisan('api-toolkit:make-resource', ['name' => 'Category'])
            ->assertSuccessful()
        ;

        $this->assertFileExists($this->resourcePath.'/CategoryResource.php');
    }

    #[Test]
    #[TestDox('it does not duplicate Resource suffix')]
    public function it_does_not_duplicate_suffix(): void
    {
        $this->artisan('api-toolkit:make-resource', ['name' => 'CategoryResource'])
            ->assertSuccessful()
        ;

        $this->assertFileExists($this->resourcePath.'/CategoryResource.php');
        $this->assertFileDoesNotExist($this->resourcePath.'/CategoryResourceResource.php');
    }

    #[Test]
    #[TestDox('it fails when resource already exists')]
    public function it_fails_when_resource_exists(): void
    {
        $this->artisan('api-toolkit:make-resource', ['name' => 'Product'])
            ->assertSuccessful()
        ;

        $this->artisan('api-toolkit:make-resource', ['name' => 'Product'])
            ->assertFailed()
        ;
    }

    #[Test]
    #[TestDox('it derives type name for plain resources')]
    public function it_derives_type_name(): void
    {
        $this->artisan('api-toolkit:make-resource', [
            'name' => 'UserProfile',
            '--plain' => true,
        ])->assertSuccessful();

        $content = file_get_contents($this->resourcePath.'/UserProfileResource.php');
        $this->assertStringContainsString("type = 'user-profile'", $content);
    }

    #[Test]
    #[TestDox('it uses camelCase variable name for model')]
    public function it_uses_camel_case_variable(): void
    {
        $this->artisan('api-toolkit:make-resource', ['name' => 'ProductCategory'])
            ->assertSuccessful()
        ;

        $content = file_get_contents($this->resourcePath.'/ProductCategoryResource.php');
        $this->assertStringContainsString('$productCategory', $content);
    }
}
