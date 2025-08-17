<?php

declare(strict_types=1);

namespace Wappo\LaravelSchemaApi\Concerns;

trait DataFromAsMake
{
    /**
     * For Spatie\LaravelData\Data classes that have `from(mixed $payload): static`.
     * If multiple parameters are passed, pass them as an array payload.
     */
    public static function make(mixed ...$parameters): static
    {
        $payload = match (count($parameters)) {
            0 => null,
            1 => $parameters[0],
            default => $parameters, // allow make($a, $b) -> from([$a, $b])
        };

        /** @phpstan-ignore-next-line */
        return static::from($payload);
    }
}
