<?php

declare(strict_types=1);

namespace Wappo\LaravelSchemaApi\Http\Controllers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Wappo\LaravelSchemaApi\Facades\ModelResolver;
use Wappo\LaravelSchemaApi\Support\ModelResourceResolver;

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

        assert(
            is_subclass_of($modelClass, Model::class),
            sprintf(
                '%s must implement %s',
                $modelClass,
                Model::class
            )
        );

        $item = $modelClass::findOrFail($id);

        $modelResourceClass = app(ModelResourceResolver::class)->get($modelClass);
        if($modelResourceClass)  {
            $printer = fn ($model) => $modelResourceClass::make($model)->resolve();
        }
        else {
            $printer = fn ($model) => $model;
        }

        return response()->json($printer($item));
    }
}
