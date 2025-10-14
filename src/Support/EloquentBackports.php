<?php

declare(strict_types=1);

namespace Wappo\LaravelSchemaApi\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

final class EloquentBackports
{
    protected static array $isSoftDeletable;

    /**
     *
     * Backport of Model::isSoftDeletable() added in Laravel 12.19
     *
     * @param \Illuminate\Database\Eloquent\Model|string $model
     * @deprecated Will be removed when this package no longer supports Laravel versions earlier than 12.19
     *
     */
    public static function isSoftDeletable(Model|string $model): bool
    {
        if ($model instanceof Model) {
            $model = $model::class;
        }

        return self::$isSoftDeletable[$model] ??= in_array(SoftDeletes::class, class_uses_recursive($model));
    }
}
