<?php

declare(strict_types=1);

namespace Wappo\LaravelSchemaApi\ResourceResolvers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Wappo\LaravelSchemaApi\Contracts\ResourceResolverInterface;
use Wappo\LaravelSchemaApi\Contracts\SchemaApiJsonResource;

final readonly class GuessResourceNameResolver implements ResourceResolverInterface
{
    public function get(string $modelClass): ?string
    {
        assert(
            is_subclass_of($modelClass, Model::class),
            sprintf(
                '%s must be a subclass of %s',
                $modelClass,
                Model::class
            )
        );
        $suggestions = $modelClass::guessResourceName();

        /** @var class-string<SchemaApiJsonResource> $candidate */
        return Arr::first($suggestions, static fn($candidate) =>
            is_string($candidate) && class_exists($candidate));
    }
}