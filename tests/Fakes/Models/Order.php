<?php

declare(strict_types=1);

namespace Wappo\LaravelSchemaApi\Tests\Fakes\Models;

use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Wappo\LaravelSchemaApi\Attributes\ApiInclude;
use Wappo\LaravelSchemaApi\Attributes\UseValidationRulesProvider;
use Wappo\LaravelSchemaApi\Casts\AsUuid;
use Wappo\LaravelSchemaApi\Concerns\HasApiIncludeCascadeDelete;
use Wappo\LaravelSchemaApi\Concerns\HasDateFormat;
use Wappo\LaravelSchemaApi\Observers\CollectOperationObserver;
use Wappo\LaravelSchemaApi\Tests\Fakes\Validators\OrderValidationRulesProvider;

#[UseValidationRulesProvider(OrderValidationRulesProvider::class), ObservedBy(CollectOperationObserver::class)]
class Order extends Model
{
    use HasFactory, HasUuids, HasDateFormat, SoftDeletes, HasApiIncludeCascadeDelete;

    protected $fillable = [
        'id',
        'number',
        'text',
        'total',
        'owner_id',
    ];

    protected function casts()
    {
        return [
            'id' => AsUuid::class,
            'number' => 'integer',
            'text' => 'string',
            'total' => 'float',
        ];
    }

    protected $attributes = [
        'total' => 0,
    ];

    #[ApiInclude(cascadeDelete: true)]
    public function rows(): HasMany
    {
        return $this->hasMany(OrderRow::class);
    }

    #[ApiInclude]
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    #[ApiInclude(cascadeDelete: true, forceDelete: true)]
    public function links(): HasMany
    {
        return $this->hasMany(OrderLink::class);
    }
}
