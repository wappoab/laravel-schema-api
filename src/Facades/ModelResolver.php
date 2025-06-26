<?php

declare(strict_types=1);

namespace Wappo\LaravelSchemaApi\Facades;

use Illuminate\Support\Facades\Facade;
use Wappo\LaravelSchemaApi\Contracts\ModelResolverInterface;

/**
 * @see \Wappo\LaravelSchemaApi\SchemaApi
 */
class ModelResolver extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return ModelResolverInterface::class;
    }
}
