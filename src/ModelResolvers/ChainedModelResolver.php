<?php

declare(strict_types=1);

namespace Wappo\LaravelSchemaApi\ModelResolvers;

use Wappo\LaravelSchemaApi\Contracts\ModelResolverInterface;

class ChainedModelResolver implements ModelResolverInterface
{
    protected array $resolvers;

    public function __construct(array $resolvers)
    {
        $this->resolvers = $resolvers;
    }
    public function get(string $table): ?string
    {
        foreach ($this->resolvers as $resolver) {
            $class = $resolver->get($table);
            if ($class) {
                return $class;
            }
        }
        return null;
    }
}