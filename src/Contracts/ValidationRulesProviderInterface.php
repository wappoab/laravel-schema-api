<?php

declare(strict_types=1);

namespace Wappo\LaravelSchemaApi\Contracts;

use Wappo\LaravelSchemaApi\Enums\Operation;

interface ValidationRulesProviderInterface
{
    /**
     * @param  Operation  $operation
     * @return array<string,string|array>
     */
    public function rulesFor(Operation $operation): array;
}