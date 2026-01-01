<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentPlan extends Model
{
    protected $fillable = [
        'name',
        'description',
        'date_from',
        'date_to',
        'date_filter',
        'filters',
        'payment_ids',
        'total_km',
        'total_eur',
        'total_usd',
        'payment_count',
        'is_paid',
        'paid_at',
        'created_by',
    ];

    protected $casts = [
        'date_from' => 'date',
        'date_to' => 'date',
        'filters' => 'array',
        'payment_ids' => 'array',
        'total_km' => 'decimal:2',
        'total_eur' => 'decimal:2',
        'total_usd' => 'decimal:2',
        'is_paid' => 'boolean',
        'paid_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function payments()
    {
        return Payment::whereIn('id', $this->payment_ids ?? [])->get();
    }
}
