<?php

declare(strict_types=1);

namespace Wappo\LaravelSchemaApi\Facades;

use Illuminate\Support\Facades\Facade;
use Wappo\LaravelSchemaApi\Contracts\ValidationRulesResolverInterface;
use Wappo\LaravelSchemaApi\Enums\Operation;

/**
 * @method static array get(string $classFQN, Operation $operation)
 *
 * @see \Wappo\LaravelSchemaApi\Contracts\ValidationRulesResolverInterface
 */
class ValidationRulesResolver extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return ValidationRulesResolverInterface::class;
    }
}
