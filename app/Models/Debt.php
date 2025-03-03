<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Debt extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_id',
        'transaction_id',
        'type',
        'person_name',
        'amount',
        'date',
        'due_date',
        'description',
        'is_settled',
        'settled_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'date' => 'date',
        'due_date' => 'date',
        'is_settled' => 'boolean',
        'settled_at' => 'datetime',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function settlementTransaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'settlement_transaction_id');
    }
}
