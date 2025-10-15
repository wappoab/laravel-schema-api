<?php

declare(strict_types=1);

namespace Wappo\LaravelSchemaApi\Broadcasting;

use Illuminate\Support\Collection;
use Wappo\LaravelSchemaApi\Contracts\ModelOperationBroadcasterInterface;
use Wappo\LaravelSchemaApi\Contracts\ModelViewAuthorizerInterface;
use Wappo\LaravelSchemaApi\Support\ModelOperation;

class ModelOperationBroadcaster implements ModelOperationBroadcasterInterface
{
    public function __construct(
        protected ModelViewAuthorizerInterface $authorizer
    ) {}
    /**
     * Broadcast a collection of model operations to all users who can view them.
     *
     * @param \Illuminate\Support\Collection<int, \Wappo\LaravelSchemaApi\Support\ModelOperation> $operations
     * @return void
     */
    public function broadcast(Collection $operations): void
    {
        $operations->each(function (ModelOperation $operation) {
            $this->broadcastOperation($operation);
        });
    }

    /**
     * Broadcast a single model operation to all users who can view it.
     *
     * @param \Wappo\LaravelSchemaApi\Support\ModelOperation $operation
     * @return void
     */
    protected function broadcastOperation(ModelOperation $operation): void
    {
        // Skip if no model instance is available
        if (!$operation->modelInstance) {
            return;
        }

        $userIds = $this->authorizer->getUserIdsWhoCanView($operation->modelInstance);

        foreach ($userIds as $userId) {
            ModelOperationBroadcast::dispatch($userId, $operation);
        }
    }
}
