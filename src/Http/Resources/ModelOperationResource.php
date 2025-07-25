<?php

declare(strict_types=1);

namespace Wappo\LaravelSchemaApi\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Wappo\LaravelSchemaApi\Support\ModelOperation;

class ModelOperationResource extends JsonResource
{
    public function __construct(ModelOperation $resource)
    {
        parent::__construct($resource);
    }

    public function toArray($request): array
    {
        $op = $this->resource;
        assert($op instanceof ModelOperation);

        if ($op->resourceClass && $op->modelInstance) {
            $obj = $op->resourceClass::make($op->modelInstance)->resolve();
        } elseif ($op->modelInstance) {
            $obj = $op->modelInstance->toArray();
        } else {
            $obj = null;
        }

        return [
            '@id'  => $op->id,
            'name' => $op->collectionName,
            'operation' => $op->operation->name,
            'obj'  => $obj,
        ];
    }
}