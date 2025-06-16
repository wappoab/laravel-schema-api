<?php

namespace Wappo\LaravelSchemaApi\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Wappo\LaravelSchemaApi\SchemaApi
 */
class SchemaApi extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Wappo\LaravelSchemaApi\SchemaApi::class;
    }
}
