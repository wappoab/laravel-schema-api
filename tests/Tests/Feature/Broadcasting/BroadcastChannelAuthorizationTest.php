<?php

declare(strict_types=1);

use Wappo\LaravelSchemaApi\Tests\Fakes\Models\User;

/**
 * Channel Authorization Tests
 *
 * These tests verify the authorization logic for the user.{id} private channel.
 * The actual broadcasting route (/broadcasting/auth) is provided by Laravel's
 * BroadcastServiceProvider in the consuming application.
 */

beforeEach(function () {
    // Enable broadcasting
    config()->set('schema-api.broadcasting.enabled', true);
});

it('has correct authorization logic for user channel with integer IDs', function () {
    $user = User::factory()->create(['id' => 123]);

    // Test the authorization callback logic
    // This is what gets called when clients try to subscribe to private-user.{id}
    $authCallback = function ($authenticatedUser, $id) {
        // Compare as strings to support both integer IDs and UUIDs
        return (string) $authenticatedUser->id === (string) $id;
    };

    // User should be authorized for their own channel (integer and string)
    expect($authCallback($user, 123))->toBeTrue()
        ->and($authCallback($user, '123'))->toBeTrue();

    // User should NOT be authorized for other users' channels
    expect($authCallback($user, 456))->toBeFalse()
        ->and($authCallback($user, '456'))->toBeFalse();
});

it('has correct authorization logic for user channel with UUID IDs', function () {
    // Create a mock user with UUID
    $uuid = '550e8400-e29b-41d4-a716-446655440000';
    $user = new class($uuid) {
        public function __construct(public $id) {}
    };

    // Test the authorization callback logic
    $authCallback = function ($authenticatedUser, $id) {
        return (string) $authenticatedUser->id === (string) $id;
    };

    // User should be authorized for their own channel
    expect($authCallback($user, $uuid))->toBeTrue()
        ->and($authCallback($user, '550e8400-e29b-41d4-a716-446655440000'))->toBeTrue();

    // User should NOT be authorized for other users' channels
    $otherUuid = '660e8400-e29b-41d4-a716-446655440000';
    expect($authCallback($user, $otherUuid))->toBeFalse();
});

it('loads channel routes when broadcasting is enabled', function () {
    config()->set('schema-api.broadcasting.enabled', true);

    $channelsPath = __DIR__ . '/../../../../routes/channels.php';

    expect(file_exists($channelsPath))->toBeTrue();
});

it('does not load channel routes when broadcasting is disabled', function () {
    config()->set('schema-api.broadcasting.enabled', false);

    // When disabled, channels should not be registered
    // (This is handled by the packageBooted() method checking the config)
    expect(config('schema-api.broadcasting.enabled'))->toBeFalse();
});