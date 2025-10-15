<?php

declare(strict_types=1);

namespace Wappo\LaravelSchemaApi;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Event;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Wappo\LaravelSchemaApi\Broadcasting\GateBasedModelViewAuthorizer;
use Wappo\LaravelSchemaApi\Broadcasting\ModelOperationBroadcaster;
use Wappo\LaravelSchemaApi\Commands\GenerateClientResources;
use Wappo\LaravelSchemaApi\Contracts\ModelOperationBroadcasterInterface;
use Wappo\LaravelSchemaApi\Contracts\ModelResolverInterface;
use Wappo\LaravelSchemaApi\Contracts\ModelViewAuthorizerInterface;
use Wappo\LaravelSchemaApi\Contracts\ResourceResolverInterface;
use Wappo\LaravelSchemaApi\Contracts\ValidationRulesResolverInterface;
use Wappo\LaravelSchemaApi\Listeners\ModelEventBroadcastListener;
use Wappo\LaravelSchemaApi\Support\ModelOperationCollection;
use Wappo\LaravelSchemaApi\Support\ValidationRulesResolver;

class SchemaApiServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-schema-api')
            ->hasConfigFile()
            ->hasRoute('api')
            ->hasCommand(GenerateClientResources::class);
    }

    public function packageRegistered() {
        if (!$this->app->bound(ModelResolverInterface::class)) {
            $this->app->singleton(ModelResolverInterface::class, function (Application $app) {
                $driver = config('schema-api.model_resolver.driver','namespace');
                $config = config("schema-api.model_resolver.drivers.$driver");

                $resolver = $app->make($config['class'], $config);
                $decorators = config('schema-api.model_resolver.decorators', []);
                foreach ($decorators as $decorator) {
                    $resolver = $app->make($decorator, ['inner' => $resolver]);
                }

                return $resolver;
            });
        }

        if (!$this->app->bound(ResourceResolverInterface::class)) {
            $this->app->singleton(ResourceResolverInterface::class, function (Application $app) {

                $driver = config('schema-api.resource_resolver.driver','namespace');
                $config = config("schema-api.resource_resolver.drivers.$driver");

                $resolver = $app->make($config['class'], $config);
                $decorators = config('schema-api.resource_resolver.decorators', []);
                foreach ($decorators as $decorator) {
                    $resolver = $app->make($decorator, ['inner' => $resolver]);
                }

                return $resolver;
            });
        }

        if (!$this->app->bound(ValidationRulesResolverInterface::class)) {
            $this->app->singleton(ValidationRulesResolverInterface::class, function (Application $app) {
                return $app->make(ValidationRulesResolver::class);
            });
        }

        if (!$this->app->bound(ModelViewAuthorizerInterface::class)) {
            $this->app->singleton(ModelViewAuthorizerInterface::class, function (Application $app) {
                return $app->make(GateBasedModelViewAuthorizer::class);
            });
        }

        $this->app->scoped( ModelOperationCollection::class, fn () => new ModelOperationCollection());

        if (!$this->app->bound(ModelOperationBroadcasterInterface::class)) {
            $this->app->singleton(ModelOperationBroadcasterInterface::class, function (Application $app) {
                return $app->make(ModelOperationBroadcaster::class);
            });
        }
    }

    public function packageBooted(): void
    {
        if (config('schema-api.broadcasting.enabled', false)) {
            $mode = config('schema-api.broadcasting.mode', 'sync');

            if ($mode === 'model-events') {
                Event::subscribe(ModelEventBroadcastListener::class);
            }
        }
    }
}
