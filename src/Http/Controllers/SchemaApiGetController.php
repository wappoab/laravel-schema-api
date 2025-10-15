<?php

declare(strict_types=1);

namespace Wappo\LaravelSchemaApi\Http\Controllers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Gate;
use Wappo\LaravelSchemaApi\Attributes\ApiIgnore;
use Wappo\LaravelSchemaApi\Attributes\ApiInclude;
use Wappo\LaravelSchemaApi\Enums\Operation;
use Wappo\LaravelSchemaApi\Facades\ModelResolver;
use Wappo\LaravelSchemaApi\Facades\ResourceResolver;
use Wappo\LaravelSchemaApi\Http\Requests\SchemaApiGetRequest;
use Wappo\LaravelSchemaApi\Support\RelationshipStreamer;
use Wappo\LaravelSchemaApi\Support\TypeToTableMapper;

final readonly class SchemaApiGetController
{
    public function __construct(
        private TypeToTableMapper $typeToTableMapper,
        private RelationshipStreamer $relationshipStreamer,
    ) {
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

        // Use toBase() for efficiency - get raw data
        $item = $modelClass::whereKey($id)->toBase()->first();

        if(!$item) {
            throw new ModelNotFoundException();
        }

        // Get relationships to include
        $ref = new \ReflectionClass($modelClass);
        $includedRelationships = [];

        foreach ($ref->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            $attrs = $method->getAttributes(ApiInclude::class);
            if (!empty($attrs)) {
                $includedRelationships[] = $method->getName();
            }
        }

        $flags = (int) config('schema-api.http.json_encode_flags', JSON_UNESCAPED_UNICODE);
        $gzipLevel = (int) ($request->validated('gzip') ?? config('schema-api.http.gzip_level', 0));
        $gzipHeader = $gzipLevel > 0 ? ['Content-Encoding' => 'gzip'] : [];

        return response()->stream(function () use ($type, $item, $modelClass, $includedRelationships, $id, $flags, $gzipLevel) {
            $stream = fopen('php://output', 'wb');
            if ($gzipLevel > 0) {
                stream_filter_append(
                    $stream,
                    'zlib.deflate',
                    STREAM_FILTER_WRITE,
                    ['window' => 31, 'level' => $gzipLevel],
                );
            }

            // Stream the main item
            if ($resource = ResourceResolver::get($modelClass)) {
                $attr = $resource::make($item);
            } else {
                $attr = (array) $item;
            }

            $wrappedItem = [
                'id' => $id,
                'op' => Operation::create->value,
                'type' => $type,
                'attr' => $attr,
            ];
            fwrite($stream, json_encode($wrappedItem, $flags) . PHP_EOL);

            // Stream included relationships if any
            if (!empty($includedRelationships)) {
                $pkName = (new $modelClass)->getKeyName();
                $this->relationshipStreamer->streamRelationshipsForBatch(
                    $modelClass,
                    [$item],
                    $includedRelationships,
                    $pkName,
                    $stream,
                    $flags
                );
            }

            fclose($stream);
        }, 200, [
            ...$gzipHeader,
            'Content-Type' => 'application/stream+json',
        ]);
    }
}
