<?php

declare(strict_types=1);

namespace Wappo\LaravelSchemaApi\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class ApplyQueryModifier
{
    /**
     * @param class-string<\Wappo\LaravelSchemaApi\Contracts\QueryModifierInterface> $modifierClass
     * @param array $parameters
     */
    public function __construct(
        public string $modifierClass,
        public array $parameters = [],
    ) {}
}