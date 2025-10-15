<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Gate;
use Wappo\LaravelSchemaApi\Tests\Fakes\Models\User;
use Wappo\LaravelSchemaApi\Tests\TestCase;

uses(TestCase::class)->in(__DIR__.'/Tests')
    ->beforeEach(function () {
        $this->authenticatedUser = User::factory()->create();
        $this->actingAs($this->authenticatedUser);

        // Allow all Gate checks by default for testing purposes
        // The authorization tests demonstrate how authorization WOULD work
        // when proper Gate rules or policies are defined in a real application
        Gate::before(fn () => true);
    });
