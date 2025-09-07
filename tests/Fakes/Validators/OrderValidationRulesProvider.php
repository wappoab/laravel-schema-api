<?php

namespace Wappo\LaravelSchemaApi\Tests\Fakes\Validators;

use Wappo\LaravelSchemaApi\Contracts\ValidationRulesProviderInterface;
use Wappo\LaravelSchemaApi\Enums\Operation;

class OrderValidationRulesProvider implements ValidationRulesProviderInterface
{
    public function rulesFor(Operation $operation): array
    {
        return match ($operation) {
            Operation::create => [
                'number' => 'required|int',
                'text' => 'required|string',
            ],
            Operation::update => [
                'text' => 'sometimes|string',
            ],
            Operation::delete => [
            ],
        };
    }
}