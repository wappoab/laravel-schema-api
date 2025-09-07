<?php

declare(strict_types=1);

namespace Wappo\LaravelSchemaApi\Support;

use Illuminate\Support\Collection;

class ModelOperationCollection extends Collection
{
    public function clear(): static
    {
        $this->splice(0);

        return $this;
    }
}
