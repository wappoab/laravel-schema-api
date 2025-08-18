<?php

declare(strict_types=1);

namespace Wappo\LaravelSchemaApi\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Wappo\LaravelSchemaApi\Enums\Operation;

class SchemaValidationRulesGenerator
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
            $type = $column['type_name'];

            $ruleSet = ($this->columnRuleMapper)($column, $casts);

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