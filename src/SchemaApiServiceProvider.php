<?php

declare(strict_types=1);

namespace Wappo\LaravelSchemaApi;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Wappo\LaravelSchemaApi\Commands\SchemaApiCommand;
use Wappo\LaravelSchemaApi\Contracts\ModelResolverInterface;
use Wappo\LaravelSchemaApi\Contracts\ResourceResolverInterface;
use Wappo\LaravelSchemaApi\Contracts\ValidationRulesResolverInterface;
use Wappo\LaravelSchemaApi\Facades\ResourceResolver;
use Wappo\LaravelSchemaApi\Support\ModelResourceResolver;
use Wappo\LaravelSchemaApi\Support\ValidationRulesResolver;

class SchemaApiServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-schema-api')
            ->hasConfigFile()
            ->hasRoute('api')
            ->hasCommand(SchemaApiCommand::class);
    }

    public function packageRegistered() {
        if (!$this->app->bound(ModelResolverInterface::class)) {
            $this->app->singleton(ModelResolverInterface::class, function ($app) {
                $resolverClass = config('schema-api.resolvers.' . config('schema-api.model_resolver') . '.class');
                return $app->make($resolverClass);
            });
        }
        if (!$this->app->bound(ResourceResolverInterface::class)) {
            $this->app->singleton(ResourceResolverInterface::class, function ($app) {
                return $app->make(ModelResourceResolver::class);
            });
        }
        if (!$this->app->bound(ValidationRulesResolverInterface::class)) {
            $this->app->singleton(ValidationRulesResolverInterface::class, function ($app) {
                return $app->make(ValidationRulesResolver::class);
            });
        }
    }
}
