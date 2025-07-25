<?php

declare(strict_types=1);

namespace Wappo\LaravelSchemaApi\Http\Controllers;

use ReflectionClass;
use Wappo\LaravelSchemaApi\Attributes\ApplyQueryModifier;
use Wappo\LaravelSchemaApi\Contracts\QueryModifierInterface;
use Wappo\LaravelSchemaApi\Facades\ModelResolver;
use Wappo\LaravelSchemaApi\Http\Requests\SchemaApiIndexRequest;
use Wappo\LaravelSchemaApi\Support\ModelResourceResolver;

class SchemaApiIndexController
{
    /**
     * @throws \ReflectionException
     */
    public function __invoke(string $table, SchemaApiIndexRequest $request)
    {
        $modelClass = ModelResolver::get($table);
        if (!$modelClass) {
            abort(404);
        }

        $query = $modelClass::query();
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

        $modelResourceClass = app(ModelResourceResolver::class)->get($modelClass);
        if($modelResourceClass)  {
            $printer = fn ($model) => json_encode(
                    $modelResourceClass::make($model)->resolve(),
                    config('schema-api.http.json_encode_flags', JSON_UNESCAPED_UNICODE),
                ) . PHP_EOL;
        }
        else {
            $printer = fn ($model) => json_encode(
                    $model,
                    config('schema-api.http.json_encode_flags', JSON_UNESCAPED_UNICODE),
                ) . PHP_EOL;
        }

        $gzipLevel = $request->validated('gzip') ?? config('schema-api.http.gzip_level', 0);
        $gzipHeader = $gzipLevel > 0 ? ['Content-Encoding' => 'gzip'] : [];

        return response()->stream(function () use ($gzipLevel, $query, $printer) {
            $stream = fopen('php://output', 'wb');
            if ($gzipLevel > 0) {
                stream_filter_append(
                    $stream,
                    'zlib.deflate',
                    STREAM_FILTER_WRITE,
                    ['window' => 31, 'level' => $gzipLevel],
                );
            }

            $cursor = $query->cursor();
            foreach ($cursor as $item) {
                fwrite($stream, $printer($item));
            }

            fclose($stream);
        }, 200, [
            ...$gzipHeader,
            'Content-Type' => 'application/stream+json',
        ]);
    }
}