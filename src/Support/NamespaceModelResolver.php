<?php

declare(strict_types=1);

namespace Wappo\LaravelSchemaApi\Support;

use Illuminate\Support\Str;
use Wappo\LaravelSchemaApi\Contracts\ModelResolverInterface;

class NamespaceModelResolver implements ModelResolverInterface
{
    public function resolve(string $table): ?string
    {
        $modelClass = Str::finish(config('schema-api.resolvers.namespace.name'), '\\') . Str::studly(Str::singular($table));
        if (!class_exists($modelClass)) {
            return null;
        }

        return $modelClass;
    }

    public function __invoke(string $table): ?string
    {
        return $this->resolve($table);
    }
}