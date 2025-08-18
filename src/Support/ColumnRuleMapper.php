<?php

declare(strict_types=1);

namespace Wappo\LaravelSchemaApi\Support;

use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Casts\AsEncryptedArrayObject;
use Illuminate\Database\Eloquent\Casts\AsEncryptedCollection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Invokable mapper: one DB column descriptor (+ model casts) -> Laravel validation rules.
 *
 * Usage:
 *   $mapper = new ColumnRuleMapper();
 *   $rulesForField = $mapper($columnArray, $model->getCasts());
 */
final readonly class ColumnRuleMapper
{
    /**
     * @param array{name:string,type_name:?string,type:?string,nullable:bool,default:mixed} $column
     * @param array<string, string|object> $casts Model::getCasts()
     *
     * @return array<int, string|Rule>
     */
    public function __invoke(array $column, array $casts): array
    {
        $rules = [];

        $typeName = strtolower(
            (string) ($column['type_name'] ?? '')
        );   // e.g. varchar, integer, numeric, datetime, json
        $type = strtolower(
            (string) ($column['type'] ?? $typeName)
        ); // may include size: varchar(100), tinyint(1), decimal(10,2)

        // Casts (if any) take precedence when they imply an input shape
        $cast = $casts[$column['name']] ?? null;
        if ($cast !== null) {
            $castRules = $this->rulesFromCast($cast);
            if ($castRules !== null) {
                return array_merge($rules, $castRules);
            }
        }

        // ---- DB-type based fallbacks (SQLite / MySQL / PostgreSQL) ----

        // boolean
        if ($type === 'tinyint(1)' || $typeName === 'boolean' || $type === 'boolean') {
            return array_merge($rules, ['boolean']);
        }

        // integers
        if (
            in_array(
                $typeName,
                ['tinyint', 'smallint', 'mediumint', 'int', 'integer', 'bigint'],
                true
            )
            || Str::startsWith($type, ['tinyint', 'smallint', 'mediumint', 'int', 'integer', 'bigint'])
        ) {
            return array_merge($rules, ['integer']);
        }

        // decimal / numeric
        if ($typeName === 'numeric' || $typeName === 'decimal' || Str::startsWith($type, 'decimal')) {
            $scale = $this->parseDecimalScale($type);

            return array_merge($rules, [$scale !== null ? "decimal:{$scale}" : 'numeric']);
        }

        // float / double / real
        if (in_array($typeName, ['float', 'double', 'real'], true)
            || Str::startsWith($type, ['float', 'double', 'real'])) {
            return array_merge($rules, ['numeric']);
        }

        // date / time / timestamp
        if ($typeName === 'date') {
            return array_merge($rules, ['date']); // or 'date_format:Y-m-d'
        }
        if ($typeName === 'time' || Str::startsWith($type, 'time')) {
            return array_merge($rules, ['date_format:H:i:s']);
        }
        if ($typeName === 'datetime' || $typeName === 'timestamp' || Str::contains($type, 'timestamp')) {
            return array_merge($rules, ['date']); // or 'date_format:Y-m-d H:i:s' / 'date_format:c'
        }

        // json / jsonb
        if ($typeName === 'json' || $typeName === 'jsonb' || Str::contains($type, 'json')) {
            return array_merge($rules, ['array']
            ); // for application/json requests; use 'json' if accepting raw JSON strings
        }

        // binary / blob
        if (
            $typeName === 'blob' || Str::contains($type, 'blob')
            || in_array(
                $typeName,
                ['binary', 'varbinary', 'bytea'],
                true
            )
        ) {
            return array_merge($rules, ['string']); // typically base64 over JSON
        }

        // varchar / char / text / enum / set (length-aware where available)
        if (
            in_array(
                $typeName,
                ['varchar', 'char', 'text', 'mediumtext', 'longtext', 'enum', 'set'],
                true
            )
            || Str::contains($type, ['varchar', 'char', 'text', 'enum', 'set'])
        ) {
            $out = ['string'];
            if ($len = $this->parseLength($type)) {
                $out[] = Str::startsWith($type, 'char(') ? "size:{$len}" : "max:{$len}";
            }

            return array_merge($rules, $out);
        }

        // catch-all
        return array_merge($rules, ['string']);
    }

    /**
     * @param string|object $cast
     *
     * @return array<int, string|Rule>|null
     */
    private function rulesFromCast(string|object $cast): ?array
    {
        if (is_object($cast)) {
            return match (true) {
                $cast instanceof AsArrayObject,
                    $cast instanceof AsEncryptedArrayObject,
                    $cast instanceof AsCollection,
                    $cast instanceof AsEncryptedCollection => ['array'],
                default => null, // unknown/custom cast class → fall back to DB type
            };
        }

        $cast = trim(strtolower($cast));

        // encrypted:* — string by default; arrays/objects/collections remain arrays
        if (Str::startsWith($cast, 'encrypted')) {
            return preg_match('/^encrypted:(array|object|collection)$/', $cast) ? ['array'] : ['string'];
        }

        // enum:Fully\Qualified\Enum
        if (Str::startsWith($cast, 'enum:')) {
            $enumClass = ltrim(substr($cast, 5), '\\');

            return class_exists($enumClass) ? [Rule::enum($enumClass)] : ['string'];
        }

        // dates
        if (in_array($cast, ['date', 'immutable_date'], true)) {
            return ['date'];
        }
        if (Str::startsWith($cast, 'datetime') || Str::startsWith($cast, 'immutable_datetime')) {
            $fmt = $this->castParamAfterColon($cast);

            return $fmt ? ["date_format:{$fmt}"] : ['date'];
        }
        if ($cast === 'timestamp') {
            return ['integer']; // Unix timestamp
        }

        // numerics
        if ($cast === 'boolean') {
            return ['boolean'];
        }
        if (in_array($cast, ['integer', 'int'], true)) {
            return ['integer'];
        }
        if (in_array($cast, ['real', 'float', 'double'], true)) {
            return ['numeric'];
        }
        if (Str::startsWith($cast, 'decimal')) {
            $scale = $this->castLastNumber($cast);

            return $scale !== null ? ["decimal:{$scale}"] : ['numeric'];
        }

        // array-ish
        if (in_array($cast, ['array', 'json', 'collection', 'object'], true)) {
            return ['array'];
        }

        // strings / hashed
        if (in_array($cast, ['string', 'hashed'], true)) {
            return ['string'];
        }

        return null; // unknown cast string → let DB type decide
    }

    private function parseLength(string $type): ?int
    {
        return preg_match('/\((\d+)\)/', $type, $m) ? (int) $m[1] : null;
    }

    private function parseDecimalScale(string $type): ?int
    {
        return preg_match('/\(\s*\d+\s*,\s*(\d+)\s*\)/', $type, $m) ? (int) $m[1] : null;
    }

    private function castParamAfterColon(string $cast): ?string
    {
        $pos = strpos($cast, ':');

        return $pos === false ? null : substr($cast, $pos + 1);
    }

    private function castLastNumber(string $cast): ?int
    {
        return preg_match('/(\d+)\s*$/', $cast, $m) ? (int) $m[1] : null;
    }
}
