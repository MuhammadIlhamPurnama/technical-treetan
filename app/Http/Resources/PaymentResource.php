<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'payment_id' => $this->payment_id,
            'external_id' => $this->external_id,
            'payment_method' => $this->payment_method,
            'payment_channel' => $this->payment_channel,
            'status' => $this->status,
            
            // Amount information
            'amount' => $this->amount,
            'formatted_amount' => 'Rp ' . number_format($this->amount, 0, ',', '.'),
            'paid_amount' => $this->paid_amount,
            'formatted_paid_amount' => $this->paid_amount ? 'Rp ' . number_format($this->paid_amount, 0, ',', '.') : null,
            'currency' => $this->currency,
            
            // Payment URL for user to complete payment
            'payment_url' => $this->payment_url,
            
            // Status checks
            'is_successful' => $this->isSuccessful(),
            'is_pending' => $this->isPending(),
            'is_expired' => $this->isExpired(),
            
            // Order information
            'order' => $this->when($this->relationLoaded('order'), [
                'id' => $this->order?->id,
                'order_number' => $this->order?->order_number,
                'status' => $this->order?->status,
                'payment_status' => $this->order?->payment_status,
                'total_amount' => $this->order?->total_amount,
                'formatted_total_amount' => $this->order ? 'Rp ' . number_format($this->order->total_amount, 0, ',', '.') : null,
            ]),
            
            // Timestamps
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            'expired_at' => $this->expired_at?->format('Y-m-d H:i:s'),
            'paid_at' => $this->paid_at?->format('Y-m-d H:i:s'),
            
            // Failure information
            'failure_reason' => $this->when($this->status === 'failed', $this->failure_reason),
            
            // Additional metadata (excluding sensitive data)
            'payment_instructions' => $this->when($this->isPending() && $this->xendit_response, [
                'virtual_account_number' => $this->xendit_response['available_banks'][0]['virtual_account_number'] ?? null,
                'bank_code' => $this->xendit_response['available_banks'][0]['bank_code'] ?? null,
                'expiry_date' => $this->xendit_response['expiry_date'] ?? null,
            ]),
        ];
    }
}
