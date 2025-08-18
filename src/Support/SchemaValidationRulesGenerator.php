<?php

declare(strict_types=1);

namespace Wappo\LaravelSchemaApi\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Wappo\LaravelSchemaApi\Enums\Operation;

class SchemaValidationRulesGenerator
{
    public function generate(string $modelClass, Operation $operation): array
    {
        if (!is_subclass_of($modelClass, Model::class)) {
            return [];
        }
        $model = new $modelClass();
        $table = $model->getTable();
        $fillable = $model->getFillable();
        $guarded = $model->getGuarded();
        $primaryKey = $model->getKeyName();

        $columns = collect(Schema::getColumns($table))
            ->filter(function (array $col) use ($fillable, $guarded) {
                $name = $col['name'];
                if (!empty($fillable)) {
                    return in_array($name, $fillable, true);
                }

                return !in_array($name, $guarded, true);
            });

        $rules = [];
        foreach ($columns as $column) {
            $name = $column['name'];
            $type = $column['type_name'];

            $ruleSet = match ($type) {
                'uuid' => ['uuid'],
                'jsonb' => ['array'],
                'boolean' => ['boolean'],
                'integer', 'bigint', 'smallint' => ['integer'],
                'float', 'double', 'decimal' => ['numeric'],
                'date', 'datetime', 'timestamp', 'timestamptz' => ['date'],
                default => ['string'],
            };

            if ($operation === Operation::create && empty($column['nullable']) && $name !== $primaryKey) {
                array_unshift($ruleSet, 'required');
            } else {
                array_unshift($ruleSet, 'sometimes');
            }

            if (in_array('string', $ruleSet, true) && preg_match('/\\((\\d+)\\)/', $type, $m)) {
                $ruleSet[] = 'max:' . (int) $m[1];
            }

            $rules[$name] = implode('|', $ruleSet);
        }

        return $rules;
    }
}