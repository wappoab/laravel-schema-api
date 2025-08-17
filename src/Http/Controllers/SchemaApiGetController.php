<?php

declare(strict_types=1);

namespace Wappo\LaravelSchemaApi\Http\Controllers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Wappo\LaravelSchemaApi\Attributes\ApiIgnore;
use Wappo\LaravelSchemaApi\Facades\ModelResolver;
use Wappo\LaravelSchemaApi\Facades\ResourceResolver;
use Wappo\LaravelSchemaApi\ResourceResolvers\UseSchemaApiJsonResourceAttributeResolver;

class SchemaApiGetController
{
    public function __invoke(string $table, mixed $id)
    {
        $modelClass = ModelResolver::get($table);
        if (!$modelClass) {
            throw new ModelNotFoundException(
                sprintf(
                    'Model class for %s was not found',
                    $table,
                ),
            );
        }

        $ref = new \ReflectionClass($modelClass);
        if($ref->getAttributes(ApiIgnore::class)) {
            throw new ModelNotFoundException(
                sprintf(
                    'Table %s is unlisted',
                    $table,
                ),
            );
        }

        assert(
            is_subclass_of($modelClass, Model::class),
            sprintf(
                '%s must implement %s',
                $modelClass,
                Model::class
            )
        );

        $item = $modelClass::findOrFail($id);

        $modelResourceClass = ResourceResolver::get($modelClass);
        if ($modelResourceClass) {
            $item = $modelResourceClass::make($item);
        }

        return response()->json($item);
    }
}
