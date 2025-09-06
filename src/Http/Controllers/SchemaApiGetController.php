<?php

declare(strict_types=1);

namespace Wappo\LaravelSchemaApi\Http\Controllers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Gate;
use Wappo\LaravelSchemaApi\Attributes\ApiIgnore;
use Wappo\LaravelSchemaApi\Enums\Operation;
use Wappo\LaravelSchemaApi\Facades\ModelResolver;
use Wappo\LaravelSchemaApi\Facades\ResourceResolver;
use Wappo\LaravelSchemaApi\Http\Requests\SchemaApiGetRequest;
use Wappo\LaravelSchemaApi\Support\TypeToTableMapper;

final readonly class SchemaApiGetController
{
    public function __construct(private TypeToTableMapper $typeToTableMapper)
    {
    }

    public function __invoke(string $type, mixed $id, SchemaApiGetRequest $request)
    {
        $modelClass = ModelResolver::get(($this->typeToTableMapper)($type));
        if (!$modelClass) {
            throw new ModelNotFoundException(
                sprintf(
                    'Model class for %s was not found',
                    $type,
                ),
            );
        }

        $ref = new \ReflectionClass($modelClass);
        if($ref->getAttributes(ApiIgnore::class)) {
            throw new ModelNotFoundException(
                sprintf(
                    'Model %s is unlisted',
                    $type,
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

        Gate::authorize('view', [$modelClass, $id]);

        $model = $modelClass::findOrFail($id);

        $modelResourceClass = ResourceResolver::get($modelClass);
        $item = $modelResourceClass ? $modelResourceClass::make($model) : $model;

        $flags = (int) config('schema-api.http.json_encode_flags', JSON_UNESCAPED_UNICODE);
        $gzipLevel = (int) ($request->validated('gzip') ?? config('schema-api.http.gzip_level', 0));
        $gzipHeader = $gzipLevel > 0 ? ['Content-Encoding' => 'gzip'] : [];

        return response()->stream(function () use ($type, $item, $model, $flags, $gzipLevel) {
            $stream = fopen('php://output', 'wb');
            if ($gzipLevel > 0) {
                stream_filter_append(
                    $stream,
                    'zlib.deflate',
                    STREAM_FILTER_WRITE,
                    ['window' => 31, 'level' => $gzipLevel],
                );
            }

            $wrappedItem = [
                'id' => $model->getKey(),
                'op' => Operation::create->value,
                'type' => $type,
                'attr' => $item,
            ];
            fwrite($stream, json_encode($wrappedItem, $flags) . PHP_EOL);

            fclose($stream);
        }, 200, [
            ...$gzipHeader,
            'Content-Type' => 'application/stream+json',
        ]);
    }
}
