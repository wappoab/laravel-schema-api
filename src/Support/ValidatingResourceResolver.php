<?php

declare(strict_types=1);

namespace Wappo\LaravelSchemaApi\Support;

use Illuminate\Database\Eloquent\Model;
use Wappo\LaravelSchemaApi\Contracts\ResourceResolverInterface;
use Wappo\LaravelSchemaApi\Contracts\SchemaApiJsonResource;

final readonly class ValidatingResourceResolver implements ResourceResolverInterface
{
    public function __construct(private ResourceResolverInterface $inner)
    {
    }

    public function get(string $modelClass): ?string
    {
        if (!is_subclass_of($modelClass, Model::class)) {
            throw new \InvalidArgumentException(sprintf(
                '%s must be a subclass of %s',
                $modelClass,
                Model::class
            ));
        }

        $resolved = $this->inner->get($modelClass);

        if($resolved === null) {
            return null;
        }

        if (!is_a($resolved, SchemaApiJsonResource::class, true)) {
            throw new \UnexpectedValueException(sprintf(
                'Resolved class %s for model %s must implement %s.',
                $resolved,
                $modelClass,
                SchemaApiJsonResource::class
            ));
        }

        return $resolved;
    }
}
