<?php

declare(strict_types=1);

namespace Wappo\LaravelSchemaApi\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class AsUuid implements CastsAttributes
{
    /**
     * Cast the given value to a Uuid object.
     *
     * @param Model $model
     * @param string $key
     * @param mixed $value
     * @param array $attributes
     *
     * @return ?UuidInterface
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?UuidInterface
    {
        if (!$value) {
            return null;
        }

        if ($value instanceof UuidInterface) {
            return $value;
        }

        return Uuid::fromString($value);
    }

    /**
     * Prepare the given value for storage.
     *
     * @param Model $model
     * @param string $key
     * @param mixed $value
     * @param array $attributes
     *
     * @return ?string
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        return $value ? (string) $value : null;
    }
}
