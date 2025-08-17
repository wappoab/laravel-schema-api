<?php

declare(strict_types=1);

namespace Wappo\LaravelSchemaApi\ResourceResolvers;

use ReflectionClass;
use Wappo\LaravelSchemaApi\Attributes\UseSchemaApiJsonResource;
use Wappo\LaravelSchemaApi\Contracts\ResourceResolverInterface;
use Wappo\LaravelSchemaApi\Contracts\SchemaApiJsonResource;

final readonly class UseSchemaApiJsonResourceAttributeResolver implements ResourceResolverInterface
{
    /**
     * @throws \ReflectionException
     */
    public function get(string $modelClass): ?string
    {
        $ref = new ReflectionClass($modelClass);
        $attrs = $ref->getAttributes(UseSchemaApiJsonResource::class);

        foreach ($attrs as $attr) {
            $cfg = $attr->newInstance();
            $candidate = $cfg->resourceClass;
            if (
                is_string($candidate)
                && class_exists($candidate)
            ) {
                /** @var class-string<SchemaApiJsonResource> $candidate */
                return $candidate;
            }
        }

        return null;
    }
}