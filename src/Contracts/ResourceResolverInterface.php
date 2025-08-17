<?php

declare(strict_types=1);

namespace Wappo\LaravelSchemaApi\Contracts;

interface ResourceResolverInterface
{
    /**
     * Get a resource class for the given Eloquent model class.
     *
     * @param  class-string<\Illuminate\Database\Eloquent\Model> $modelClass
     * @return class-string<SchemaApiJsonResource>|null
     */
    public function get(string $modelClass): ?string;
}