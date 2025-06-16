<?php

namespace Wappo\LaravelSchemaApi\Commands;

use Illuminate\Console\Command;

class SchemaApiCommand extends Command
{
    public $signature = 'laravel-schema-api';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
