<?php

declare(strict_types=1);

namespace Wappo\LaravelSchemaApi\ModelResolvers;

use Illuminate\Support\Str;
use Wappo\LaravelSchemaApi\Contracts\ModelResolverInterface;

class NamespaceModelResolver implements ModelResolverInterface
{
    public function get(string $table): ?string
    {
        $modelClass = Str::finish(config('schema-api.model_resolvers.namespace.name'), '\\') . Str::studly(Str::singular($table));
        if (!class_exists($modelClass)) {
            return null;
        }

        return $modelClass;
    }
}