<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Gate;
use Wappo\LaravelSchemaApi\Tests\Fakes\Models\User;
use Wappo\LaravelSchemaApi\Tests\TestCase;

uses(TestCase::class)->in(__DIR__.'/Tests')
    ->beforeEach(function () {
        $this->actingAs(User::factory()->create());
        Gate::before(fn () => true);
    });
