<?php

declare(strict_types=1);

namespace Wappo\LaravelSchemaApi\Support;

use Illuminate\Database\Eloquent\Model;
use Wappo\LaravelSchemaApi\Enums\Operation;

class ModelOperation
{
    /**
     * @param \Wappo\LaravelSchemaApi\Enums\Operation|null $op
     * @param mixed|null $id
     * @param array $attr
     * @param string|null $tableName
     * @param \Illuminate\Database\Eloquent\Model|null $modelInstance
     * @param class-string<\Illuminate\Database\Eloquent\Model>|null $modelClass
     * @param class-string<\Illuminate\Http\Resources\Json\JsonResource>|null $resourceClass
     */
    public function __construct(
        public ?Operation $op = null,
        public mixed $id = null,
        public ?string $type = null,
        public ?array $attr = [],
        public ?string $tableName = null,
        public ?Model $modelInstance = null,
        public ?string $modelClass = null,
        public ?string $resourceClass = null,
    ) {}
}