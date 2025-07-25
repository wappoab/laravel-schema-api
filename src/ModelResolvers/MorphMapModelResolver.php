<?php

declare(strict_types=1);

namespace Wappo\LaravelSchemaApi\ModelResolvers;

use Illuminate\Database\Eloquent\Relations\Relation;
use Wappo\LaravelSchemaApi\Contracts\ModelResolverInterface;

class MorphMapModelResolver implements ModelResolverInterface
{
    public function get(string $table): ?string
    {
        return Relation::getMorphedModel($table);
    }
}