<?php

declare(strict_types=1);

namespace Wappo\LaravelSchemaApi\Concerns;

use Illuminate\Support\Facades\Config;

/**
 * Use this trait in your models to add timezone support in timestamps.
 */
trait HasDateFormat
{
    public function getDateFormat(): string
    {
        return Config::get('schema-api.date_format');
    }
}
