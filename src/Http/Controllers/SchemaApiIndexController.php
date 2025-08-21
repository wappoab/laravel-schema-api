<?php

declare(strict_types=1);

namespace Wappo\LaravelSchemaApi\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use ReflectionClass;
use Wappo\LaravelSchemaApi\Attributes\ApiIgnore;
use Wappo\LaravelSchemaApi\Attributes\ApplyQueryModifier;
use Wappo\LaravelSchemaApi\Contracts\QueryModifierInterface;
use Wappo\LaravelSchemaApi\Enums\Operation;
use Wappo\LaravelSchemaApi\Facades\ModelResolver;
use Wappo\LaravelSchemaApi\Facades\ResourceResolver;
use Wappo\LaravelSchemaApi\Http\Requests\SchemaApiIndexRequest;
use Wappo\LaravelSchemaApi\Support\ModelOperation;
use Wappo\LaravelSchemaApi\Support\TableToTypeMapper;
use Wappo\LaravelSchemaApi\Support\TypeToTableMapper;

final readonly class SchemaApiIndexController
{
    public function __construct(
        private TypeToTableMapper $typeToTableMapper,
        private TableToTypeMapper $tableToTypeMapper,
    ) {
    }

    /**
     * @throws \ReflectionException
     */
    public function __invoke(SchemaApiIndexRequest $request, ?string $type = null)
    {
        if (!$type) {
            $ops = collect(array_map(fn($table) => new ModelOperation(
                type: ($this->tableToTypeMapper)($table['name']),
                op: Operation::create,
                tableName: $table['name'],
            ), Schema::getTables()));
        } else {
            $ops = collect()->push(
                new ModelOperation(
                    type: $type,
                    op: Operation::create,
                    tableName: ($this->typeToTableMapper)($type),
                )
            );
        }

        $ops = $ops->each(function (ModelOperation $modelOperation) {
            $modelOperation->modelClass = ModelResolver::get($modelOperation->tableName);
        })
            ->filter(fn(ModelOperation $modelOperation) => (bool) $modelOperation->modelClass)
            ->filter(
                fn(ModelOperation $modelOperation) => !(new ReflectionClass(
                    $modelOperation->modelClass
                ))->getAttributes(ApiIgnore::class)
            )
            ->values();

        if ($ops->isEmpty()) {
            throw new ModelNotFoundException('No models found');
        }

        $ops
            ->each(fn(ModelOperation $modelOperation) => Gate::authorize('viewAny', $modelOperation->modelClass))
            ->each(function (ModelOperation $modelOperation) {
                $modelOperation->resourceClass = ResourceResolver::get($modelOperation->modelClass);
                $modelOperation->modelInstance = new $modelOperation->modelClass;
            });

        $gzipLevel = (int) ($request->validated('gzip') ?? config('schema-api.http.gzip_level', 0));
        $gzipHeader = $gzipLevel > 0 ? ['Content-Encoding' => 'gzip'] : [];

        return response()->stream(function () use ($ops, $request, $gzipLevel) {
            $flags = (int) config('schema-api.http.json_encode_flags', JSON_UNESCAPED_UNICODE);

            $stream = fopen('php://output', 'wb');
            if ($gzipLevel > 0) {
                stream_filter_append(
                    $stream,
                    'zlib.deflate',
                    STREAM_FILTER_WRITE,
                    ['window' => 31, 'level' => $gzipLevel],
                );
            }

            $ops->each(function (ModelOperation $modelOperation) use ($stream, $flags, $request) {
                if ($modelOperation->resourceClass) {
                    $resourceWrapper = fn($item) => $modelOperation->resourceClass::make($item);
                } else {
                    $resourceWrapper = fn($item) => (array) $item;
                }

                $query = $modelOperation->modelClass::query();
                $since = $request->validated('since');
                $pkName = $modelOperation->modelInstance->getKeyName();

                if ($since && in_array(SoftDeletes::class, class_uses_recursive($modelOperation->modelClass), true)) {
                    $createdAtColumn = $modelOperation->modelInstance->getCreatedAtColumn();
                    $updatedAtColumn = $modelOperation->modelInstance->getUpdatedAtColumn();
                    $deletedAtColumn = $modelOperation->modelInstance->getDeletedAtColumn();
                    $sinceTime = Carbon::parse($since);

                    $query = $query->withTrashed()->where(
                        fn(Builder $builder) => $builder->where($deletedAtColumn, '>=', $sinceTime)
                            ->orWhere($updatedAtColumn, '>=', $sinceTime)
                    );

                    $itemWrapper = function ($item) use ($deletedAtColumn, $createdAtColumn, $modelOperation, $pkName, $sinceTime, $resourceWrapper) {
                        $op = match (true) {
                            !is_null($item->{$deletedAtColumn}) => Operation::delete->value,
                            Carbon::parse($item->{$createdAtColumn})->isBefore($sinceTime) => Operation::update->value,
                            default => Operation::create->value,
                        };

                        return [
                            'id' => $item->{$pkName},
                            'op' => $op,
                            'type' => $modelOperation->type,
                            'attr' => $resourceWrapper($item),
                        ];
                    };
                } else {
                    $itemWrapper = function ($item) use ($modelOperation, $pkName, $resourceWrapper) {
                        return [
                            'id' => $item->{$pkName},
                            'op' => Operation::create->value,
                            'type' => $modelOperation->type,
                            'attr' => $resourceWrapper($item),
                        ];
                    };
                }

                $query = $this->applyQueryModifier($modelOperation->modelClass, $query);

                $cursor = $query->toBase()->cursor();
                foreach ($cursor as $item) {
                    fwrite($stream, json_encode($itemWrapper($item), $flags) . PHP_EOL);
                }
            });
            fclose($stream);
        }, 200, [
            ...$gzipHeader,
            'Content-Type' => 'application/stream+json',
        ]);
    }

    /**
     * @param class-string<\Illuminate\Database\Eloquent\Model> $modelClass
     * @param Builder $query
     *
     * @return Builder
     * @throws \ReflectionException
     */
    private function applyQueryModifier(string $modelClass, Builder $query): Builder
    {
        $ref = new ReflectionClass($modelClass);
        $attrs = $ref->getAttributes(ApplyQueryModifier::class);
        foreach ($attrs as $attr) {
            $cfg = $attr->newInstance();
            assert(
                $cfg instanceof ApplyQueryModifier,
                sprintf(
                    'Expected attribute instance of %s, got %s',
                    ApplyQueryModifier::class,
                    is_object($cfg) ? get_class($cfg) : gettype($cfg)
                )
            );

            assert(
                is_subclass_of($cfg->modifierClass, QueryModifierInterface::class),
                sprintf(
                    '%s must implement %s',
                    $cfg->modifierClass,
                    QueryModifierInterface::class
                )
            );

            $modifier = app($cfg->modifierClass, $cfg->parameters);
            $query = $modifier->modify($query);
        }

        return $query;
    }
}