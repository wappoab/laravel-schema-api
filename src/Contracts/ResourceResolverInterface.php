<?php

declare(strict_types=1);

namespace Wappo\LaravelSchemaApi\Contracts;

interface ResourceResolverInterface
{
    public function get(string $modelClass): ?string;
}