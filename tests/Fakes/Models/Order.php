<?php

declare(strict_types=1);

namespace Wappo\LaravelSchemaApi\Tests\Fakes\Models;

use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Wappo\LaravelSchemaApi\Attributes\UseValidationRulesProvider;
use Wappo\LaravelSchemaApi\Casts\AsUuid;
use Wappo\LaravelSchemaApi\Concerns\HasDateFormat;
use Wappo\LaravelSchemaApi\Observers\CollectOperationObserver;
use Wappo\LaravelSchemaApi\Tests\Fakes\Validators\OrderValidationRulesProvider;

#[UseValidationRulesProvider(OrderValidationRulesProvider::class), ObservedBy(CollectOperationObserver::class)]
class Order extends Model
{
    use HasFactory, HasUuids, HasDateFormat, SoftDeletes;

    protected $fillable = [
        'id',
        'number',
        'text',
        'total',
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

    public function rows(): HasMany
    {
        return $this->hasMany(OrderRow::class);
    }

    protected static function booted()
    {
        static::deleting(fn(self $order) => $order->rows->each(fn(OrderRow $row) => $row->delete()));
        static::restoring(fn(self $order) => $order->rows()->withTrashed()->get()->each(fn(OrderRow $row) => $row->restore()));
    }
}
