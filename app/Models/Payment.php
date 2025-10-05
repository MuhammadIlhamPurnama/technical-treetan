<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'order_id',
        'payment_id',
        'external_id',
        'payment_method',
        'payment_channel',
        'status',
        'amount',
        'paid_amount',
        'currency',
        'payment_url',
        'xendit_response',
        'callback_data',
        'expired_at',
        'paid_at',
        'failure_reason',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'xendit_response' => 'array',
            'callback_data' => 'array',
            'expired_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    /**
     * Generate unique external ID
     */
    public static function generateExternalId(): string
    {
        do {
            $externalId = 'PAY-' . date('Ymd') . '-' . strtoupper(uniqid());
        } while (self::where('external_id', $externalId)->exists());

        return $externalId;
    }

    /**
     * Get the order that owns the payment
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Check if payment is successful
     */
    public function isSuccessful(): bool
    {
        return $this->status === 'paid';
    }

    /**
     * Check if payment is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if payment is expired
     */
    public function isExpired(): bool
    {
        return $this->status === 'expired' || 
               ($this->expired_at && $this->expired_at->isPast());
    }

    /**
     * Mark payment as paid
     */
    public function markAsPaid(array $callbackData = []): bool
    {
        return $this->update([
            'status' => 'paid',
            'paid_at' => now(),
            'paid_amount' => $callbackData['paid_amount'] ?? $this->amount,
            'callback_data' => $callbackData,
        ]);
    }

    /**
     * Mark payment as failed
     */
    public function markAsFailed(string $reason = null, array $callbackData = []): bool
    {
        return $this->update([
            'status' => 'failed',
            'failure_reason' => $reason,
            'callback_data' => $callbackData,
        ]);
    }

    /**
     * Mark payment as expired
     */
    public function markAsExpired(array $callbackData = []): bool
    {
        return $this->update([
            'status' => 'expired',
            'callback_data' => $callbackData,
        ]);
    }
}
