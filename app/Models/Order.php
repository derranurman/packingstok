<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_READY = 'ready_to_pack';
    public const STATUS_PACKED = 'packed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'tiktok_order_id',
        'tracking_number',
        'courier',
        'buyer_name',
        'status',
        'total_amount',
        'raw_payload',
        'packed_at',
        'packed_by_user_id',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'raw_payload' => 'array',
        'packed_at' => 'datetime',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function packedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'packed_by_user_id');
    }

    public function hasUnmappedItems(): bool
    {
        return $this->items()->whereNull('product_id')->exists();
    }

    public function isPackable(): bool
    {
        return $this->status === self::STATUS_READY;
    }
}
