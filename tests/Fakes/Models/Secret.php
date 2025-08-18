<?php

declare(strict_types=1);

namespace Wappo\LaravelSchemaApi\Tests\Fakes\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Wappo\LaravelSchemaApi\Attributes\ApiIgnore;
use Wappo\LaravelSchemaApi\Concerns\HasDateFormat;
use Wappo\LaravelSchemaApi\Tests\Factories\SecretFactory;

/**
 *
 *
 * @property string $id
 * @property string $launch_code
 * @property string $nuke_payload
 * @property bool $is_armed
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $deleted_at
 * @method static SecretFactory factory($count = null, $state = [])
 * @method static Builder<static>|Secret newModelQuery()
 * @method static Builder<static>|Secret newQuery()
 * @method static Builder<static>|Secret query()
 * @method static Builder<static>|Secret whereCreatedAt($value)
 * @method static Builder<static>|Secret whereDeletedAt($value)
 * @method static Builder<static>|Secret whereId($value)
 * @method static Builder<static>|Secret whereIsArmed($value)
 * @method static Builder<static>|Secret whereLaunchCode($value)
 * @method static Builder<static>|Secret whereNukePayload($value)
 * @method static Builder<static>|Secret whereUpdatedAt($value)
 * @mixin \Eloquent
 */
#[ApiIgnore]
class Secret extends Model
{
    use HasUuids, HasFactory, HasDateFormat;

    protected function casts(): array
    {
        return [
            'is_armed' => 'boolean',
        ];
    }
}
