<?php

declare(strict_types=1);

namespace Wappo\LaravelSchemaApi\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Wappo\LaravelSchemaApi\Facades\ModelResolver;

class GenerateClientResources extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:generate-client-resources';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generates the schema-api client supporting files';

    /**
     * @var array<class-string<Model>> $models
     */
    protected array $models;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $tables = Schema::getTables();
        $enumCases = [];
        $collections = [];
        foreach ($tables as $table) {
            $collectionName = $table['name'];

            $model = ModelResolver::get($collectionName);
            if(!$model) {
                continue;
            }
            $enumCases[class_basename($model)] = $collectionName;
            $collections[] = [
                'modelName' => class_basename($model),
                'collectionName' => $collectionName,
                'morphAlias' => Relation::getMorphAlias($model),
                'modelFQN' => $model,
            ];
        }

        foreach ($collections as $collection) {
            $this->buildModelTypeAndBuilder($collection['modelFQN'], $collection['collectionName']);
            $this->buildModelForm($collection['modelName'], $collection['collectionName']);
            $this->buildCollectionStore($collection['modelName'], $collection['collectionName']);
        }

        File::put(
            base_path('./frontend/models/index.ts'),
            Blade::render(
                file_get_contents(__DIR__ . '/Stubs/models.blade.php'),
                [
                    'collections' => $collections,
                ]
            )
        );

        File::put(
            base_path('./frontend/stores/collections.ts'),
            Blade::render(
                file_get_contents(__DIR__ . '/Stubs/collections.blade.php'),
                [
                    'collections' => $collections,
                ]
            )
        );

        File::put(
            base_path('./frontend/enums/CollectionName.ts'),
            Blade::render(
                file_get_contents(__DIR__ . '/Stubs/enum.blade.php'),
                [
                    'cases' => $enumCases,
                    'name' => 'CollectionName',
                ]
            )
        );

        File::put(
            base_path('./frontend/components.ts'),
            Blade::render(
                file_get_contents(__DIR__ . '/Stubs/components.ts.blade.php'),
                [
                    'collections' => $collections,
                ]
            )
        );

        return self::SUCCESS;
    }

    protected function buildCollectionStore(string $modelName, string $collectionName): void
    {
        File::put(
            base_path('./frontend/stores/collections/' . $collectionName . '.ts'),
            Blade::render(
                file_get_contents(__DIR__ . '/Stubs/collection.blade.php'),
                [
                    'collectionName' => $collectionName,
                    'modelName' => $modelName,
                ]
            )
        );
    }

    protected function buildModelForm(string $modelClass, string $collectionName) {
        $modelName = class_basename($modelClass);
        $target = base_path('./frontend/components/forms/' . $modelName . 'Form.vue');

        if (!is_file($target)) {
            $this->comment("Creating $target");
            File::put(
                $target,
                Blade::render(
                    file_get_contents(__DIR__ . '/Stubs/Forms/model.form.vue.blade.php'),
                    [
                        'collectionName' => $collectionName,
                        'modelName' => $modelName,
                    ]
                )
            );
        } else {
            $this->comment("Skipping $target");
        }
    }

    protected function buildModelTypeAndBuilder(string $modelFQN, string $collectionName): void {
        $modelName = class_basename($modelFQN);
        $instance = new $modelFQN();
        $casts = $instance->getCasts();
        $defaults = [];
        $properties = [];
        $columns = Schema::getColumns($collectionName);
        foreach ($columns as $column) {
            $type = $this->databaseColumnTypeToTypescriptType($column['type'], $column['type_name'], $casts[$column['name']]??null);

            $default = explode('::', $column['default']??'')[0];
            $properties[$column['name']] = $type . ' | null';
            $defaults[$column['name']] = match (true) {
                $type === 'boolean' => $default === '1' ? 'true' : 'false',
                empty($default) => 'null',
                default => $default,
            };
        }

        File::put(
            base_path('./frontend/models/' . $modelName . '.ts'),
            Blade::render(
                file_get_contents(__DIR__ . '/Stubs/model.ts.blade.php'),
                [
                    'properties' => $properties,
                    'modelName' => $modelName,
                    'defaults' => $defaults,
                ]
            )
        );
    }

    protected function databaseColumnTypeToTypescriptType(string $type, string $type_name, ?string $cast): string
    {
        if ($type_name === 'blob' || $cast === 'string' || $type_name === 'uuid' || $type_name === 'varchar' || $type_name === 'text') {
            return 'string';
        }

        if ($type_name === 'time' || $type_name === 'date' || $type_name === 'timestamp' || $type_name === 'timestamptz' || $type_name === 'datetime' || $type_name === 'datetimetz') {
            return 'Date';
        }

        if ($type_name === 'jsonb') {
            return 'Array<any>|Record<string, any>';
        }

        if ($cast === 'boolean' || $type === 'boolean') {
            return 'boolean';
        }

        if ($type_name === 'double' || $type_name === 'float' || $type_name === 'numeric' || $type_name === 'tinyint' || $type === 'integer') {
            return 'number';
        }


        throw new Exception("Unknown type $type or type_name $type_name");
    }
}
