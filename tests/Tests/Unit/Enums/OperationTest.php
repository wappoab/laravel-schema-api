<?php

declare(strict_types=1);

use Wappo\LaravelSchemaApi\Enums\Operation;

it('has a working operation enum', function () {
    expect(Operation::create)
        ->toBeInstanceOf(Operation::class)
        ->name->toBe('create')
        ->value->toBe('I')
        ->and(Operation::update)
        ->toBeInstanceOf(Operation::class)
        ->name->toBe('update')
        ->value->toBe('U')
        ->and(Operation::delete)
        ->toBeInstanceOf(Operation::class)
        ->name->toBe('delete')
        ->value->toBe('R');
});

it('can be created from strings', function () {
    expect(Operation::from('I'))->toBe(Operation::create)
        ->and(Operation::from('U'))->toBe(Operation::update)
        ->and(Operation::from('R'))->toBe(Operation::delete);
});