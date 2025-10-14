<?php

declare(strict_types=1);

namespace Wappo\LaravelSchemaApi\Concerns;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use ReflectionClass;
use ReflectionMethod;
use Wappo\LaravelSchemaApi\Attributes\ApiInclude;

trait HasApiIncludeCascadeDelete
{
    protected static function bootHasApiIncludeCascadeDelete(): void
    {
        static::deleting(function ($model) {
            $ref = new ReflectionClass($model);

            foreach ($ref->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                $attrs = $method->getAttributes(ApiInclude::class);
                if (empty($attrs)) {
                    continue;
                }

                $apiInclude = $attrs[0]->newInstance();
                assert($apiInclude instanceof ApiInclude);
                if (!$apiInclude->cascadeDelete) {
                    continue;
                }

                $relationshipName = $method->getName();
                $relation = $model->{$relationshipName}();

                // Handle different relationship types
                if ($relation instanceof HasMany ||
                    $relation instanceof HasOne) {
                    $model->{$relationshipName}->each(function ($item) use ($apiInclude) {
                        if (static::shouldDeleteRelatedModel($item, $apiInclude)) {
                            $item->delete();
                        }
                    });
                } elseif ($relation instanceof BelongsTo) {
                    $related = $model->{$relationshipName};
                    if ($related && static::shouldDeleteRelatedModel($related, $apiInclude)) {
                        $related->delete();
                    }
                } elseif ($relation instanceof BelongsToMany) {
                    $model->{$relationshipName}->each(function ($item) use ($apiInclude) {
                        if (static::shouldDeleteRelatedModel($item, $apiInclude)) {
                            $item->delete();
                        }
                    });
                }
            }
        });

        static::restoring(function ($model) {
            $parentDeletedAt = $model->{$model->getDeletedAtColumn()};
            if (!$parentDeletedAt) {
                return;
            }

            $ref = new ReflectionClass($model);

            foreach ($ref->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                $attrs = $method->getAttributes(ApiInclude::class);
                if (empty($attrs)) {
                    continue;
                }

                $apiInclude = $attrs[0]->newInstance();
                if (!$apiInclude->cascadeDelete) {
                    continue;
                }

                $relationshipName = $method->getName();
                $relation = $model->{$relationshipName}();
                $relatedModel = $relation->getRelated();

                if (!$relatedModel::isSoftDeletable()) {
                    continue;
                }

                if ($relation instanceof HasMany ||
                    $relation instanceof HasOne) {
                    // Query for related models, including trashed ones
                    $relatedItems = $model->{$relationshipName}()->withTrashed()->get();
                    foreach ($relatedItems as $item) {
                        if (static::shouldRestoreRelatedModel($item, $parentDeletedAt)) {
                            $item->restore();
                        }
                    }
                } elseif ($relation instanceof BelongsTo) {
                    $related = $model->{$relationshipName}()->withTrashed()->first();
                    if ($related && $related->trashed() && static::shouldRestoreRelatedModel($related, $parentDeletedAt)) {
                        $related->restore();
                    }
                } elseif ($relation instanceof BelongsToMany) {
                    // Query for related models through pivot, including trashed ones
                    $relatedItems = $model->{$relationshipName}()->withTrashed()->get();

                    foreach ($relatedItems as $item) {
                        if (static::shouldRestoreRelatedModel($item, $parentDeletedAt)) {
                            $item->restore();
                        }
                    }
                }
            }
        });
    }

    /**
     * Determine if a related model should be deleted.
     *
     * Related models will be deleted if:
     * - They use SoftDeletes (safe to delete)
     * - OR forceDelete is explicitly true
     *
     * @param \Illuminate\Database\Eloquent\Model $relatedModel
     * @param ApiInclude $apiInclude
     * @return bool
     */
    protected static function shouldDeleteRelatedModel($relatedModel, ApiInclude $apiInclude): bool
    {
        return $relatedModel::isSoftDeletable() || $apiInclude->forceDelete;
    }

    /**
     * Determine if a related model should be restored.
     *
     * Only restore related models that were deleted at approximately the same time
     * as the parent model. This prevents restoring models that were deleted
     * independently before the parent was deleted.
     *
     * @param \Illuminate\Database\Eloquent\Model $relatedModel
     * @param Carbon|\DateTimeInterface $parentDeletedAt
     * @return bool
     */
    protected static function shouldRestoreRelatedModel($relatedModel, $parentDeletedAt): bool
    {
        // Related model must use soft deletes
        if (!$relatedModel::isSoftDeletable()) {
            return false;
        }

        // Related model must be trashed
        if (!$relatedModel->trashed()) {
            return false;
        }

        $relatedDeletedAt = $relatedModel->{$relatedModel->getDeletedAtColumn()};
        if (!$relatedDeletedAt) {
            return false;
        }

        // Convert to Carbon for comparison if needed
        if (!$parentDeletedAt instanceof Carbon) {
            $parentDeletedAt = Carbon::parse($parentDeletedAt);
        }
        if (!$relatedDeletedAt instanceof Carbon) {
            $relatedDeletedAt = Carbon::parse($relatedDeletedAt);
        }

        $tolerance = config('schema-api.restore_soft_delete_tolerance_in_seconds', 1);

        return abs($parentDeletedAt->diffInSeconds($relatedDeletedAt)) <= $tolerance;
    }
}
