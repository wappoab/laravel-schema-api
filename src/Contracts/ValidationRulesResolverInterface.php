<?php

declare(strict_types=1);

namespace Wappo\LaravelSchemaApi\Contracts;

use Wappo\LaravelSchemaApi\Enums\Operation;

interface ValidationRulesResolverInterface
{
    public function get(string $classFQN, Operation $operation): ?array;
}