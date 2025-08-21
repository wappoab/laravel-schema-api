<?php

declare(strict_types=1);

namespace Wappo\LaravelSchemaApi\Contracts;

interface ModelResolverInterface
{
    /**
     * Get an Eloquent model class from a table name.
     *
     * @param  string $type
     *
     * @return class-string<\Illuminate\Database\Eloquent\Model>|null
     */
    public function get(string $type): ?string;
}