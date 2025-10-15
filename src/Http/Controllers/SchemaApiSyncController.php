<?php

declare(strict_types=1);

namespace Wappo\LaravelSchemaApi\Http\Controllers;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Wappo\LaravelSchemaApi\Broadcasting\ModelOperationBroadcaster;
use Wappo\LaravelSchemaApi\Enums\Operation;
use Wappo\LaravelSchemaApi\Facades\ModelResolver;
use Wappo\LaravelSchemaApi\Facades\ResourceResolver;
use Wappo\LaravelSchemaApi\Facades\ValidationRulesResolver;
use Wappo\LaravelSchemaApi\Http\Requests\SchemaApiSyncRequest;
use Wappo\LaravelSchemaApi\Support\ModelOperation;
use Wappo\LaravelSchemaApi\Support\ModelOperationCollection;
use Wappo\LaravelSchemaApi\Support\TypeToTableMapper;

final readonly class SchemaApiSyncController
{
    public function __construct(
        private TypeToTableMapper $typeToTableMapper,
        private ModelOperationCollection $modelOperations,
        private ModelOperationBroadcaster $broadcaster,
    )
    {
    }

    public function __invoke(SchemaApiSyncRequest $request)
    {
        $this->modelOperations->clear();

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
        $modelOperations = $this->mergeCollectedOperations($modelOperations);
        $this->broadcaster->broadcast($modelOperations);

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
                $attr = [];
                if($modelOperation->op !== Operation::delete) {
                    $attr = $modelOperation->modelClass::whereKey($modelOperation->id)->toBase()->first();
                    if ($resource = ResourceResolver::get($modelOperation->modelClass)) {
                        $attr = $resource::make($attr);
                    }
                }

                $item = [
                    'id' => $modelOperation->id,
                    'type' => $modelOperation->type,
                    'op' => $modelOperation->op->value,
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
        return collect($operations)
            ->map(function (array $op) {
                $op['op'] = Operation::from($op['op']); //throws ValueError that is handled

                return $op;
            })
            ->groupBy(fn($op) => $op['type'] . ':' . $op['id'])
            ->filter(
                fn(Collection $ops) => !($ops->first()['op'] === Operation::create
                    && $ops->last()['op'] === Operation::delete)
            )->map(function (Collection $ops) {
                return $ops->reduce(function (ModelOperation $carry, array $op) {
                    $props = collect(($op['attr']??[]))->except('id')->toArray();
                    $carry->attr = $props + $carry->attr;
                    if (!$carry->modelClass) {
                        $carry->modelClass = ModelResolver::get($op['type']);
                        if (!$carry->modelClass) {
                            throw new BadRequestHttpException(
                                sprintf(
                                    'Model class for %s dont exist',
                                    $carry->tableName,
                                ),
                            );
                        }
                        $carry->tableName = ($this->typeToTableMapper)($op['type']);
                        $carry->type = $op['type'];
                        $carry->id = $op['id'];
                    }
                    if ($op['op'] === Operation::delete) {
                        $carry->attr = [];
                    }
                    if ($op['op'] === Operation::delete || $op['op'] === Operation::create || !$carry->op) {
                        $carry->op = $op['op'];
                    }

                    return $carry;
                }, new ModelOperation);
            })->values()->each(function (ModelOperation $modelOperation) {
                if ($modelOperation->op === Operation::create) {
                    $modelOperation->modelInstance = new $modelOperation->modelClass;
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
            $modelOperation->op->name,
            $modelOperation->op === Operation::create
                ? $modelOperation->modelClass
                : $modelOperation->modelInstance,
        )
        );
    }

    private function validateModelsOperations(Collection $modelOperations): Collection
    {
        return $modelOperations->map(function (ModelOperation $modelOperation) {
            $rules = ValidationRulesResolver::get($modelOperation->modelClass, $modelOperation->op);
            $validator = Validator::make($modelOperation->attr, $rules);
            if ($validator->fails()) {
                return [
                    'id' => $modelOperation->id,
                    'type' => $modelOperation->type,
                    'errors' => $validator->errors()->messages(),
                ];
            }
            return false;
        })->filter();
    }

    private function persistModelOperations(Collection $modelOperations): void
    {
        DB::transaction(fn() => $modelOperations->each(fn(ModelOperation $op) => match($op->op) {
            Operation::create, Operation::update => $op->modelInstance->fill($op->attr)->isDirty()
                ? $op->modelInstance->save()
                : null,
            Operation::delete => $op->modelInstance->delete(),
        }));
    }
    private function mergeCollectedOperations(Collection $modelOperations): Collection
    {
        $collectedOperations = app(ModelOperationCollection::class);
        $generateModelOperationKey = function (ModelOperation $op): string
        {
            return $op->id.'.'.$op->type;
        };

        $opKeys = $modelOperations
            ->map(fn (ModelOperation $op) => $generateModelOperationKey($op))
            ->flip();

        $extraOperations = $collectedOperations->reject(fn (ModelOperation $op) => isset($opKeys[$generateModelOperationKey($op)]))
            ->groupBy(fn(ModelOperation $op) => $generateModelOperationKey($op))
            ->map(function (Collection $ops) {
                return $ops->reduce(function (?ModelOperation $carry, ModelOperation $op) {
                    if(!$carry) {
                        return $op;
                    }

                    if ($op->op === Operation::delete) {
                        $carry->op = Operation::delete;
                        return $carry;
                    }

                    if ($op->op === Operation::create) {
                        $carry->op = Operation::create;
                        return $carry;
                    }

                    return $carry;
                });
            })->values();
        ;
        return $modelOperations->concat($extraOperations)->values();
    }
}
