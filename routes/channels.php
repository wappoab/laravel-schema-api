<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| package supports. The given callback will be used to retrieve the user
| that can listen to the channel.
|
*/

Broadcast::channel('user.{id}', function ($user, $id) {
    // Compare as strings to support both integer IDs and UUIDs
    return (string) $user->id === (string) $id;
});
