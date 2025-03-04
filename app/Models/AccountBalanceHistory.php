<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountBalanceHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_id',
        'old_balance',
        'new_balance',
        'amount',
        'type',
        'source_type',
        'source_id',
        'description',
        'is_custom_unit',
        'is_estimation',
    ];

    protected $casts = [
        'old_balance' => 'decimal:4',
        'new_balance' => 'decimal:4',
        'amount' => 'decimal:4',
        'is_custom_unit' => 'boolean',
        'is_estimation' => 'boolean',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}
