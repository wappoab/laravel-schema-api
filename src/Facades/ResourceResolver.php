<?php

declare(strict_types=1);

namespace Wappo\LaravelSchemaApi\Facades;

use Illuminate\Support\Facades\Facade;
use Wappo\LaravelSchemaApi\Contracts\ResourceResolverInterface;

/**
 * @method static string|null get(string $modelClass)
 *
 * @see \Wappo\LaravelSchemaApi\Contracts\ModelResolverInterface
 */
class ResourceResolver extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return ResourceResolverInterface::class;
    }
}
