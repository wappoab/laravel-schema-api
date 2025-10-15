<?php

declare(strict_types=1);

namespace Wappo\LaravelSchemaApi\Listeners;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Wappo\LaravelSchemaApi\Contracts\ModelOperationBroadcasterInterface;
use Wappo\LaravelSchemaApi\Enums\Operation;
use Wappo\LaravelSchemaApi\Facades\ResourceResolver;
use Wappo\LaravelSchemaApi\Support\ModelOperation;
use Wappo\LaravelSchemaApi\Support\TableToTypeMapper;

class ModelEventBroadcastListener
{
    public function __construct(
        protected ModelOperationBroadcasterInterface $broadcaster,
        protected TableToTypeMapper $tableToTypeMapper,
    ) {}

    /**
     * Handle model created event.
     */
    public function created(string $event, array $data): void
    {
        [$model] = $data;
        $this->broadcastModelOperation($model, Operation::create);
    }

    /**
     * Handle model updated event.
     */
    public function updated(string $event, array $data): void
    {
        [$model] = $data;
        $this->broadcastModelOperation($model, Operation::update);
    }

    /**
     * Handle model deleted event.
     */
    public function deleted(string $event, array $data): void
    {
        [$model] = $data;
        $this->broadcastModelOperation($model, Operation::delete);
    }

    /**
     * Handle model restored event.
     */
    public function restored(string $event, array $data): void
    {
        [$model] = $data;
        $this->broadcastModelOperation($model, Operation::create);
    }

    /**
     * Create and broadcast a ModelOperation from a model event.
     */
    protected function broadcastModelOperation(Model $model, Operation $operation): void
    {
        $modelOperation = $this->makeModelOperation($model, $operation);

        $this->broadcaster->broadcast(
            collect([$modelOperation])
        );
    }

    /**
     * Create a ModelOperation from a model and operation type.
     */
    protected function makeModelOperation(Model $model, Operation $operation): ModelOperation
    {
        if ($operation !== Operation::delete) {
            $modelResourceClass = ResourceResolver::get($model::class);
            $attr = $modelResourceClass ? $modelResourceClass::make($model) : $model->getOriginal();

            return new ModelOperation(
                op: $operation,
                id: $model->getKey(),
                type: ($this->tableToTypeMapper)($model->getTable()),
                attr: $attr,
                modelClass: $model::class,
                modelInstance: $model,
            );
        }

        return new ModelOperation(
            op: $operation,
            id: $model->getKey(),
            type: ($this->tableToTypeMapper)($model->getTable()),
            modelClass: $model::class,
            modelInstance: $model,
        );
    }

    /**
     * Subscribe to model events.
     */
    public function subscribe($events): array
    {
        return [
            'eloquent.created:*' => 'created',
            'eloquent.updated:*' => 'updated',
            'eloquent.deleted:*' => 'deleted',
            'eloquent.restored:*' => 'restored',
        ];
    }
}
