<?php

declare(strict_types=1);

namespace Wappo\LaravelSchemaApi\Support;

use Wappo\LaravelSchemaApi\Enums\Operation;
use Wappo\LaravelSchemaApi\Facades\ResourceResolver;

final readonly class RelationshipStreamer
{
    public function __construct(
        private TableToTypeMapper $tableToTypeMapper,
    ) {
    }

    /**
     * @param class-string<\Illuminate\Database\Eloquent\Model> $modelClass
     * @param array<object> $batch
     * @param array<string> $relationships
     * @param string $pkName
     * @param resource $stream
     * @param int $flags
     * @return void
     * @throws \ReflectionException
     */
    public function streamRelationshipsForBatch(string $modelClass, array $batch, array $relationships, string $pkName, $stream, int $flags): void
    {
        // Extract parent IDs from batch
        $parentIds = array_map(fn($item) => $item->{$pkName}, $batch);

        // Create a temporary model instance to introspect relationships
        $tempModel = new $modelClass;

        foreach ($relationships as $relationshipName) {
            // Get the relationship instance
            $relation = $tempModel->{$relationshipName}();

            // Determine relationship type and load accordingly
            if ($relation instanceof \Illuminate\Database\Eloquent\Relations\HasMany) {
                $this->streamHasManyRelation($relation, $parentIds, $stream, $flags);
            } elseif ($relation instanceof \Illuminate\Database\Eloquent\Relations\BelongsTo) {
                $this->streamBelongsToRelation($relation, $batch, $pkName, $stream, $flags);
            } elseif ($relation instanceof \Illuminate\Database\Eloquent\Relations\BelongsToMany) {
                $this->streamBelongsToManyRelation($relation, $parentIds, $stream, $flags);
            } elseif ($relation instanceof \Illuminate\Database\Eloquent\Relations\HasOne) {
                $this->streamHasOneRelation($relation, $parentIds, $stream, $flags);
            }
        }
    }

    /**
     * @param \Illuminate\Database\Eloquent\Relations\HasMany $relation
     * @param array<mixed> $parentIds
     * @param resource $stream
     * @param int $flags
     * @return void
     */
    private function streamHasManyRelation($relation, array $parentIds, $stream, int $flags): void
    {
        $foreignKey = $relation->getForeignKeyName();
        $relatedModel = $relation->getRelated();
        $relatedTable = $relatedModel->getTable();
        $relatedType = ($this->tableToTypeMapper)($relatedTable);
        $relatedPkName = $relatedModel->getKeyName();
        $relatedClass = get_class($relatedModel);

        // Get the column name without table prefix for whereIn
        $foreignKeyColumn = last(explode('.', $foreignKey));

        // Query the related model directly to avoid unwanted constraints
        $relatedRecords = $relatedClass::whereIn($foreignKeyColumn, $parentIds)->toBase()->cursor();

        $resourceClass = ResourceResolver::get($relatedClass);

        foreach ($relatedRecords as $record) {
            if ($resourceClass) {
                $attr = $resourceClass::make($record);
            } else {
                $attr = (array) $record;
            }

            $wrappedItem = [
                'id' => $record->{$relatedPkName},
                'op' => Operation::create->value,
                'type' => $relatedType,
                'attr' => $attr,
            ];

            fwrite($stream, json_encode($wrappedItem, $flags) . PHP_EOL);
        }
    }

    /**
     * @param \Illuminate\Database\Eloquent\Relations\BelongsTo $relation
     * @param array<object> $batch
     * @param string $pkName
     * @param resource $stream
     * @param int $flags
     * @return void
     */
    private function streamBelongsToRelation($relation, array $batch, string $pkName, $stream, int $flags): void
    {
        $foreignKey = $relation->getForeignKeyName();
        $ownerKey = $relation->getOwnerKeyName();
        $relatedModel = $relation->getRelated();
        $relatedTable = $relatedModel->getTable();
        $relatedType = ($this->tableToTypeMapper)($relatedTable);
        $relatedPkName = $relatedModel->getKeyName();
        $relatedClass = get_class($relatedModel);

        // Extract foreign key values from batch items
        $foreignKeyValues = array_filter(array_map(fn($item) => $item->{$foreignKey} ?? null, $batch));

        if (empty($foreignKeyValues)) {
            return;
        }

        // Query the related model directly to avoid unwanted constraints from getQuery()
        $relatedRecords = $relatedClass::whereIn($ownerKey, $foreignKeyValues)->toBase()->cursor();

        $resourceClass = ResourceResolver::get($relatedClass);

        foreach ($relatedRecords as $record) {
            if ($resourceClass) {
                $attr = $resourceClass::make($record);
            } else {
                $attr = (array) $record;
            }

            $wrappedItem = [
                'id' => $record->{$relatedPkName},
                'op' => Operation::create->value,
                'type' => $relatedType,
                'attr' => $attr,
            ];

            fwrite($stream, json_encode($wrappedItem, $flags) . PHP_EOL);
        }
    }

    /**
     * @param \Illuminate\Database\Eloquent\Relations\HasOne $relation
     * @param array<mixed> $parentIds
     * @param resource $stream
     * @param int $flags
     * @return void
     */
    private function streamHasOneRelation($relation, array $parentIds, $stream, int $flags): void
    {
        $foreignKey = $relation->getForeignKeyName();
        $relatedModel = $relation->getRelated();
        $relatedTable = $relatedModel->getTable();
        $relatedType = ($this->tableToTypeMapper)($relatedTable);
        $relatedPkName = $relatedModel->getKeyName();
        $relatedClass = get_class($relatedModel);

        // Get the column name without table prefix for whereIn
        $foreignKeyColumn = last(explode('.', $foreignKey));

        // Query the related model directly to avoid unwanted constraints
        $relatedRecords = $relatedClass::whereIn($foreignKeyColumn, $parentIds)->toBase()->cursor();

        $resourceClass = ResourceResolver::get($relatedClass);

        foreach ($relatedRecords as $record) {
            if ($resourceClass) {
                $attr = $resourceClass::make($record);
            } else {
                $attr = (array) $record;
            }

            $wrappedItem = [
                'id' => $record->{$relatedPkName},
                'op' => Operation::create->value,
                'type' => $relatedType,
                'attr' => $attr,
            ];

            fwrite($stream, json_encode($wrappedItem, $flags) . PHP_EOL);
        }
    }

    /**
     * @param \Illuminate\Database\Eloquent\Relations\BelongsToMany $relation
     * @param array<mixed> $parentIds
     * @param resource $stream
     * @param int $flags
     * @return void
     */
    private function streamBelongsToManyRelation($relation, array $parentIds, $stream, int $flags): void
    {
        $relatedModel = $relation->getRelated();
        $relatedTable = $relatedModel->getTable();
        $relatedType = ($this->tableToTypeMapper)($relatedTable);
        $relatedPkName = $relatedModel->getKeyName();
        $relatedClass = get_class($relatedModel);

        // Get pivot table information
        $pivotTable = $relation->getTable();
        $foreignPivotKey = $relation->getForeignPivotKeyName();
        $relatedPivotKey = $relation->getRelatedPivotKeyName();
        $relatedKey = $relation->getRelatedKeyName();

        // Query through the pivot table directly
        $relatedRecords = $relatedClass::select($relatedTable . '.*')
            ->join($pivotTable, $relatedTable . '.' . $relatedKey, '=', $pivotTable . '.' . $relatedPivotKey)
            ->whereIn($pivotTable . '.' . $foreignPivotKey, $parentIds)
            ->toBase()
            ->cursor();

        $resourceClass = ResourceResolver::get($relatedClass);

        foreach ($relatedRecords as $record) {
            if ($resourceClass) {
                $attr = $resourceClass::make($record);
            } else {
                $attr = (array) $record;
            }

            $wrappedItem = [
                'id' => $record->{$relatedPkName},
                'op' => Operation::create->value,
                'type' => $relatedType,
                'attr' => $attr,
            ];

            fwrite($stream, json_encode($wrappedItem, $flags) . PHP_EOL);
        }
    }
}
