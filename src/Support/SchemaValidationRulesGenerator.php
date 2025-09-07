<?php

declare(strict_types=1);

namespace Wappo\LaravelSchemaApi\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Wappo\LaravelSchemaApi\Enums\Operation;

readonly class SchemaValidationRulesGenerator
{
    public function __construct(private ColumnRuleMapper $columnRuleMapper)
    {
    }

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
        $casts = $model->getCasts();

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

            $ruleSet = ($this->columnRuleMapper)($column, $casts);

            $nullable = (bool)($column['nullable'] ?? false);
            if ($operation === Operation::create && !$nullable && $name !== $primaryKey) {
                array_unshift($ruleSet, 'required');
            } else {
                if($nullable) {
                    array_unshift($ruleSet, 'nullable');
                }
                array_unshift($ruleSet, 'sometimes');
            }

            $rules[$name] = implode('|', $ruleSet);
        }

        return $rules;
    }
}