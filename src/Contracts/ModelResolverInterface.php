<?php

declare(strict_types=1);

namespace Wappo\LaravelSchemaApi\Contracts;

interface ModelResolverInterface
{
    public function get(string $table): ?string;
}