<?php

namespace Wappo\LaravelSchemaApi\Support;

use Wappo\LaravelSchemaApi\Contracts\ModelResolverInterface;

class ChainedModelResolver implements ModelResolverInterface
{
    protected array $resolvers;

    public function __construct(array $resolvers)
    {
        $this->resolvers = $resolvers;
    }
    public function resolve(string $table): ?string
    {
        foreach ($this->resolvers as $resolver) {
            $class = $resolver->resolve($table);
            if ($class) {
                return $class;
            }
        }
        return null;
    }

    public function __invoke(string $table): ?string
    {
        return $this->resolve($table);
    }
}