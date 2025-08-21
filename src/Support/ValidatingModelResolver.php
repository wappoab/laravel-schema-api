<?php

declare(strict_types=1);

namespace Wappo\LaravelSchemaApi\Support;

use Illuminate\Database\Eloquent\Model;
use Wappo\LaravelSchemaApi\Contracts\ModelResolverInterface;

final readonly class ValidatingModelResolver implements ModelResolverInterface
{
    public function __construct(private ModelResolverInterface $inner)
    {
    }

    public function get(string $type): ?string
    {
        $resolved = $this->inner->get($type);

        if($resolved === null) {
            return null;
        }

        if (!is_a($resolved, Model::class, true)) {
            throw new \UnexpectedValueException(sprintf(
                'Resolved class %s for table %s must extend %s.',
                $resolved,
                $type,
                Model::class
            ));
        }

        return $resolved;
    }
}
