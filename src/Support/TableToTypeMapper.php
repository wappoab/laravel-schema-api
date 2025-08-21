<?php

declare(strict_types=1);

namespace Wappo\LaravelSchemaApi\Support;

use Illuminate\Support\Str;

class TableToTypeMapper
{
    public function __invoke($table): string
    {
        return Str::slug($table);
    }
}