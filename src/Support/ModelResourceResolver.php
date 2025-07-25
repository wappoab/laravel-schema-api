<?php

declare(strict_types=1);

namespace Wappo\LaravelSchemaApi\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\JsonResource;
use Wappo\LaravelSchemaApi\Attributes\UseModelResource;
use ReflectionClass;
use Wappo\LaravelSchemaApi\Contracts\ResourceResolverInterface;

class ModelResourceResolver implements ResourceResolverInterface
{
    public function get(string $modelClass): ?string
    {
        assert(
            is_subclass_of($modelClass, Model::class),
            sprintf(
                '%s must be a subclass of %s',
                $modelClass,
                Model::class
            )
        );
        $resourceSuggestions = $modelClass::guessResourceName();

        foreach ($resourceSuggestions as $resourceClass) {
            if (is_string($resourceClass) && class_exists($resourceClass)) {
                return $resourceClass;
            }
        }

        $ref = new ReflectionClass($modelClass);
        $attrs = $ref->getAttributes(UseModelResource::class);
        foreach ($attrs as $attr) {
            $cfg = $attr->newInstance();
            assert(
                $cfg instanceof UseModelResource,
                sprintf(
                    'Expected attribute instance of %s, got %s',
                    UseModelResource::class,
                    is_object($cfg) ? get_class($cfg) : gettype($cfg)
                )
            );

            if(!is_subclass_of($cfg->modelResourceClass, JsonResource::class)) {
                continue;
            }

            return $cfg->modelResourceClass;
        }

        return null;
    }
}