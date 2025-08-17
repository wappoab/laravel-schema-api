<?php

declare(strict_types=1);

namespace Wappo\LaravelSchemaApi\ResourceResolvers;

use Illuminate\Support\Str;
use Wappo\LaravelSchemaApi\Contracts\ResourceResolverInterface;

final readonly class NamespaceResourceResolver implements ResourceResolverInterface
{
    public function __construct(private string $namespace)
    {
    }

    public function get(string $modelClass): ?string
    {
        $resourceClass = Str::finish($this->namespace, '\\') . class_basename($modelClass) . 'Resource';
        if (!class_exists($resourceClass)) {
            return null;
        }

        return $resourceClass;
    }
}