<?php


use Wappo\LaravelSchemaApi\Support\TableToTypeMapper;

it('maps table to type', function ($table, $type) {
    expect(app(TableToTypeMapper::class)($table))->toBe($type);
})->with([
    ['posts_datas', 'posts-datas'],
    ['posts_data', 'posts-data'],
    ['postsdata', 'postsdata'],
]);