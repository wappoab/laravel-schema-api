<?php

declare(strict_types=1);

namespace Wappo\LaravelSchemaApi\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class UseValidationRulesProvider
{
    /**
     * @param class-string<\Wappo\LaravelSchemaApi\Contracts\ValidationRulesProviderInterface> $modelValidationRulesClass
     */
    public function __construct(
        public string $modelValidationRulesClass,
    ) {}
}