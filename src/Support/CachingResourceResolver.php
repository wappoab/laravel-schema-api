<?php

namespace Wappo\LaravelSchemaApi\Support;

use Wappo\LaravelSchemaApi\Contracts\ResourceResolverInterface;

final class CachingResourceResolver implements ResourceResolverInterface
{
    /** @var array<class-string<\Illuminate\Database\Eloquent\Model>, string|null> */
    private array $cache = [];

    public function __construct(readonly private ResourceResolverInterface $inner)
    {
    }

    public function get(string $modelClass): ?string
    {
        return $this->cache[$modelClass] ??= $this->inner->get($modelClass);
    }
}