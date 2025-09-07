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
use Wappo\LaravelSchemaApi\Casts\AsUuid;
use Wappo\LaravelSchemaApi\Concerns\HasDateFormat;
use Wappo\LaravelSchemaApi\Observers\CollectOperationObserver;

#[ObservedBy(CollectOperationObserver::class)]
class OrderRow extends Model
{
    use HasFactory, HasUuids, HasDateFormat, SoftDeletes;

    protected $fillable = [
        'id',
        'order_id',
        'specification',
        'quantity',
        'price',
        'total',
    ];

    protected function casts()
    {
        return [
            'id' => AsUuid::class,
            'order_id' => AsUuid::class,
            'specification' => 'string',
            'quantity' => 'float',
            'price' => 'float',
            'total' => 'float',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function entries(): HasMany
    {
        return $this->hasMany(OrderRowEntry::class);
    }

    protected static function booted()
    {
        $recalcTotal = function (self $row) {
            $total = static::query()
                ->where('order_id', $row->order_id)
                ->sum('total');
            $row->order->update(['total' => $total]);
        };
        $recalcRowTotal = function (self $row) {
            $row->total = $row->quantity * $row->price;
        };

        static::created($recalcTotal);
        static::updated($recalcTotal);
        static::deleted($recalcTotal);
        static::restored($recalcTotal);

        static::creating($recalcRowTotal);
        static::updating($recalcRowTotal);

        static::deleting(fn(self $orderRow) => $orderRow->entries->each(fn(OrderRowEntry $orderRowEntry) => $orderRowEntry->delete()));
        static::restoring(fn(self $orderRow) => $orderRow->entries()->withTrashed()->get()->each(fn(OrderRowEntry $rowEntry) => $rowEntry->restore()));
    }
}
