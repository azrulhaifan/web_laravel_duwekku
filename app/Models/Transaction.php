<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_id',
        'category_id',
        'to_account_id',
        'type',
        'amount',
        'date',
        'time',
        'description',
        'attachment',
        'is_recurring',
        'recurring_type',
        'recurring_day',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'date' => 'date',
        'time' => 'datetime',
        'is_recurring' => 'boolean',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function toAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'to_account_id');
    }
}
