<?php

declare(strict_types=1);

namespace Wappo\LaravelSchemaApi\Support;

use Illuminate\Database\Eloquent\Model;
use Wappo\LaravelSchemaApi\Enums\Operation;

class ModelOperation
{
    /**
     * @param \Wappo\LaravelSchemaApi\Enums\Operation|null $operation
     * @param mixed|null $id
     * @param array $attributes
     * @param string|null $collectionName
     * @param \Illuminate\Database\Eloquent\Model|null $modelInstance
     * @param class-string<\Illuminate\Database\Eloquent\Model>|null $modelClass
     * @param class-string<\Illuminate\Http\Resources\Json\JsonResource>|null $resourceClass
     */
    public function __construct(
        public ?Operation $operation = null,
        public mixed $id = null,
        public array $attributes = [],
        public ?string $collectionName = null,
        public ?Model $modelInstance = null,
        public ?string $modelClass = null,
        public ?string $resourceClass = null,
    ) {}
}