<?php

declare(strict_types=1);

namespace Wappo\LaravelSchemaApi\Tests\Fakes\Models\Validators;

use Wappo\LaravelSchemaApi\Contracts\ValidationRulesProviderInterface;
use Wappo\LaravelSchemaApi\Enums\Operation;

class CategoryValidationRulesProvider implements ValidationRulesProviderInterface
{
    public function rulesFor(Operation $operation): array
    {
        return match ($operation) {
            Operation::create => [
                'name' => 'required|string|max:255|min:1',
            ],
            Operation::update => [
                'name' => 'sometimes|string|max:255|min:1',
            ],
            Operation::delete => [],
        };
    }
}