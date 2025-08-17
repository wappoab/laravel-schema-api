<?php

declare(strict_types=1);

namespace Wappo\LaravelSchemaApi\ModelResolvers;

use Illuminate\Support\Str;
use Wappo\LaravelSchemaApi\Contracts\ModelResolverInterface;

final readonly class NamespaceModelResolver implements ModelResolverInterface
{
    public function __construct(private string $namespace)
    {
    }

    public function get(string $table): ?string
    {
        $modelClass = Str::finish($this->namespace, '\\') . Str::studly(Str::singular($table));
        if (!class_exists($modelClass)) {
            return null;
        }

        return $modelClass;
    }
}