<?php

namespace Wappo\LaravelSchemaApi\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
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
    protected $description = 'Generates the fat client supporting files';

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
            $model = ModelResolver::get($table);

            $collectionName = $table;
            $enumCases[class_basename($model)] = $collectionName;

            $collections[] = [
                'modelName' => class_basename($model),
                'collectionName' => $collectionName,
                'morphAlias' => Relation::getMorphAlias($model),
            ];

            $this->buildModelTypeAndBuilder($model);
            $this->buildModelForm($model, $collectionName);
        }

        foreach ($collections as $collection) {
            $this->buildCollectionStore($collection['collectionName'], $collection['modelName']);
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

    protected function buildCollectionStore(string $collectionName, string $modelName): void
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
            $this->info("Creating $target");
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
            $this->info("Skipping $target");
        }
    }

    protected function buildModelTypeAndBuilder(string $modelClass) {
        $modelName = class_basename($modelClass);
        $properties = [];

        $columns = DB::getSchemaBuilder()->getColumns((new $modelClass())->getTable());
        foreach ($columns as $column) {
            $type = $this->databaseColumnTypeToTypescriptType($column['type'], $column['type_name']);
            $type .= ' | null';

            $default = explode('::', $column['default'])[0];
            $properties[$column['name']] = $type;
            $defaults[$column['name']] = empty($default) ? 'null' : $default;
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

    protected function databaseColumnTypeToTypescriptType(string $type, string $type_name): string
    {
        if ($type_name === 'uuid' || $type_name === 'varchar' || $type_name === 'text') {
            return 'string';
        }

        if ($type_name === 'timestamp' || $type_name === 'timestamptz') {
            return 'Date';
        }

        if ($type_name === 'jsonb') {
            return 'Array<any>|Record<string, any>';
        }

        if ($type === 'integer') {
            return 'number';
        }

        if ($type === 'boolean') {
            return 'boolean';
        }

        throw new Exception("Unknown type $type or type_name $type_name");
    }
}
