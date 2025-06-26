<?php

declare(strict_types=1);

namespace Wappo\LaravelSchemaApi\Concerns;

/**
 * Use this trait in your models to add timezone support in timestamps.
 */
trait HasDateFormat
{
    public function getDateFormat(): string
    {
        return config('schema-api.date_format');
    }
}
