<?php

declare(strict_types=1);

use Wappo\LaravelSchemaApi\Support\TypeToTableMapper;

it('maps table to type', function ($table, $type) {
    expect(app(TypeToTableMapper::class)($table))->toBe($type);
})->with([
    ['posts-datas', 'posts_datas'],
    ['posts-data', 'posts_data'],
    ['category', 'category'],
    ['something_else', 'something_else'],
]);