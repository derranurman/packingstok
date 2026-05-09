<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderImport extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'filename',
        'rows_read',
        'orders_created',
        'orders_updated',
        'rows_skipped',
        'warnings',
    ];

    protected $casts = [
        'warnings' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
