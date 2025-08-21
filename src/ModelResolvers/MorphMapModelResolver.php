<?php

declare(strict_types=1);

namespace Wappo\LaravelSchemaApi\ModelResolvers;

use Illuminate\Database\Eloquent\Relations\Relation;
use Wappo\LaravelSchemaApi\Contracts\ModelResolverInterface;

final readonly class MorphMapModelResolver implements ModelResolverInterface
{
    public function get(string $type): ?string
    {
        return Relation::getMorphedModel($type);
    }
}