<?php

declare(strict_types=1);

namespace Wappo\LaravelSchemaApi\QueryModifiers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;
use Wappo\LaravelSchemaApi\Contracts\QueryModifierInterface;

class SortQueryModifier implements QueryModifierInterface
{
    public function __construct(
        protected array $allowedAttributes = [],
    ) {}

    public function modify(Builder $query): Builder
    {
        $allowedAttributes = $this->allowedAttributes ?: Schema::getColumnListing($query->getModel()->getTable());

        collect(explode(",", request()->query('sort', '')))
            ->filter()
            ->each(function (string $piece) use ($query, $allowedAttributes) {
                $direction = 'asc';
                if (str_starts_with($piece, '-')) {
                    $direction = 'desc';
                    $piece = substr($piece, 1);
                }
                if (!in_array($piece, $allowedAttributes, true)) {
                    return;
                }
                $query->orderBy($piece, $direction);
            });
        return $query;
    }
}