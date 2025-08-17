<?php

declare(strict_types=1);

namespace Wappo\LaravelSchemaApi\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Testing\TestResponse;
use Orchestra\Testbench\TestCase as Orchestra;
use Wappo\LaravelSchemaApi\SchemaApiServiceProvider;

class TestCase extends Orchestra
{
    use RefreshDatabase;
    protected string $base;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('app.key', 'base64:'.base64_encode(random_bytes(32)));

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Wappo\\LaravelSchemaApi\\Tests\\Factories\\'.class_basename($modelName).'Factory'
        );

        TestResponse::macro('streamedJson', function () {
            /** @var \Illuminate\Testing\TestResponse $this */
            $content = $this->streamedContent();

            return collect(explode(PHP_EOL, trim($content)))
                ->filter() // drop empty lines
                ->map(fn (string $line) => json_decode($line, true))
                ->values();
        });
    }

    protected function getPackageProviders($app)
    {
        return [
            SchemaApiServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app): void
    {
        config()->set('database.default', 'testing');
        config()->set('schema-api.model_resolvers.namespace.name', 'Wappo\\LaravelSchemaApi\\Tests\\Fakes\\Models');

        $this->base = __DIR__ . '/tmp';
        File::ensureDirectoryExists($this->base);
        $app->setBasePath($this->base);
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/Migrations');
    }

    protected function getFixture(string $path): array|string
    {
        if(pathinfo($path, PATHINFO_EXTENSION) === 'json') {
            return json_decode(file_get_contents($path), true);
        }
        else {
            return file_get_contents($path);
        }
    }

    public function setFixture(string $path, string|array $data): false|int
    {
        if(is_array($data)) {
            $data = json_encode($data, JSON_PRETTY_PRINT);
        }
        return file_put_contents($path, $data);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->base);
        parent::tearDown();
    }
}
