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

    public function get(string $table): ?string
    {
        $resolved = $this->inner->get($table);

        if($resolved === null) {
            return null;
        }

        if (!is_a($resolved, Model::class, true)) {
            throw new \UnexpectedValueException(sprintf(
                'Resolved class %s for table %s must extend %s.',
                $resolved,
                $table,
                Model::class
            ));
        }

        return $resolved;
    }
}
