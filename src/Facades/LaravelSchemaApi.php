<?php

namespace Wappo\LaravelSchemaApi\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Wappo\LaravelSchemaApi\LaravelSchemaApi
 */
class LaravelSchemaApi extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Wappo\LaravelSchemaApi\LaravelSchemaApi::class;
    }
}
