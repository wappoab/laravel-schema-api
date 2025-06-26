<?php

declare(strict_types=1);

namespace Wappo\LaravelSchemaApi\Tests\Fakes\Models;

use Database\Factories\SecretFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Wappo\LaravelSchemaApi\Attributes\ApiIgnore;
use Wappo\LaravelSchemaApi\Concerns\HasDateFormat;

#[ApiIgnore]
/**
 *
 *
 * @property string $id
 * @property string $real_name
 * @property string $noc_list_name
 * @property bool $is_dead
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $deleted_at
 * @method static \Database\Factories\SecretFactory factory($count = null, $state = [])
 * @method static Builder<static>|Secret newModelQuery()
 * @method static Builder<static>|Secret newQuery()
 * @method static Builder<static>|Secret query()
 * @method static Builder<static>|Secret whereCreatedAt($value)
 * @method static Builder<static>|Secret whereDeletedAt($value)
 * @method static Builder<static>|Secret whereId($value)
 * @method static Builder<static>|Secret whereIsDead($value)
 * @method static Builder<static>|Secret whereNocListName($value)
 * @method static Builder<static>|Secret whereRealName($value)
 * @method static Builder<static>|Secret whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Secret extends Model
{
    use HasUuids, HasFactory, HasDateFormat;
}
