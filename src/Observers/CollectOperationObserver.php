<?php

declare(strict_types=1);

namespace Wappo\LaravelSchemaApi\Observers;

use Illuminate\Database\Eloquent\Model;
use Wappo\LaravelSchemaApi\Enums\Operation;
use Wappo\LaravelSchemaApi\Facades\ResourceResolver;
use Wappo\LaravelSchemaApi\Support\ModelOperation;
use Wappo\LaravelSchemaApi\Support\ModelOperationCollection;
use Wappo\LaravelSchemaApi\Support\TableToTypeMapper;

class CollectOperationObserver
{
    public bool $afterCommit = true;

    public function __construct(
        protected ModelOperationCollection $operations,
        private readonly TableToTypeMapper $tableToTypeMapper,
    ) {
    }

    public function created(Model $model): void
    {
        $this->operations->prepend($this->makeOperation(Operation::create, $model));
    }

    public function updated(Model $model): void
    {
        $this->operations->prepend($this->makeOperation(Operation::update, $model));
    }

    public function deleted(Model $model): void
    {
        $this->operations->prepend($this->makeOperation(Operation::delete, $model));
    }

    public function restored(Model $model): void
    {
        $this->operations->prepend($this->makeOperation(Operation::create, $model));
    }

    private function makeOperation(Operation $op, Model $model): ModelOperation {
        if($op !== Operation::delete) {
            $modelResourceClass = ResourceResolver::get($model::class);
            $item = $modelResourceClass ? $modelResourceClass::make($model) : $model->getOriginal();
            return new ModelOperation(
                op: $op,
                id: $model->getKey(),
                type: ($this->tableToTypeMapper)($model->getTable()),
                attr: $item,
                modelClass: $model::class,
                modelInstance: $model,
            );
        }
        return new ModelOperation(
            op: $op,
            id: $model->getKey(),
            type: ($this->tableToTypeMapper)($model->getTable()),
            modelClass: $model::class,
            modelInstance: $model,
        );
    }
}