<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;
use Wappo\LaravelSchemaApi\Support\ColumnRuleMapper;

it('maps data_types columns to correct validation rules', function () {
    $casts = [
        'json_col' => 'array',
    ];

    $mapper = new ColumnRuleMapper();
    $columns = Schema::getColumns('data_types');

    $expected = [
        'id' => ['string'],
        'string' => ['string'],
        'char_col' => ['string'],
        'string_100' => ['string'],
        'text_col' => ['string'],
        'medium_text_col' => ['string'],
        'long_text_col' => ['string'],
        'tiny_integer_col' => ['integer'],
        'unsigned_tiny_integer_col' => ['integer'],
        'small_integer_col' => ['integer'],
        'unsigned_small_integer_col' => ['integer'],
        'medium_integer_col' => ['integer'],
        'unsigned_medium_integer_col' => ['integer'],
        'integer_col' => ['integer'],
        'unsigned_integer_col' => ['integer'],
        'big_integer_col' => ['integer'],
        'unsigned_big_integer_col' => ['integer'],
        'decimal_col' => ['numeric'], // SQLite shows 'numeric' without scale
        'float_col' => ['numeric'],
        'double_col' => ['numeric'],
        'boolean_col' => ['boolean'], // tinyint(1)
        'date_col' => ['date'],
        'time_col' => ['date_format:H:i:s'],
        'time_tz_col' => ['date_format:H:i:s'],
        'datetime_col' => ['date'],
        'datetime_tz_col' => ['date'],
        'timestamp_col' => ['date'],
        'timestamp_tz_col' => ['date'],
        'json_col' => ['array'],   // cast forces array input
        'ip_address_col' => ['string'],  // DB reports varchar → string
        'mac_address_col' => ['string'],
        'uuid_col' => ['string'],
        'ulid_col' => ['string'],
        'binary_col' => ['string'],  // blob over JSON → base64 string typically
        'enum_col' => ['string'],  // no enum options in schema dump
        'year_col' => ['integer'],
        'morphable_type' => ['string'],
        'morphable_id' => ['integer'],
        'nullable_morphable_type' => ['string'],
        'nullable_morphable_id' => ['integer'],
        'uuid_morphable_type' => ['string'],
        'uuid_morphable_id' => ['string'],
        'ulid_morphable_type' => ['string'],
        'ulid_morphable_id' => ['string'],
        'user_id' => ['integer'],
        'team_uuid' => ['string'],
        'project_ulid' => ['string'],
        'created_at' => ['date'],
        'updated_at' => ['date'],
    ];

    // Sanity: make sure our expected set matches the actual column names
    expect(collect($columns)->pluck('name')->all())->toEqualCanonicalizing(array_keys($expected));

    // Assert each column maps to the expected rules
    foreach ($columns as $col) {
        $name = $col['name'];
        $rules = $mapper($col, $casts);

        expect($rules)->toEqual($expected[$name], "Column [$name] rules mismatch");
    }
});