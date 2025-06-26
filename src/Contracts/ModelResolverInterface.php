<?php

declare(strict_types=1);

namespace Wappo\LaravelSchemaApi\Contracts;

interface ModelResolverInterface
{
    public function resolve(string $table): ?string;
    public function __invoke(string $table): ?string;
}