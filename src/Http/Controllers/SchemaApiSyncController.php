<?php

declare(strict_types=1);

namespace Wappo\LaravelSchemaApi\Http\Controllers;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Wappo\LaravelSchemaApi\Enums\Operation;
use Wappo\LaravelSchemaApi\Facades\ModelResolver;
use Wappo\LaravelSchemaApi\Facades\ResourceResolver;
use Wappo\LaravelSchemaApi\Facades\ValidationRulesResolver;
use Wappo\LaravelSchemaApi\Http\Requests\SchemaApiSyncRequest;
use Wappo\LaravelSchemaApi\Support\ModelOperation;

class SchemaApiSyncController
{
    public function __invoke(SchemaApiSyncRequest $request)
    {
        try {
            $modelOperations = $this->buildModelOperations($request->validated());
        }
        catch (\ValueError $e) {
            throw new BadRequestHttpException($e->getMessage(), $e);
        }
        $this->authorizeModelOperations($modelOperations);
        $validationErrors = $this->validateModelsOperations($modelOperations);
        if ($validationErrors->isNotEmpty()) {
            return response()->json($validationErrors, 422);
        }
        $this->persistModelOperations($modelOperations);

        $flags = (int) config('schema-api.http.json_encode_flags', JSON_UNESCAPED_UNICODE);
        $gzipLevel = (int) ($request->validated('gzip') ?? config('schema-api.http.gzip_level', 0));
        $gzipHeader = $gzipLevel > 0 ? ['Content-Encoding' => 'gzip'] : [];
        return response()->stream(function () use ($modelOperations, $flags, $gzipLevel) {
            $stream = fopen('php://output', 'wb');
            if ($gzipLevel > 0) {
                stream_filter_append(
                    $stream,
                    'zlib.deflate',
                    STREAM_FILTER_WRITE,
                    ['window' => 31, 'level' => $gzipLevel],
                );
            }

            $modelOperations->each(function (ModelOperation $modelOperation) use ($stream, $flags) {
                if ($resource = ResourceResolver::get($modelOperation->modelClass)) {
                    $attr = $resource::make($modelOperation->modelInstance);
                } else {
                    $attr = $modelOperation->modelInstance;
                }

                $item = [
                    'id' => $modelOperation->id,
                    'type' => $modelOperation->collectionName,
                    'op' => $modelOperation->operation->value,
                    'attr' => $attr,
                ];
                fwrite($stream, json_encode($item, $flags) . PHP_EOL);
            });
            fclose($stream);
        }, 200, [
            ...$gzipHeader,
            'Content-Type' => 'application/stream+json',
        ]);
    }

    private function buildModelOperations(array $operations): Collection
    {
        $modelClasses = [];
        return collect($operations)
            ->map(function (array $op) {
                $op['op'] = Operation::from($op['op']); //throws ValueError that is handled

                return $op;
            })
            ->groupBy(fn($op) => $op['type'] . ':' . $op['id'])
            ->filter(
                fn(Collection $ops) => !($ops->first()['op'] === Operation::create
                    && $ops->last()['op'] === Operation::delete)
            )->map(function (Collection $ops) use (&$modelClasses) {
                return $ops->reduce(function (ModelOperation $carry, array $op) use (&$modelClasses) {
                    $props = collect(($op['attr']??[]))->except('id')->toArray();
                    $carry->attributes = $props + $carry->attributes;
                    if (!$carry->modelClass) {
                        $carry->modelClass = $modelClasses[$op['type']] ??= ModelResolver::get($op['type']);
                        $carry->collectionName = $op['type'];
                        if (!$modelClasses[$op['type']]) {
                            throw new BadRequestHttpException(
                                sprintf(
                                    'Model class for %s dont exist',
                                    $carry->collectionName,
                                ),
                            );
                        }
                    }
                    if (!$carry->id) {
                        $carry->id = $op['id'];
                    }
                    if ($op['op'] === Operation::delete) {
                        $carry->attributes = [];
                    }
                    if ($op['op'] === Operation::delete || $op['op'] === Operation::create || !$carry->operation) {
                        $carry->operation = $op['op'];
                    }

                    return $carry;
                }, app(ModelOperation::class));
            })->values()->each(function (ModelOperation $modelOperation) {
                if ($modelOperation->operation === Operation::create) {
                    $modelOperation->modelInstance = app($modelOperation->modelClass);
                    $modelOperation->modelInstance->setAttribute(
                        $modelOperation->modelInstance->getKeyName(),
                        $modelOperation->id
                    );

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
                    'id' => $modelOperation->id,
                    'type' => $modelOperation->collectionName,
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
}
