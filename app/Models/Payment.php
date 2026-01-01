<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_id',
        'branch_id',
        'invoice_number',
        'amount',
        'currency',
        'status',
        'planned_date',
        'paid_date',
        'description',
        'created_by',
        'paid_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'planned_date' => 'date',
            'paid_date' => 'date',
        ];
    }

    public const STATUS_PLANNED = 'PLANNED';
    public const STATUS_PAID = 'PAID';
    public const STATUSES = [self::STATUS_PLANNED, self::STATUS_PAID];

    public const CURRENCY_KM = 'KM';
    public const CURRENCY_EUR = 'EUR';
    public const CURRENCIES = [self::CURRENCY_KM, self::CURRENCY_EUR];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function paidByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    public function markAsPaid(int $userId): void
    {
        $this->update([
            'status' => self::STATUS_PAID,
            'paid_date' => now()->toDateString(),
            'paid_by' => $userId,
        ]);
    }

    public function scopePlanned($query)
    {
        return $query->where('status', self::STATUS_PLANNED);
    }

    public function scopePaid($query)
    {
        return $query->where('status', self::STATUS_PAID);
    }

    public function scopeForToday($query)
    {
        return $query->whereDate('planned_date', today());
    }

    public function scopeOverdue($query)
    {
        return $query->planned()->whereDate('planned_date', '<', today());
    }
}
