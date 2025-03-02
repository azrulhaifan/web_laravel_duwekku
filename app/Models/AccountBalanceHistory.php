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
    ];

    protected $casts = [
        'old_balance' => 'decimal:2',
        'new_balance' => 'decimal:2',
        'amount' => 'decimal:2',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}
