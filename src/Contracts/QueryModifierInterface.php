<?php

declare(strict_types=1);

namespace Wappo\LaravelSchemaApi\Contracts;

use Illuminate\Database\Eloquent\Builder;

interface QueryModifierInterface
{
    public function modify(Builder $query): Builder;
}