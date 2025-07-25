<?php

declare(strict_types=1);

namespace Wappo\LaravelSchemaApi\QueryModifiers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;
use Wappo\LaravelSchemaApi\Contracts\QueryModifierInterface;

class FilterQueryModifier implements QueryModifierInterface
{
    public function __construct(
        protected array $allowedAttributes = [],
    ) {}

    public function modify(Builder $query): Builder
    {
        $allowedAttributes = $this->allowedAttributes ?: Schema::getColumnListing($query->getModel()->getTable());

        collect(request()->query('filter', []))
            ->only($allowedAttributes)
            ->each(function ($value, $field) use ($query) {
                if ($value === '' || is_null($value)) {
                    $query->whereNull($field);
                }
                elseif (is_string($value) && str_contains($value, ',')) {
                    $items = array_map('trim', explode(',', $value));
                    $query->whereIn($field, $items);
                } else {
                    $query->where($field, $value);
                }
            });

        return $query;
    }
}