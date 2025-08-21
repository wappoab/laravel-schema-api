<?php

declare(strict_types=1);

namespace Wappo\LaravelSchemaApi\Support;

use Illuminate\Support\Str;

class TypeToTableMapper
{
    public function __invoke($type): string
    {
        return Str::snake(str_replace('-', '_', $type));
    }
}