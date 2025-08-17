<?php

declare(strict_types = 1);

namespace Wappo\LaravelSchemaApi\Contracts;

interface SchemaApiJsonResource extends \JsonSerializable
{
    public static function make(mixed ...$parameters): static;
}