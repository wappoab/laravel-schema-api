<?php

namespace Wappo\LaravelSchemaApi;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Wappo\LaravelSchemaApi\Commands\SchemaApiCommand;

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
            ->hasViews()
            ->hasMigration('create_laravel_schema_api_table')
            ->hasCommand(SchemaApiCommand::class);
    }
}
