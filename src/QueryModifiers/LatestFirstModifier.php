<?php

declare(strict_types=1);

namespace Wappo\LaravelSchemaApi\QueryModifiers;

use Illuminate\Database\Eloquent\Builder;
use Wappo\LaravelSchemaApi\Contracts\QueryModifierInterface;

class LatestFirstModifier implements QueryModifierInterface
{
    public function modify(Builder $query): Builder
    {
        return $query->orderByDesc($query->getModel()->getCreatedAtColumn());
    }
}