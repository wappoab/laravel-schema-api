<?php

declare(strict_types=1);

namespace Wappo\LaravelSchemaApi\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class UseModelResource
{
    /**
     * @param class-string<\Illuminate\Http\Resources\Json\JsonResource> $modelResourceClass
     */
    public function __construct(
        public string $modelResourceClass,
    ) {}
}