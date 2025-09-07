<?php

declare(strict_types=1);

namespace Wappo\LaravelSchemaApi\Concerns;

use Wappo\LaravelSchemaApi\Observers\CollectOperationObserver;

/**
 * Use this trait in your models to make it track changes that will be passed on when updates happen
 */
trait CollectsOperations
{
    protected static function bootCollectsOperations(): void
    {
        self::observe(CollectOperationObserver::class);
    }
}
