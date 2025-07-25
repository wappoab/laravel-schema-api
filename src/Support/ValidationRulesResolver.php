<?php

declare(strict_types=1);

namespace Wappo\LaravelSchemaApi\Support;

use ReflectionClass;
use Wappo\LaravelSchemaApi\Attributes\UseValidationRulesProvider;
use Wappo\LaravelSchemaApi\Contracts\ValidationRulesProviderInterface;
use Wappo\LaravelSchemaApi\Contracts\ValidationRulesResolverInterface;
use Wappo\LaravelSchemaApi\Enums\Operation;

class ValidationRulesResolver implements ValidationRulesResolverInterface
{
    public function get(string $classFQN, Operation $operation): array
    {
        $ref = new ReflectionClass($classFQN);
        $attr = ($ref->getAttributes(UseValidationRulesProvider::class)[0]) ?? false;
        if (!$attr) {
            return app(SchemaValidationRulesGenerator::class)->generate($classFQN, $operation);
        }

        $cfg = $attr->newInstance();
        assert(
            $cfg instanceof UseValidationRulesProvider,
            sprintf(
                '%s must be an instance of %s',
                $cfg,
                UseValidationRulesProvider::class,
            )
        );

        $validationRulesProvider = app($cfg->modelValidationRulesClass);
        assert(
            is_subclass_of($validationRulesProvider, ValidationRulesProviderInterface::class),
            sprintf(
                '%s must be a subclass of %s',
                $validationRulesProvider,
                ValidationRulesProviderInterface::class
            )
        );

        return $validationRulesProvider->rulesFor($operation);
    }
}