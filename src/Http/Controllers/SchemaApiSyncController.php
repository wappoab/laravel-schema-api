<?php

declare(strict_types=1);

namespace Wappo\LaravelSchemaApi\Http\Controllers;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Wappo\LaravelSchemaApi\Enums\Operation;
use Wappo\LaravelSchemaApi\Facades\ModelResolver;
use Wappo\LaravelSchemaApi\Facades\ResourceResolver;
use Wappo\LaravelSchemaApi\Facades\ValidationRulesResolver;
use Wappo\LaravelSchemaApi\Http\Requests\SchemaApiSyncRequest;
use Wappo\LaravelSchemaApi\Http\Resources\ModelOperationResource;
use Wappo\LaravelSchemaApi\Support\ModelOperation;

class SchemaApiSyncController
{
    public function __invoke(SchemaApiSyncRequest $request)
    {
        $modelOperations = $this->buildModelOperations($request->validated('operations'));
        $this->authorizeModelOperations($modelOperations);

        $validationErrors = $this->validateModelsOperations($modelOperations);
        if ($validationErrors->isNotEmpty()) {
            return response()->json($validationErrors, 422);
        }

        $this->persistModelOperations($modelOperations);
        $output = $this->renderModelOperations($modelOperations);

        return response()->json($output);
    }

    private function buildModelOperations(array $operations): Collection
    {
        $modelClasses = [];
        return collect($operations)
            ->map(function (array $op) {
                $op['operation'] = Operation::from($op['operation']);

                return $op;
            })
            ->groupBy(fn($op) => $op['name'] . ':' . $op['obj']['id'])
            ->filter(
                fn(Collection $ops) => !($ops->first()['operation'] === Operation::create
                    && $ops->last()['operation'] === Operation::delete)
            )->map(function (Collection $ops) use (&$modelClasses) {
                return $ops->reduce(function (ModelOperation $carry, array $op) use (&$modelClasses) {
                    $props = collect($op['obj'])->except('id', '$loki', 'meta')->toArray();
                    $carry->attributes = $props + $carry->attributes;
                    if (!$carry->modelClass) {
                        $carry->modelClass = $modelClasses[$op['name']] ??= ModelResolver::get($op['name']);
                        $carry->collectionName = $op['name'];
                        if (!$modelClasses[$op['name']]) {
                            throw new ModelNotFoundException(
                                sprintf(
                                    'Model class for %s was not found',
                                    $carry->collectionName,
                                ),
                            );
                        }
                    }
                    if (!$carry->id) {
                        $carry->id = $op['obj']['id'];
                    }
                    if ($op['operation'] === Operation::delete) {
                        $carry->attributes = [];
                    }
                    if ($op['operation'] === Operation::delete || $op['operation'] === Operation::create || !$carry->operation) {
                        $carry->operation = $op['operation'];
                    }

                    return $carry;
                }, app(ModelOperation::class));
            })->values()->each(function (ModelOperation $modelOperation) {
                if ($modelOperation->operation === Operation::create) {
                    $modelOperation->modelInstance = app($modelOperation->modelClass);
                    $modelOperation->modelInstance->id = $modelOperation->id;

                    return;
                }
                $modelOperation->modelInstance = $modelOperation->modelClass::findOrFail($modelOperation->id);
            });
    }

    private function authorizeModelOperations(Collection $modelOperations): void
    {
        $modelOperations->each(fn(ModelOperation $modelOperation) => Gate::authorize(
            $modelOperation->operation->name,
            $modelOperation->operation === Operation::create
                ? $modelOperation->modelClass
                : $modelOperation->modelInstance,
        )
        );
    }

    private function validateModelsOperations(Collection $modelOperations): Collection
    {
        return $modelOperations->map(function (ModelOperation $modelOperation) {
            $rules = ValidationRulesResolver::get($modelOperation->modelClass, $modelOperation->operation);
            $validator = Validator::make($modelOperation->attributes, $rules);
            if ($validator->fails()) {
                return [
                    '@id' => $modelOperation->id,
                    'name' => $modelOperation->collectionName,
                    'errors' => $validator->errors()->messages(),
                ];
            }
            return false;
        })->filter();
    }

    private function persistModelOperations(Collection $modelOperations): void
    {
        DB::transaction(fn() => $modelOperations->each(fn(ModelOperation $op) => match($op->operation) {
            Operation::create, Operation::update => $op->modelInstance->fill($op->attributes)->isDirty()
                ? tap($op->modelInstance)->save()->refresh()
                : null,
            Operation::delete => $op->modelInstance->delete(),
        }));
    }

    private function renderModelOperations(Collection $modelOperations): array
    {
        $resourceClasses = [];

        return ModelOperationResource::collection(
            $modelOperations
                ->each(function (ModelOperation $modelOperation) use (&$resourceClasses) {
                    $modelOperation->resourceClass = $resourceClasses[$modelOperation->modelClass]
                        ??= ResourceResolver::get($modelOperation->modelClass);
                })
        )->resolve();
    }
}
