<?php

namespace Wappo\LaravelSchemaApi\Support;

use Illuminate\Database\Eloquent\Relations\Relation;
use Wappo\LaravelSchemaApi\Contracts\ModelResolverInterface;

class MorphMapModelResolver implements ModelResolverInterface
{
    public function resolve(string $table): ?string
    {
        return Relation::getMorphedModel($table);
    }

    public function __invoke(string $table): ?string
    {
        return $this->resolve($table);
    }
}