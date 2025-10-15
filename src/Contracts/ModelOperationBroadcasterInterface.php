<?php

declare(strict_types=1);

namespace Wappo\LaravelSchemaApi\Contracts;

use Illuminate\Support\Collection;

interface ModelOperationBroadcasterInterface
{
    /**
     * Broadcast a collection of model operations to all users who can view them.
     *
     * @param \Illuminate\Support\Collection<int, \Wappo\LaravelSchemaApi\Support\ModelOperation> $operations
     * @return void
     */
    public function broadcast(Collection $operations): void;
}
