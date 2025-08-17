<?php

declare(strict_types=1);

namespace Wappo\LaravelSchemaApi\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class UseSchemaApiJsonResource
{
    /**
     * @param class-string<\Wappo\LaravelSchemaApi\Contracts\SchemaApiJsonResource> $resourceClass
     */
    public function __construct(
        public string $resourceClass,
    ) {}
}