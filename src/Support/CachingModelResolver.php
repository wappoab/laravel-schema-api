<?php

namespace Wappo\LaravelSchemaApi\Support;

use Wappo\LaravelSchemaApi\Contracts\ModelResolverInterface;

final class CachingModelResolver implements ModelResolverInterface
{
    /** @var array<string, class-string<\Illuminate\Database\Eloquent\Model>|null> */
    private array $cache = [];

    public function __construct(readonly private ModelResolverInterface $inner)
    {
    }

    public function get(string $type): ?string
    {
        return $this->cache[$type] ??= $this->inner->get($type);
    }
}