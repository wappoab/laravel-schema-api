<?php

declare(strict_types=1);

namespace Wappo\LaravelSchemaApi\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use ReflectionClass;
use Wappo\LaravelSchemaApi\Attributes\ApiIgnore;
use Wappo\LaravelSchemaApi\Attributes\ApplyQueryModifier;
use Wappo\LaravelSchemaApi\Attributes\UseSchemaApiJsonResource;
use Wappo\LaravelSchemaApi\Contracts\QueryModifierInterface;
use Wappo\LaravelSchemaApi\Enums\Operation;
use Wappo\LaravelSchemaApi\Facades\ModelResolver;
use Wappo\LaravelSchemaApi\Facades\ResourceResolver;
use Wappo\LaravelSchemaApi\Http\Requests\SchemaApiIndexRequest;

class SchemaApiIndexController
{
    /**
     * @throws \ReflectionException
     */
    public function __invoke(SchemaApiIndexRequest $request, ?string $table = null)
    {
        $flags      = (int) config('schema-api.http.json_encode_flags', JSON_UNESCAPED_UNICODE);
        $gzipLevel  = (int) ($request->validated('gzip') ?? config('schema-api.http.gzip_level', 0));
        $gzipHeader = $gzipLevel > 0 ? ['Content-Encoding' => 'gzip'] : [];

        $tables = $table ? [$table] : array_map(fn ($tbl) => $tbl['name'], Schema::getTables());

        return response()->stream(function () use ($tables, $request, $flags, $gzipLevel) {
            $stream = fopen('php://output', 'wb');
            if ($gzipLevel > 0) {
                stream_filter_append(
                    $stream,
                    'zlib.deflate',
                    STREAM_FILTER_WRITE,
                    ['window' => 31, 'level' => $gzipLevel],
                );
            }

            foreach ($tables as $table) {
                $modelClass = ModelResolver::get($table);
                if (!$modelClass) {
                    continue;
                }

                $ref = new ReflectionClass($modelClass);
                if($ref->getAttributes(ApiIgnore::class)) {
                    continue;
                }

                assert(
                    is_subclass_of($modelClass, Model::class),
                    sprintf('Class %s must extend %s', $modelClass, Model::class)
                );

                $modelResourceClass = ResourceResolver::get($modelClass);
                if ($modelResourceClass) {
                    $resourceWrapper = fn($item) => $modelResourceClass::make($item);
                } else {
                    $resourceWrapper = fn($item) => (array) $item;
                }

                $query = $modelClass::query();
                $since = $request->validated('since');
                $model = new $modelClass;
                $pkName = $model->getKeyName();
                if ($since && in_array(SoftDeletes::class, class_uses_recursive($modelClass), true)) {
                    $createdAtColumn = $model->getCreatedAtColumn();
                    $updatedAtColumn = $model->getUpdatedAtColumn();
                    $deletedAtColumn = $model->getDeletedAtColumn();
                    $sinceTime = Carbon::parse($since);

                    $query = $query->withTrashed()->where(
                        fn(Builder $builder) => $builder->where($deletedAtColumn, '>=', $sinceTime)
                            ->orWhere($updatedAtColumn, '>=', $sinceTime)
                    );

                    $itemWrapper = function ($item) use ($deletedAtColumn, $createdAtColumn, $table, $pkName, $sinceTime, $resourceWrapper) {
                        $op = match (true) {
                            $item->{$deletedAtColumn} => Operation::delete->value,
                            Carbon::parse($item->{$createdAtColumn})->isAfter($sinceTime) => Operation::create->value,
                            default => Operation::update->value,
                        };

                        return [
                            'id' => $item->{$pkName},
                            'op' => $op,
                            'type' => $table,
                            'attr' => $resourceWrapper($item),
                        ];
                    };
                } else {
                    $itemWrapper = function ($item) use ($table, $pkName, $resourceWrapper) {
                        return [
                            'id' => $item->{$pkName},
                            'op' => Operation::create->value,
                            'type' => $table,
                            'attr' => $resourceWrapper($item),
                        ];
                    };
                }

                $query = $this->applyQueryModifier($modelClass, $query);

                $cursor = $query->toBase()->cursor();
                foreach ($cursor as $item) {
                    fwrite($stream, json_encode($itemWrapper($item), $flags) . PHP_EOL);
                }
            }
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